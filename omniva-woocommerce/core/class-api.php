<?php

use \Mijora\Omniva\OmnivaException;
use \Mijora\Omniva\Shipment\Shipment;
use \Mijora\Omniva\Shipment\ShipmentHeader;
use \Mijora\Omniva\Shipment\Label;
use \Mijora\Omniva\Shipment\Order;
use \Mijora\Omniva\Shipment\Manifest;
use \Mijora\Omniva\Shipment\CallCourier;
use \Mijora\Omniva\Shipment\Package\Package;
use \Mijora\Omniva\Shipment\Package\Address;
use \Mijora\Omniva\Shipment\Package\Contact;
use \Mijora\Omniva\Shipment\Package\AdditionalService;
use \Mijora\Omniva\Shipment\Package\Cod;
use \Mijora\Omniva\Shipment\Package\Measures;

class OmnivaLt_Api
{
  private $omnivalt_settings;
  private $omnivalt_configs;
  private $api_url;
  private $use_old_api = true;

  public function __construct()
  {
    $this->omnivalt_configs = OmnivaLt_Core::get_configs();
    $this->omnivalt_settings = get_option($this->omnivalt_configs['plugin']['settings_key']);
    $this->set_api_url($this->omnivalt_settings['api_url']);
  }

  private function set_api_url( $api_url )
  {
    $api_url = esc_url(preg_replace('{/$}', '', $api_url));
    $url_path = '/epmx/services/messagesService.wsdl';
    if ( ! str_contains($api_url, $url_path) ) {
      $api_url .= $url_path;
    }

    $this->api_url = $api_url;
  }

