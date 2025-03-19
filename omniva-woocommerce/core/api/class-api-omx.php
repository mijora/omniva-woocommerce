<?php

use \Mijora\Omniva\OmnivaException;
use \Mijora\Omniva\Shipment\Shipment;
use \Mijora\Omniva\Shipment\ShipmentHeader;
use \Mijora\Omniva\Shipment\CallCourier;
use \Mijora\Omniva\Shipment\Package\Package;
use \Mijora\Omniva\Shipment\Package\Address;
use \Mijora\Omniva\Shipment\Package\Contact;
use \Mijora\Omniva\Shipment\Package\AdditionalService;
use \Mijora\Omniva\Shipment\Package\Cod;
use \Mijora\Omniva\Shipment\Package\Measures;
use \Mijora\Omniva\Shipment\Package\ServicePackage;
use \Mijora\Omniva\Shipment\AdditionalService\CodService;
use \Mijora\Omniva\Shipment\AdditionalService\DeliveryToAnAdultService;
use \Mijora\Omniva\Shipment\AdditionalService\DeliveryToSpecificPersonService;
use \Mijora\Omniva\Shipment\AdditionalService\DocumentReturnService;
use \Mijora\Omniva\Shipment\AdditionalService\FragileService;
use \Mijora\Omniva\Shipment\AdditionalService\InsuranceService;
use \Mijora\Omniva\Shipment\AdditionalService\LetterDeliveryToASpecificPersonService;
use \Mijora\Omniva\Shipment\AdditionalService\RegisteredAdviceOfDeliveryService;
use \Mijora\Omniva\Shipment\AdditionalService\SameDayDeliveryService;
use \Mijora\Omniva\Shipment\AdditionalService\SecondDeliveryAttemptOnSaturdayService;
use \Mijora\Omniva\Shipment\AdditionalService\StandardAdviceOfDeliveryService;

class OmnivaLt_Api_Omx extends OmnivaLt_Api_Core
{
  public function __construct()
  {
    parent::__construct();

    if ( ! defined('_OMNIVA_INTEGRATION_AGENT_ID_') ) {
      define('_OMNIVA_INTEGRATION_AGENT_ID_', '7005511 WooCommerce v' . OMNIVALT_VERSION);
    }
  }

  private function get_channels()
  {
    return array(
      'terminal' => (defined(Package::class . '::CHANNEL_PARCEL_MACHINE')) ? Package::CHANNEL_PARCEL_MACHINE : false,
      'courier' => (defined(Package::class . '::CHANNEL_COURIER')) ? Package::CHANNEL_COURIER : false,
      'post' => (defined(Package::class . '::CHANNEL_POST_OFFICE')) ? Package::CHANNEL_POST_OFFICE : false,
    );
  }

  private function get_shipment_types()
  {
    return array(
      'parcel' => (defined(Package::class . '::MAIN_SERVICE_PARCEL')) ? Package::MAIN_SERVICE_PARCEL : false,
      'letter' => (defined(Package::class . '::MAIN_SERVICE_LETTER')) ? Package::MAIN_SERVICE_LETTER : false,
      'pallet' => (defined(Package::class . '::MAIN_SERVICE_PALLET')) ? Package::MAIN_SERVICE_PALLET : false,
    );
  }

  private function get_letter_service_codes()
  {
    return array(
      'document' => (defined(ServicePackage::class . '::CODE_PROCEDURAL_DOCUMENT')) ? ServicePackage::CODE_PROCEDURAL_DOCUMENT : false,
      'letter' => (defined(ServicePackage::class . '::CODE_REGISTERED_LETTER')) ? ServicePackage::CODE_REGISTERED_LETTER : false,
      'maxiletter' => (defined(ServicePackage::class . '::CODE_REGISTERED_MAXILETTER')) ? ServicePackage::CODE_REGISTERED_MAXILETTER : false,
    );
  }
  
