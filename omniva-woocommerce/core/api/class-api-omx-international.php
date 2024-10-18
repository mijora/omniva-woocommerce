<?php

use \Mijora\Omniva\OmnivaException;
use \Mijora\Omniva\Shipment\Shipment;
use \Mijora\Omniva\Shipment\ShipmentHeader;
use \Mijora\Omniva\Shipment\Package\Package;
use \Mijora\Omniva\Shipment\Package\Address;
use \Mijora\Omniva\Shipment\Package\Contact;
use \Mijora\Omniva\Shipment\Package\ServicePackage;
use \Mijora\Omniva\Shipment\Package\Measures;
use \Mijora\Omniva\Shipment\Package\Notification;
use \Mijora\Omniva\ServicePackageHelper\ServicePackageHelper;

class OmnivaLt_Api_Omx_International extends OmnivaLt_Api_Omx
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
        $package_counter++;

        /* Get package key and zone */
        $method_exploded = explode('_', $data_package->method);
        $method_package = $method_exploded[0];
        $method_zone = $method_exploded[1];

        /* Set package service */
        $api_service_package = new ServicePackage(ServicePackageHelper::getServicePackageCode($method_package));

        /* Set package measurements */
        $api_measures = new Measures();
        $api_measures
          ->setWeight($data_package->weight)
          ->setLength($data_package->length)
          ->setHeight($data_package->height)
          ->setWidth($data_package->width);

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

        /* Set receiver */
        $api_receiver_address = new Address();
        $api_receiver_address
          ->setCountry($data_client->country)
          ->setPostcode($data_client->postcode)
          ->setDeliverypoint($data_client->city)
          ->setStreet($data_client->street);
        $api_receiver_contact = new Contact();
        $api_receiver_contact
          ->setAddress($api_receiver_address)
          ->setEmail($data_client->email)
          ->setMobile($data_client->phone)
          ->setPersonName($this->get_client_fullname($data_client));

        /* Set sender notifications */
        $api_notification = new Notification();
        $api_notification
          ->setChannel(Notification::CHANNEL_EMAIL) //Make variable when used
          ->setType(Notification::TYPE_REGISTERED); //Make variable when used
        
        /* Create package */
        $api_package = new Package();
        $api_package
          ->setId($data_package->id . '_' . $package_counter)
          //->setComment() //Not using
          ->setService(Package::MAIN_SERVICE_PARCEL, Package::CHANNEL_COURIER)
          //->setNotification($api_notification) //Not using
          ->setMeasures($api_measures)
          ->setReceiverContact($api_receiver_contact)
          ->setSenderContact($api_sender_contact)
          ->setReturnAllowed($send_return_code)
          ->setServicePackage($api_service_package);

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
}