  public function get_tracking_number( $id_order )
  {
    $output = array(
      'status' => false,
      'msg' => '',
      'debug' => '',
      'barcodes' => array()
    );

    $order = OmnivaLt_Wc_Order::get_data($id_order);
    if ( ! $order ) {
      $output['msg'] = __('Failed to get WooCommerce order data', 'omnivalt');
      return $output;
    }

    try {
      /* Get all data */
      $data_client = $this->get_client_data($order);
      $data_shop = $this->get_shop_data();
      $data_settings = $this->get_settings_data();
      $data_packages = $this->get_packages_data($order);

      $label_comment = $this->fill_comment_variables($data_settings->label_comment, $data_settings->comment_variables, $order );

      /* Create shipment */
      $api_shipment = new Shipment();
      $api_shipment
        ->setComment($label_comment)
        ->setShowReturnCodeEmail($data_settings->send_return_code->email)
        ->setShowReturnCodeSms($data_settings->send_return_code->sms);
      $this->setAuth($api_shipment);

      /* Prepare shipment header */
      $api_shipmentHeader = new ShipmentHeader();
      $api_shipmentHeader
        ->setSenderCd($data_settings->api_user)
        ->setFileId(current_time('YmdHms'));
      $api_shipment->setShipmentHeader($api_shipmentHeader);

      /* Prepare packages */
      $packages = array();
      foreach ( $data_packages as $data_package ) {
        /* Create package */
        $shipment_service = OmnivaLt_Helper::get_shipping_service_code($data_shop->country, $data_client->country, $data_settings->pickup_method . ' ' . $data_package->method);
        if ( ! is_string($shipment_service) ) {
          if ( isset($shipment_service['msg']) ) {
            throw new OmnivaException($shipment_service['msg']);
          }
          throw new OmnivaException(__('Failed to get shipment service', 'omnivalt'));
        }
        
        $api_package = new Package();
        $api_package
          ->setId($data_package->id)
          ->setService($shipment_service);

        /* Set additional services */
        $additional_services = $this->get_additional_services($order, $shipment_service);
        $all_api_additional_services = array();
        foreach ( $additional_services as $additional_service_key => $additional_service_code ) {
          $service_conditions = $this->check_additional_service_condition($shipment_service, $additional_service_code); //Temporary use while this function not exist in API
          //$service_conditions = Shipment::getAdditionalServiceConditionsForShipment($shipment_service, $additional_service_code); //Function from API
          if ( ! empty($service_conditions) ) {
            if ( isset($service_conditions->only_countries) && ! in_array($data_client->country, $service_conditions->only_countries) ) {
              continue;
            }
          }
          $api_additional_service = new AdditionalService();
          $api_additional_service
            ->setServiceCode($additional_service_code);
          $all_api_additional_services[] = $api_additional_service;
          /* Add additional service data */
          if ( $additional_service_key == 'cod' ) {
            $api_cod = new Cod();
            $api_cod
              ->setAmount($data_package->amount)
              ->setBankAccount($data_settings->bank_account)
              ->setReceiverName($data_settings->company)
              ->setReferenceNumber($this->get_reference_number($order->id));
            $api_package->setCod($api_cod);
          }
        }
        $api_package->setAdditionalServices($all_api_additional_services);

        /* Set measures */
        $api_measures = new Measures();
        $api_measures
          ->setWeight($data_package->weight)
          ->setLength($data_package->length)
          ->setHeight($data_package->height)
          ->setWidth($data_package->width);
        $api_package->setMeasures($api_measures);

        /* Set receiver */
        $api_receiver_address = new Address();
        $api_receiver_address
          ->setCountry($data_client->country)
          ->setPostcode($data_client->postcode)
          ->setDeliverypoint($data_client->city)
          ->setStreet($data_client->street);
        if ( OmnivaLt_Configs::get_method_terminals_type($data_package->method) ) {
          $api_receiver_address->setOffloadPostcode($data_package->terminal);
        }
        $api_receiver_contact = new Contact();
        $api_receiver_contact
          ->setAddress($api_receiver_address)
          ->setEmail($data_client->email)
          ->setMobile($data_client->phone)
          ->setPersonName($this->get_client_fullname($data_client));
        $api_package->setReceiverContact($api_receiver_contact);

        /* Set sender */
        $api_sender_address = new Address();
        $api_sender_address
          ->setCountry($data_shop->country)
          ->setPostcode($data_shop->postcode)
          ->setDeliverypoint($data_shop->city)
          ->setStreet($data_shop->street);
        $api_sender_contact = new Contact();
        $api_sender_contact
          ->setAddress($api_sender_address)
          ->setEmail($data_shop->email)
          ->setMobile($data_shop->phone)
          ->setPersonName($data_shop->name);
        $api_package->setSenderContact($api_sender_contact);

        $packages[] = $api_package;
      }
      if ( empty($packages) ) {
        throw new OmnivaException(__('Failed to get packages', 'omnivalt'));
      }
      $api_shipment->setPackages($packages);
      OmnivaLt_Debug::debug_request($api_shipment, 'json');

      /* Register shipment */
      $result = $api_shipment->registerShipment();
      $debug_data = OmnivaLt_Debug::debug_response($result, 'json');
      $output['debug'] = $debug_data;
    } catch (OmnivaException $e) {
      $output['msg'] = $e->getMessage();
      $output['debug'] = $e->getData();
      return $output;
    }

    if ( ! isset($result['barcodes']) ) {
      $output['msg'] = __('Failed to register shipments', 'omnivalt');
      return $output;
    }

    $output['status'] = true;
    $output['barcodes'] = $result['barcodes'];
    return $output;
  }

  public function get_shipment_labels( $barcodes )
  {
    $output = array(
      'status' => false,
      'msg' => '',
      'debug' => '',
      'labels' => array()
    );

    try {
      $api_label = new Label();
      $this->setAuth($api_label);

      $labels = $api_label->getLabels($barcodes);
      $output['debug'] = OmnivaLt_Debug::debug_response($labels);
      if ( empty($labels['labels']) ) {
        $output['msg'] = __('Failed to get labels', 'omnivalt');
        return $output;
      }
      $output['status'] = true;
      $output['labels'] = $labels['labels'];
    } catch (OmnivaException $e) {
      $output['msg'] = $e->getMessage();
      $output['debug'] = $e->getData();
    }

    return $output;
  }

  public function download_shipment_labels( $barcodes )
  {
    $output = array(
      'status' => false,
      'msg' => '',
      'debug' => ''
    );

    $print_type = (isset($this->omnivalt_settings['print_type'])) ? $this->omnivalt_settings['print_type'] : '4';
    $print_type_bool = ($print_type == '4');

    try {
      $api_label = new Label();
      $this->setAuth($api_label);

      $api_label->downloadLabels($barcodes, $print_type_bool, 'D', 'Omnivalt_labels_' . current_time('Ymd_His'));
      $output['status'] = true;
    } catch (OmnivaException $e) {
      $output['msg'] = $e->getMessage();
      $output['debug'] = $e->getData();
    }

    return $output;
  }