  public function get_service_code( ...$args )
  {
    $shipping_method = (isset($args[0])) ? $args[0] : false;

    $channels = $this->get_channels();

    $methods_channels = array(
      'pickup' => $channels['terminal'],
      'courier' => $channels['courier'],
      'courier_plus' => $channels['courier'],
      'private_customer' => $channels['courier'],
      'post_near' => $channels['post'],
      'post_specific' => $channels['post'],
      'letter_courier' => $channels['courier'],
      'letter_post' => $channels['post'],
    );

    $method = OmnivaLt_Method::get_by_key($shipping_method);
    if ( ! $method || ! isset($method['id']) ) {
      return false;
    }

    return (isset($methods_channels[$method['id']])) ? $methods_channels[$method['id']] : false;
  }

  private function get_shipment_type_key( $shipping_method )
  {
    $method = OmnivaLt_Method::get_by_key($shipping_method);
    if ( ! $method || ! isset($method['type']) ) {
      return 'parcel';
    }

    return (isset($method['type'])) ? $method['type'] : 'parcel';
  }

  private function get_shipment_type_code( $type_key )
  {
    $types = $this->get_shipment_types();

    return (isset($types[$type_key])) ? $types[$type_key] : false;
  }

  private function get_letter_service_code( $type_key )
  {
    $codes = $this->get_letter_service_codes();

    return (isset($codes[$type_key])) ? $codes[$type_key] : false;
  }

  public static function get_additional_services()
  {
    return array(
      'cod' => array(
        'title' => __('Cash on delivery', 'omnivalt'),
        'code' => (new CodService())->getServiceCode(),
        'class' => '\Mijora\Omniva\Shipment\AdditionalService\CodService',
        'in_product' => false,
        'in_order' => false,
        'add_always' => false,
      ),
      'persons_over_18' => array(
        'title' => __('Issue to persons at the age of 18+', 'omnivalt'),
        'code' => (new DeliveryToAnAdultService())->getServiceCode(),
        'class' => '\Mijora\Omniva\Shipment\AdditionalService\DeliveryToAnAdultService',
        'in_product' => 'checkbox',
        'in_order' => 'checkbox',
        'add_always' => false,
        'desc_product' => __('If this item will be added to the shipment, the shipment receiver will have to show the document before picking up the shipment', 'omnivalt'),
      ),
      'personal_delivery' => array(
        'title' => __('Personal delivery', 'omnivalt'),
        'code' => (new DeliveryToSpecificPersonService())->getServiceCode(),
        'class' => '\Mijora\Omniva\Shipment\AdditionalService\DeliveryToSpecificPersonService',
        'in_product' => false,
        'in_order' => 'checkbox',
        'add_always' => false,
      ),
      'personal_delivery_letter' => array(
        'title' => __('Personal delivery', 'omnivalt') . ' (' . __('Letter', 'omnivalt') . ')',
        'code' => (new LetterDeliveryToASpecificPersonService())->getServiceCode(),
        'class' => '\Mijora\Omniva\Shipment\AdditionalService\LetterDeliveryToASpecificPersonService',
        'in_product' => false,
        'in_order' => 'checkbox',
        'add_always' => false,
      ),
      'doc_return' => array(
        'title' => __('Document return', 'omnivalt'),
        'code' => (new DocumentReturnService())->getServiceCode(),
        'class' => '\Mijora\Omniva\Shipment\AdditionalService\DocumentReturnService',
        'in_product' => false,
        'in_order' => 'checkbox',
        'add_always' => false,
      ),
      'fragile' => array(
        'title' => __('Fragile', 'omnivalt'),
        'code' => (new FragileService())->getServiceCode(),
        'class' => '\Mijora\Omniva\Shipment\AdditionalService\FragileService',
        'in_product' => 'checkbox',
        'in_order' => 'checkbox',
        'add_always' => false,
        'desc_product' => __('If this item will be added to the shipment, mark that shipment as fragile', 'omnivalt'),
      ),
      'insurance' => array(
        'title' => __('Insurance', 'omnivalt'),
        'code' => (new InsuranceService())->getServiceCode(),
        'class' => '\Mijora\Omniva\Shipment\AdditionalService\InsuranceService',
        'in_product' => false,
        'in_order' => 'checkbox',
        'add_always' => false,
      ),
      'standard_advice_delivery' => array(
        'title' => __('Standard Advice Of Delivery', 'omnivalt'),
        'code' => (new StandardAdviceOfDeliveryService())->getServiceCode(),
        'class' => '\Mijora\Omniva\Shipment\AdditionalService\StandardAdviceOfDeliveryService',
        'in_product' => false,
        'in_order' => 'checkbox',
        'add_always' => false,
      ),
      'registered_advice_delivery' => array(
        'title' => __('Registered Advice Of Delivery', 'omnivalt'),
        'code' => (new RegisteredAdviceOfDeliveryService())->getServiceCode(),
        'class' => '\Mijora\Omniva\Shipment\AdditionalService\RegisteredAdviceOfDeliveryService',
        'in_product' => false,
        'in_order' => 'checkbox',
        'add_always' => false,
      ),
      'same_day_delivery' => array(
        'title' => __('Same day delivery', 'omnivalt'),
        'code' => (new SameDayDeliveryService())->getServiceCode(),
        'class' => '\Mijora\Omniva\Shipment\AdditionalService\SameDayDeliveryService',
        'in_product' => false,
        'in_order' => 'checkbox',
        'add_always' => false,
      ),
      'second_delivery_saturday' => array(
        'title' => __('Second delivery attempt on Saturday', 'omnivalt'),
        'code' => (new SecondDeliveryAttemptOnSaturdayService())->getServiceCode(),
        'class' => '\Mijora\Omniva\Shipment\AdditionalService\SecondDeliveryAttemptOnSaturdayService',
        'in_product' => false,
        'in_order' => 'checkbox',
        'add_always' => false,
      ),
    );
  }

