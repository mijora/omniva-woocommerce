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
use \Mijora\Omniva\Shipment\AdditionalService\InsuranceService;

class OmnivaLt_Api_Omx extends OmnivaLt_Api_Core
{
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

      /* Create shipment */
      $api_shipment = new Shipment();
      $api_shipment
        ->setComment($label_comment)
        ->setShowReturnCodeEmail($data_settings->send_return_code->email)
        ->setShowReturnCodeSms($data_settings->send_return_code->sms);
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
        /* Create package */
        $shipment_service = OmnivaLt_Helper::get_shipping_service_code($data_shop->country, $data_client->country, $data_settings->pickup_method . ' ' . $data_package->method);
        if ( ! is_string($shipment_service) ) {
          if ( isset($shipment_service['msg']) ) {
            throw new OmnivaException($shipment_service['msg']);
          }
          throw new OmnivaException(__('Failed to get shipment service', 'omnivalt'));
        }

        $package_counter++;
        
        $api_package = new Package();
        $api_package
          ->setId($data_package->id)
          ->setService($shipment_service);

        /* Set additional services */
        $additional_services = $this->get_additional_services($order, $shipment_service);
        
        $use_consolidation = (isset($additional_services['cod']) || isset($additional_services['doc_return'])) ? true : false;
        if ( ! $use_consolidation && count($data_packages) > 1 ) {
          $api_package->setId($data_package->id . '_' . $package_counter);
        }

        $all_api_additional_services = array();
        foreach ( $additional_services as $additional_service_key => $additional_service_code ) {
          $service_conditions = Shipment::getAdditionalServiceConditionsForShipment($shipment_service, $additional_service_code);
          if ( ! empty($service_conditions) ) {
            if ( isset($service_conditions->only_countries) && ! in_array($data_client->country, $service_conditions->only_countries) ) {
              continue;
            }
          }
          $api_additional_service = new AdditionalService();
          $api_additional_service
            ->setServiceCode($additional_service_code);
          if ( $package_counter > 1 && $use_consolidation && $additional_service_key != 'fragile' ) {
            continue;
          }
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
          if ( $additional_service_key == 'insurance' ) {
            $api_insurance = new InsuranceService();
            $api_insurance->setInsuranceValue(55);
            $api_package->setAdditionalServiceOmx($api_insurance);
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
          ->setPhone($data_shop->phone)
          ->setMobile($data_shop->mobile)
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
          'start_time' => date('Y-m-d H:i:s', strtotime($result_data['startTime'])),
          'end_time' => date('Y-m-d H:i:s', strtotime($result_data['endTime'])),
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