  public function get_manifest( $orders_ids )
  {
    $output = array(
      'status' => false,
      'msg' => '',
      'success' => array(),
      'debug' => ''
    );

    if ( ! is_array($orders_ids) ) {
      $orders_ids = array($orders_ids);
    }

    try {
      $data_shop = $this->get_shop_data();
      $data_settings = $this->get_settings_data();

      /* Set sender */
      $api_sender_address = new Address();
      $api_sender_address
        ->setCountry($data_shop->country)
        ->setPostcode($data_shop->postcode)
        ->setDeliverypoint($data_shop->city)
        ->setStreet($data_shop->street);
      $api_sender_contact = new Contact();
      $api_sender_contact
        ->setAddress($api_sender_address)
        ->setEmail($data_shop->email)
        ->setMobile($data_shop->phone)
        ->setPersonName($data_shop->name);

      /* Create manifest */
      $api_manifest = new Manifest();
      $api_manifest
        ->setSender($api_sender_contact)
        ->showBarcode($data_settings->show_barcode)
        ->setString('sender_address', _x('Sender address', 'Manifest', 'omnivalt'))
        ->setString('row_number', _x('No.', 'Manifest', 'omnivalt'))
        ->setString('shipment_number', _x('Shipment number', 'Manifest', 'omnivalt'))
        ->setString('order_number', _x('Order No.', 'Manifest', 'omnivalt'))
        ->setString('date', _x('Date', 'Manifest', 'omnivalt'))
        ->setString('quantity', _x('Quantity', 'Manifest', 'omnivalt'))
        ->setString('weight', _x('Weight', 'Manifest', 'omnivalt') . ' (kg)')
        ->setString('recipient_address', _x("Recipient's name and address", 'Manifest', 'omnivalt'))
        ->setString('courier_signature', _x("Courier name, surname, signature", 'Manifest', 'omnivalt'))
        ->setString('sender_signature', _x("Sender name, surname, signature", 'Manifest', 'omnivalt'));

      /* Prepare orders */
      foreach ( $orders_ids as $order_id ) {
        $order_id = (int) $order_id;
        if ( empty($order_id) ) {
          continue;
        }

        $order = OmnivaLt_Wc_Order::get_data($order_id);
        if ( ! $order ) {
          continue;
        }
        
        $data_client = $this->get_client_data($order);
        $data_shipments = $this->get_shipments_data($order);
        if ( empty($data_shipments->barcodes) ) {
          continue;
        }

        /* Add order */
        $api_order = new Order();
        $api_order
          ->setTracking($data_shipments->barcodes[0])
          ->setQuantity(count($data_shipments->barcodes))
          ->setWeight($data_shipments->weight)
          ->setReceiver($this->get_client_fulladress($order))
          ->setOrderNumber($order->number);
        $api_manifest->addOrder($api_order);

        $output['success'][] = $order_id;
      }

      $api_manifest->downloadManifest('I', 'Omnivalt_manifest_' . current_time('Ymd_His'));
      $output['status'] = true;
    }  catch (OmnivaException $e) {
      $output['msg'] = $e->getMessage();
      $output['debug'] = $e->getData();
    }

    return $output;
  }

  public function call_courier( $parcels_number = 0 )
  {
    return ($this->use_old_api) ? $this->call_courier_old($parcels_number) : $this->call_courier_omx($parcels_number);
  }

  public function call_courier_old( $parcels_number = 0 )
  {
    $is_cod = false;
    $parcel_terminal = "";
    $shop = $this->get_shop_data();
    $pickStart = OmnivaLt_Helper::get_formated_time($shop->pick_from, '8:00');
    $pickFinish = OmnivaLt_Helper::get_formated_time($shop->pick_until, '17:00');
    $parcels_number = ($parcels_number > 0) ? $parcels_number : 1;

    try {
      $api_address = new Address();
      $api_address
        ->setCountry($shop->country)
        ->setPostcode($shop->postcode)
        ->setDeliverypoint($shop->city)
        ->setStreet($shop->street);
      $api_sender = new Contact();
      $api_sender
        ->setAddress($api_address)
        ->setMobile($shop->phone)
        ->setPersonName($shop->name);

      $api_call = new CallCourier();
      $this->setAuth($api_call);
      $api_call
        ->setSender($api_sender)
        ->setEarliestPickupTime($pickStart)
        ->setLatestPickupTime($pickFinish)
        ->setDestinationCountry(OmnivaLt_Helper::get_shipping_service($shop->api_country, 'call'))
        ->setParcelsNumber($parcels_number);

      $api_call->callCourier();
      $debug_data = $api_call->getDebugData();
      OmnivaLt_Debug::debug_request($debug_data['request']);
      return array(
        'status' => true,
        'barcodes' => '',
        'debug' => OmnivaLt_Debug::debug_response($debug_data['response'])
      );
    } catch (OmnivaException $e) {
      $debug_data = $e->getData();
      OmnivaLt_Debug::debug_request($debug_data['request']);
      $debug_response = (!empty($debug_data['response'])) ? $debug_data['response'] : $debug_data['url'];
      return array('status' => false, 'msg' => $e->getMessage(), 'debug' => OmnivaLt_Debug::debug_response($debug_response));
    }

    return array('status' => false, 'msg' => __('Failed to call courier', 'omnivalt'));
  }