  protected function get_additional_services_for_shipment( $order, $shipment_service )
  {
    $order_services = OmnivaLt_Helper::get_order_services($order);
    $additional_services = array();

    foreach ( $this->get_additional_services() as $service_key => $service_values ) {
      $add_service = (in_array($service_key, $order_services)) ? true : false;

      if ( $service_values['add_always'] ) {
        $add_service = true;
      }

      if ( $add_service ) {
        $additional_services[$service_key] = array(
          'code' => $service_values['code'],
          'class' => $service_values['class']
        );
      }
    }

    return $additional_services;
  }

  public function register_shipment( $id_order )
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
      $send_return_code = ($data_settings->send_return_code->email || $data_settings->send_return_code->sms) ? true : false;

      /* Create shipment */
      $api_shipment = new Shipment();
      $api_shipment->setComment($label_comment);
      $this->set_auth($api_shipment);

      /* Prepare shipment header */
      $api_shipmentHeader = new ShipmentHeader();
      $api_shipmentHeader
        ->setSenderCd($data_settings->api_user)
        ->setFileId(current_time('YmdHms'));
      $api_shipment->setShipmentHeader($api_shipmentHeader);

      /* Prepare packages */
      $packages = array();
      $package_counter = 0;
      foreach ( $data_packages as $data_package ) {
        $shipment_type_key = $this->get_shipment_type_key($data_package->method);
        $shipment_main_service = $this->get_shipment_type_code($shipment_type_key);
        if ( ! $shipment_main_service ) {
          throw new OmnivaException(__('Failed to get shipment service', 'omnivalt'));
        }
        $shipment_delivery_service = $this->get_service_code($data_package->method);
        if ( ! $shipment_delivery_service ) {
          throw new OmnivaException(__('Failed to get delivery service', 'omnivalt'));
        }

        $package_counter++;
        
        $api_package = new Package();
        $api_package
          ->setId($data_package->id)
          ->setService($shipment_main_service, $shipment_delivery_service)
          ->setReturnAllowed($send_return_code);

        /* Set additional services */
        $additional_services = $this->get_additional_services_for_shipment($order, $shipment_main_service);
        
        $use_consolidation = (isset($additional_services['cod']) || isset($additional_services['doc_return'])) ? true : false;
        if ( ! $use_consolidation && count($data_packages) > 1 ) {
          $api_package->setId($data_package->id . '_' . $package_counter);
        }

        foreach ( $additional_services as $additional_service_key => $additional_service_code ) {
          $api_additional_service = new $additional_service_code['class']();

          if ( $package_counter > 1 && $use_consolidation && $additional_service_key != 'fragile' ) {
            continue;
          }

          /* Add additional service data */
          if ( $additional_service_key == 'cod' ) {
            $api_additional_service
              ->setCodAmount($data_package->amount)
              ->setCodIban($data_settings->bank_account)
              ->setCodReference($api_additional_service::calculateReferenceNumber($order->id))
              ->setCodReceiver($data_settings->company);
          }
          if ( $additional_service_key == 'insurance' ) {
            $api_additional_service
              ->setInsuranceValue($order->payment->subtotal);
          }
          $api_package->setAdditionalServiceOmx($api_additional_service);
        }

        /* Set measures */
        if ( $shipment_type_key != 'letter' ) {
          $api_measures = new Measures();
          $api_measures
            ->setWeight($data_package->weight)
            ->setLength($data_package->length)
            ->setHeight($data_package->height)
            ->setWidth($data_package->width);
          $api_package->setMeasures($api_measures);
        }

        /* Set receiver */
        $api_receiver_address = new Address();
        $api_receiver_address
          ->setCountry($data_client->country)
          ->setPostcode($data_client->postcode)
          ->setDeliverypoint($data_client->city)
          ->setStreet($data_client->street);
        if ( OmnivaLt_Method::is_omniva_domestic_terminal($data_package->method) ) {
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
          ->setPhone($data_shop->phone)
          ->setMobile($data_shop->mobile)
          ->setPersonName($data_shop->name);
        $api_package->setSenderContact($api_sender_contact);

        if ( $shipment_type_key == 'letter' ) {
          $letter_service_code = $this->get_letter_service_code('letter'); //Always use "Local registered standard letter"
          $api_service_package = new ServicePackage($letter_service_code);
          $api_package->setServicePackage($api_service_package);
        }

        $packages[] = $api_package;
      }
      if ( empty($packages) ) {
        throw new OmnivaException(__('Failed to get packages', 'omnivalt'));
      }
      $api_shipment->setPackages($packages);
      OmnivaLt_Debug::debug_request($api_shipment, 'json');

      /* Register shipment */
      $result = $api_shipment->registerShipment(false);
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

  public function call_courier( $params )
  {
    $shop = $this->get_shop_data();
    $pickStart = OmnivaLt_Helper::get_formated_time($shop->pick_from, '8:00');
    $pickFinish = OmnivaLt_Helper::get_formated_time($shop->pick_until, '17:00');
    $parcels_number = ($params['quantity'] > 0) ? $params['quantity'] : 1;

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
      $this->set_auth($api_call);

      $api_call
        ->setSender($api_sender)
        ->setEarliestPickupTime($pickStart)
        ->setLatestPickupTime($pickFinish)
        ->setTimezone('Europe/Tallinn')
        ->setComment($shop->courier_comment)
        ->setIsHeavyPackage($params['heavy'])
        ->setIsTwoManPickup($params['twoman'])
        ->setParcelsNumber($parcels_number);

      $debug_request = $api_call->getCallCourierOmxRequest();

      $result = $api_call->callCourier();

      if ( $result ) {
        $result_data = $api_call->getResponseBody();
        return array(
          'status' => true,
          'call_id' => $result_data['courierOrderNumber'],
          'start_time' => get_date_from_gmt($result_data['startTime'], 'Y-m-d H:i:s'),
          'end_time' => get_date_from_gmt($result_data['endTime'], 'Y-m-d H:i:s'),
          'debug' => array(
            'request' => json_encode($debug_request),
            'response' => json_encode($result_data),
          ),
        );
      }
    } catch (OmnivaException $e) {
      return array('status' => false, 'msg' => $e->getMessage());
    }

    return array('status' => false, 'msg' => __('Failed to call courier', 'omnivalt'));
  }

  public function cancel_courier_call( $call_id )
  {
    try {
      $api_call = new CallCourier();
      $this->set_auth($api_call);

      $result = $api_call->cancelCourierOmx($call_id);

      if ( $result ) {
        return array(
          'status' => true,
          'call_id' => $call_id,
        );
      }
    } catch (OmnivaException $e) {
      return array('status' => false, 'call_id' => $call_id, 'msg' => $e->getMessage());
    }

    return array('status' => false, 'call_id' => $call_id, 'msg' => __('Failed to cancel courier', 'omnivalt'));
  }
}