  public function call_courier_omx( $parcels_number = 0 ) //The OMX courier invitation is not working yet.
  {
    $shop = $this->get_shop_data();

    try {
      return array(
        'status' => true,
        'call_id' => rand(1000000,9999999),
        'start_time' => date('Y-m-d H:i:s', strtotime('2023-10-05T08:00:00')),
        'end_time' => date('Y-m-d H:i:s', strtotime('2023-10-05T17:00:00')),
      );
    } catch (OmnivaException $e) {
      return array('status' => false, 'msg' => $e->getMessage());
    }

    return array('status' => false, 'msg' => __('Failed to call courier', 'omnivalt'));
  }

  public function cancel_courier_call( $call_id )
  {
    if ( $this->use_old_api ) {
      return array('status' => false, 'call_id' => $call_id, 'msg' => __('The old API does not have a courier cancel option', 'omnivalt'));
    }

    try {
      return array(
        'status' => true,
        'call_id' => $call_id,
      );
    } catch (OmnivaException $e) {
      return array('status' => false, 'call_id' => $call_id, 'msg' => $e->getMessage());
    }

    return array('status' => false, 'call_id' => $call_id, 'msg' => __('Failed to cancel courier', 'omnivalt'));
  }

  private function setAuth( $object )
  {
    if( method_exists($object, 'setAuth') ) {
      $object->setAuth(
        $this->clean($this->omnivalt_settings['api_user']),
        $this->clean($this->omnivalt_settings['api_pass']),
        $this->clean(preg_replace('{/$}', '', $this->omnivalt_settings['api_url'])),
        OmnivaLt_Debug::check_debug_enabled()
      );
    }
  }

  private function get_shop_data( $object = true )
  {
    $data = array(
      'name' => $this->clean($this->omnivalt_settings['shop_name']),
      'street' => $this->clean($this->omnivalt_settings['shop_address']),
      'city' => $this->clean($this->omnivalt_settings['shop_city']),
      'country' => $this->clean($this->omnivalt_settings['shop_countrycode']),
      'postcode' => $this->clean($this->omnivalt_settings['shop_postcode']),
      'phone' => $this->clean($this->omnivalt_settings['shop_phone']),
      'email' => (! empty($this->omnivalt_settings['shop_email'])) ? $this->clean($this->omnivalt_settings['shop_email']) : get_bloginfo('admin_email'),
      'pick_day' => current_time('Y-m-d'),
      'pick_from' => $this->omnivalt_settings['pick_up_start'] ? $this->clean($this->omnivalt_settings['pick_up_start']) : '8:00',
      'pick_until' => $this->omnivalt_settings['pick_up_end'] ? $this->clean($this->omnivalt_settings['pick_up_end']) : '17:00',
      'api_country' => $this->clean($this->omnivalt_settings['api_country']),
    );

    if ( current_time('timestamp') > strtotime($data['pick_day'] . ' ' . $data['pick_from']) ) {
      $data['pick_day'] = date('Y-m-d', strtotime($data['pick_day'] . "+1 days"));
    }

    return ($object) ? (object) $data : $data;
  }

  private function get_client_data( $order, $object = true )
  {
    $data = array(
      'name' => $this->clean($order->shipping->name),
      'surname' => $this->clean($order->shipping->surname),
      'company' => $this->clean($order->shipping->company),
      'street' => $this->clean($order->shipping->address_1),
      'postcode' => $this->clean($order->shipping->postcode),
      'city' => $this->clean($order->shipping->city),
      'country' => $this->clean($order->shipping->country),
      'email' => $this->clean($order->shipping->email),
      'phone' => $this->clean($order->shipping->phone),
    );

    if ( ! empty($order->shipping->address_2) ) {
      $data['street'] .= ' - ' . $this->clean($order->shipping->address_2);
    }

    if ( empty($data['postcode']) && empty($data['city']) && empty($data['street']) && empty($data['country']) ) {
      $data['postcode'] = $this->clean($order->billing->postcode);
      $data['city'] = $this->clean($order->billing->city);
      $data['street'] = $this->clean($order->billing->address_1);
      $data['country'] = $this->clean($order->billing->country);
      if ( ! empty($order->billing->address_2) ) {
        $data['street'] .= ' - ' . $this->clean($order->billing->address_2);
      }
    }
    if ( empty($data['name']) && empty($data['surname']) ) {
      $data['name'] = $this->clean($order->billing->name);
      $data['surname'] = $this->clean($order->billing->surname);
    }
    if ( empty($data['name']) && empty($data['surname']) && empty($data['company']) ) {
      $data['company'] = $this->clean($order->billing->company);
    }
    if ( empty($data['country']) ) $data['country'] = $this->clean($this->omnivalt_settings['shop_countrycode']);
    if ( empty($data['country']) ) $data['country'] = 'LT';
    if ( empty($data['phone']) ) $data['phone'] = $this->clean($order->billing->phone);
    
    //Fix postcode
    $data['postcode'] = preg_replace("/[^0-9]/", "", $data['postcode']);
    if ($data['country'] == 'LV') {
      $data['postcode'] = 'LV-' . $data['postcode'];
    }

    return ($object) ? (object) $data : $data;
  }

  private function get_client_fullname( $client_data )
  {
    if ( ! empty($client_data->company) ) {
      return trim($client_data->company);
    }

    return trim($client_data->name . ' ' . $client_data->surname);
  }

  private function get_client_fulladress( $order )
  {    
    $address = OmnivaLt_Order::get_customer_full_address($order);
    if ( ! empty($order->omniva->terminal_id) ) {
      $address = OmnivaLt_Terminals::get_terminal_address($order->omniva->terminal_id, true);
    }

    return trim(OmnivaLt_Order::get_customer_fullname_or_company($order) . ', ' . $address);
  }

  private function get_settings_data()
  {
    $data = array(
      'api_user' => '',
      'pickup_method' => 'c',
      'label_comment' => '',
      'comment_variables' => array(),
      'send_return_code' => $this->get_return_code_sending(),
      'company' => '',
      'bank_account' => '',
      'show_barcode' => true,
    );

    if ( ! empty($this->omnivalt_settings['api_user']) ) {
      $data['api_user'] = $this->clean($this->omnivalt_settings['api_user']);
    }

    if ( ! empty($this->omnivalt_settings['send_off']) ) {
      $data['pickup_method'] = $this->clean($this->omnivalt_settings['send_off']);
    }

    if ( ! empty($this->omnivalt_settings['label_note']) ) {
      $data['label_comment'] = esc_html($this->omnivalt_settings['label_note']);
      $data['comment_variables'] = $this->omnivalt_configs['text_variables'];
    }

    if ( ! empty($this->omnivalt_settings['company']) ) {
      $data['company'] = $this->clean($this->omnivalt_settings['company']);
    }

    if ( ! empty($this->omnivalt_settings['bank_account']) ) {
      $data['bank_account'] = $this->clean(str_replace(' ', '', $this->omnivalt_settings['bank_account']));
    }

    if ( ! empty($this->omnivalt_settings['manifest_show_barcode']) ) {
      $data['show_barcode'] = ($this->omnivalt_settings['manifest_show_barcode'] === 'yes');
    }

    return (object) $data;
  }

  private function get_packages_data( $order )
  {
    $data = array();

    for ( $i = 0; $i < 1; $i++ ) { // Preparing for multiple packages
      $shipment_size = $this->prepare_package_size($order->shipment->size, $order->units);
      
      $shipment_data = array(
        'id' => $order->id . '-' . ($i + 1),
        'method' => $order->omniva->method,
        'terminal' => $order->omniva->terminal_id ?? '',
        'weight' => $shipment_size['weight'],
        'length' => $shipment_size['length'],
        'width' => $shipment_size['width'],
        'height' => $shipment_size['height'],
        'amount' => $order->payment->total,
      );

      $data[] = (object) $shipment_data;
    }

    return $data;
  }

  private function get_shipments_data( $order )
  {
    $data = array(
      'barcodes' => $order->omniva->barcodes,
      'weight' => $order->shipment->size['weight'],
    );

    return (object) $data;
  }

  private function fill_comment_variables( $comment, $variables, $order )
  {
    foreach ( $variables as $key => $title ) {
      $value = '';
      
      if ( $key === 'order_id' ) $value = $order->id;
      if ( $key === 'order_number' ) $value = $order->number;
      
      $comment = str_replace('{' . $key . '}', $value, $comment);
    }

    return $comment;
  }

  private function prepare_package_size( $shipment_size, $units )
  {
    foreach ( $shipment_size as $size_key => $size_value ) {
      if ( $size_key == 'weight' ) {
        $shipment_size[$size_key] = OmnivaLt_Helper::convert_unit($size_value, 'kg', $units->weight, 'weight');
        if ( empty($shipment_size[$size_key]) ) { // Value cant be zero
          $shipment_size[$size_key] = 1;
        }
      } else {
        $shipment_size[$size_key] = OmnivaLt_Helper::convert_unit($size_value, 'm', $units->dimension, 'dimension');
        if ( empty($shipment_size[$size_key]) ) { // Value cant be zero
          $shipment_size[$size_key] = 0.1;
        }
      }
    }

    return $shipment_size;
  }

  private function get_additional_services( $order, $shipment_service )
  {
    $order_services = OmnivaLt_Helper::get_order_services($order);
    $service_additional_services = $this->get_service_all_additional_services($shipment_service); //Temporary use while this function not exist in API
    //$service_additional_services = Shipment::getAdditionalServicesForShipment($shipment_service); //Function from API
    $additional_services = array();

    foreach ( $this->omnivalt_configs['additional_services'] as $service_key => $service_values ) {
      $add_service = (in_array($service_key, $order_services)) ? true : false;

      if ( ! $add_service && $service_values['add_always'] ) {
        $add_service = true;
      }
      if ( ! in_array($service_values['code'], $service_additional_services) ) {
        $add_service = false;
      }

      if ( $add_service ) {
        $additional_services[$service_key] = $service_values['code'];
      }
    }

    return $additional_services;
  }

  private function get_service_all_additional_services( $shipment_service ) //Temporary while this function not exist in API
  {
    $all_additional_services = $this->omnivalt_configs['additional_services_map'];
    if ( ! isset($all_additional_services[$shipment_service]) ) {
      return array();
    }

    $service_additional_services = array();
    foreach ( $all_additional_services[$shipment_service] as $position => $value ) {
      if ( $value ) {
        $service_additional_services[] = $all_additional_services['map'][$position];
      }
    }

    return $service_additional_services;
  }

  private function check_additional_service_condition( $shipment_service, $additional_service ) //Temporary while this function not exist in API
  {
    $all_conditions = $this->omnivalt_configs['additional_services_conditions'];
    
    if ( ! isset($all_conditions[$shipment_service]) ) {
      return (object) array();
    }
    if ( ! isset($all_conditions[$shipment_service][$additional_service]) ) {
      return (object) array();
    }

    return (object) $all_conditions[$shipment_service][$additional_service];
  }

  private function get_return_code_sending()
  {
    $add_to_sms = true;
    $add_to_email = true;
    
    if ( isset($this->omnivalt_settings['send_return_code']) ) {
      switch ($this->omnivalt_settings['send_return_code']) {
        case 'dont':
          $add_to_sms = false;
          $add_to_email = false;
          break;
        case 'sms':
          $add_to_email = false;
          break;
        case 'email':
          $add_to_sms = false;
          break;
      }
    }

    return (object)array(
      'sms' => $add_to_sms,
      'email' => $add_to_email,
    );
  }

  protected static function get_reference_number( $order_number )
  {
    $order_number = (string) $order_number;
    $kaal = array(7, 3, 1);
    $sl = $st = strlen($order_number);
    $total = 0;
    while ( $sl > 0 and substr($order_number, --$sl, 1) >= '0' ) {
      $total += substr($order_number, ($st - 1) - $sl, 1) * $kaal[($sl % 3)];
    }
    $kontrollnr = ((ceil(($total / 10)) * 10) - $total);
    
    return $order_number . $kontrollnr;
  }

  private function clean( $string ) {
    return str_replace('"', "'", trim($string));
  }
}
