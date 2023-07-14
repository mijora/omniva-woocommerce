<?php

use \Mijora\Omniva\OmnivaException;
use \Mijora\Omniva\Shipment\CallCourier;
use \Mijora\Omniva\Shipment\Package\Address;
use \Mijora\Omniva\Shipment\Package\Contact;

class OmnivaLt_Api
{
  private $omnivalt_settings;
  private $omnivalt_configs;
  private $api_url;

  public function __construct()
  {
    $this->omnivalt_configs = OmnivaLt_Core::get_configs();
    $this->omnivalt_settings = get_option($this->omnivalt_configs['plugin']['settings_key']);
    $this->api_url = $this->clean(preg_replace('{/$}', '', $this->omnivalt_settings['api_url'])) . '/epmx/services/messagesService.wsdl';
  }

  public function get_tracking_number( $id_order )
  {
    $order = OmnivaLt_Wc_Order::get_data($id_order);
    if ( ! $order ) {
      return array('msg' => __('Failed to get WooCommerce order data', 'omnivalt'));
    }

    $client = $this->get_client_data($order);
    $shop = $this->get_shop_data();
  
    $shipment_size = $order->shipment->size;
    foreach ( $shipment_size as $size_key => $size_value ) {
      if ( $size_key == 'weight' ) {
        $shipment_size[$size_key] = OmnivaLt_Helper::convert_unit($size_value, 'kg', $order->units->weight, 'weight');
        if ( empty($shipment_size[$size_key]) ) { // Value cant be zero
          $shipment_size[$size_key] = 1;
        }
      } else {
        $shipment_size[$size_key] = OmnivaLt_Helper::convert_unit($size_value, 'm', $order->units->dimension, 'dimension');
        if ( empty($shipment_size[$size_key]) ) { // Value cant be zero
          $shipment_size[$size_key] = 0.1;
        }
      }
    }
   
    if ( ! isset($this->omnivalt_configs['shipping_params'][$client->country]) ) {
      return array('msg' => __('Shipping parameters for customer country not found', 'omnivalt'));
    }
    $shipping_params = $this->omnivalt_configs['shipping_params'][$client->country];

    $send_method = $order->omniva->method;
    $pickup_method = $this->omnivalt_settings['send_off'];
    $is_cod = OmnivaLt_Helper::is_cod_payment($order->payment->method);

    $service = OmnivaLt_Helper::get_shipping_service_code($shop->country, $client->country, $pickup_method . ' ' . $send_method);
    if ( isset($service['status']) && $service['status'] === 'error' ) {
      return array('msg' => $service['msg']);
    }

    $other_services = OmnivaLt_Helper::get_order_services($order);
    $additional_services = '';

    $client_fullname = $client->name . ' ' . $client->surname;
    if ( empty(preg_replace('/\s+/', '', $client_fullname)) ) {
      $client_fullname = $client->company;
    }

    $client_mobiles = '';
    $client_emails = '';
    $sender_mobiles = '';
    $sender_emails = '';

    foreach ( $this->omnivalt_configs['additional_services'] as $service_key => $service_values ) {
      $add_service = (in_array($service_key, $other_services)) ? true : false;
      if ( ! $add_service && $service_values['add_always'] ) {
        $add_service = true;
      }
      if ( is_array($service_values['only_for']) && ! in_array($service, $service_values['only_for']) ) {
        $add_service = false;
      }

      if ( $add_service ) {
        $additional_services .= '<option code="' . $service_values['code'] . '" />';
        if ( ! empty($service_values['required_fields']) ) {
          foreach ( $service_values['required_fields'] as $req_field ) {
            if ( $req_field === 'receiver_phone' && ! empty($client->phone) ) {
              $client_mobiles = $this->get_required_field('mobile', $client->phone, $client_mobiles);
            }
            if ( $req_field === 'receiver_email' && ! empty($client->email) ) {
              $client_emails = $this->get_required_field('email', $client->email, $client_emails);
            }
            if ( $req_field === 'sender_phone' && ! empty($shop->phone) ) {
              $sender_mobiles = $this->get_required_field('mobile', $shop->phone, $sender_mobiles);
            }
            if ( $req_field === 'sender_email' && ! empty($shop->email) ) {
              $sender_emails = $this->get_required_field('email', $shop->email, $sender_emails);
            }
          }
        }
      }
    }
    if ( $additional_services ) {
      $additional_services = '<add_service>' . $additional_services . '</add_service>';
    }

    $parcel_terminal = "";
    if ( OmnivaLt_Configs::get_method_terminals_type($send_method) ) {
      $parcel_terminal = 'offloadPostcode="' . $order->omniva->terminal_id . '" ';
    }

    $send_return_code = $this->get_return_code_sending();
    $return_code_sms = (! $send_return_code->sms) ? '<show_return_code_sms>false</show_return_code_sms>' : '';
    $return_code_email = (! $send_return_code->email) ? '<show_return_code_email>false</show_return_code_email>' : '';

    $client_address = '<address postcode="' . $client->postcode . '" ' . $parcel_terminal . ' deliverypoint="' . $client->city . '" country="' . $client->country . '" street="' . $client->address_1 . '" />';

    $label_comment = '';
    if ( ! empty($this->omnivalt_settings['label_note']) ) {
      $prepare_comment = esc_html($this->omnivalt_settings['label_note']);
      foreach ( $this->omnivalt_configs['text_variables'] as $key => $title ) {
        $value = '';
        
        if ( $key === 'order_id' ) $value = $order->id;
        if ( $key === 'order_number' ) $value = $order->number;
        
        $prepare_comment = str_replace('{' . $key . '}', $value, $prepare_comment);
      }
      $label_comment = '<comment>' . $prepare_comment . '</comment>';
    }

    $sender_phone = '';
    if ( ! empty($shop->phone) ) {
        $sender_phone = '<phone>' . $shop->phone . '</phone>';
    }

    $xmlRequest = $this->xml_header();
    $xmlRequest .= '<item service="' . $service . '" >
      ' . $additional_services . '
      <measures weight="' . $shipment_size['weight'] . '" length="' . $shipment_size['length'] . '" width="' . $shipment_size['width'] . '" height="' . $shipment_size['height'] . '" />
      ' . $this->cod($order->id, $is_cod, $order->payment->total) . '
      ' . $label_comment . $return_code_sms . $return_code_email . '
      <receiverAddressee>
        <person_name>' . $client_fullname . '</person_name>
        ' . $client_mobiles . $client_emails . $client_address . '
      </receiverAddressee>
      <returnAddressee>
        <person_name>' . $shop->name . '</person_name>
        ' . $sender_phone . $sender_mobiles . $sender_emails . '
        <address postcode="' . $shop->postcode . '" deliverypoint="' . $shop->city . '" country="' . $shop->country . '" street="' . $shop->street . '" />
      </returnAddressee>
    </item>';
    $xmlRequest .= $this->xml_footer();

    return $this->api_request($xmlRequest);
  }

  public function get_shipment_labels( $barcodes )
  {
    $errors = array();
    $barcodeXML = '';
    foreach ( $barcodes as $barcode ) {
      $barcodeXML .= '<barcode>' . $barcode . '</barcode>';
    }

    $xmlRequest = '
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
      xmlns:xsd="http://service.core.epmx.application.eestipost.ee/xsd">
       <soapenv:Header/>
       <soapenv:Body>
          <xsd:addrcardMsgRequest>
             <partner>' . $this->clean($this->omnivalt_settings['api_user']) . '</partner>
             <sendAddressCardTo>response</sendAddressCardTo>
             <barcodes>
                ' . $barcodeXML . '
             </barcodes>
          </xsd:addrcardMsgRequest>
       </soapenv:Body>
    </soapenv:Envelope>';

    OmnivaLt_Debug::debug_request($xmlRequest);
    try {
      $headers = array(
        "Content-type: text/xml;charset=\"utf-8\"",
        "Accept: text/xml",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        "Content-length: " . strlen($xmlRequest),
      );
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->api_url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_USERPWD, $this->clean($this->omnivalt_settings['api_user']) . ":" . $this->clean($this->omnivalt_settings['api_pass']));
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      $xmlResponse = curl_exec($ch);
      OmnivaLt_Debug::debug_response($xmlResponse);
    } catch (Exception $e) {
      $errors[] = $e->getMessage() . ' ' . $e->getCode();
      $xmlResponse = '';
    }

    $xml = $this->makeReadableXmlResponse($xmlResponse);
    if ( ! is_object($xml) ) {
      $errors[] = $this->get_xml_error_from_response($xmlResponse);
    }

    $shippingLabelContent = '';
    if ( is_object($xml) && is_object($xml->Body->addrcardMsgResponse->successAddressCards->addressCardData->barcode) ) {
      $shippingLabelContent = (string) $xml->Body->addrcardMsgResponse->successAddressCards->addressCardData->fileData;
    } else {
      $errors[] = 'No label received from webservice';
    }

    if ( empty($barcodes) && empty($errors) ) {
      $errors[] = __('No saved barcodes received', 'omnivalt');
    }

    if ( ! empty($barcodes) && empty($errors) ) {
      return array(
        'status' => true,
        'file' => $shippingLabelContent,
      );
    }

    return array(
      'status' => false,
      'msg' => implode('. ', $errors)
    );
  }

  public function call_courier( $parcels_number = 0 )
  {
    $is_cod = false;
    $parcel_terminal = "";
    $shop = $this->get_shop_data();
    $pickStart = OmnivaLt_Helper::get_formated_time($shop->pick_from, '8:00');
    $pickFinish = OmnivaLt_Helper::get_formated_time($shop->pick_until, '17:00');
    $parcels_number = ($parcels_number > 0) ? $parcels_number : 1;

    $address = new Address();
    $address
      ->setCountry($shop->country)
      ->setPostcode($shop->postcode)
      ->setDeliverypoint($shop->city)
      ->setStreet($shop->street);
    $sender = new Contact();
    $sender
      ->setAddress($address)
      ->setMobile($shop->phone)
      ->setPersonName($shop->name);

    $call = new CallCourier();
    $this->setAuth($call);
    $call
      ->setSender($sender)
      ->setEarliestPickupTime($pickStart)
      ->setLatestPickupTime($pickFinish)
      ->setDestinationCountry(OmnivaLt_Helper::get_shipping_service($shop->api_country, 'call'))
      ->setParcelsNumber($parcels_number);

    try {
      $call->callCourier();
      $debug_data = $call->getDebugData();
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

  private function xml_header()
  {
    return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://service.core.epmx.application.eestipost.ee/xsd">
        <soapenv:Header/>
        <soapenv:Body>
          <xsd:businessToClientMsgRequest>
            <partner>' . $this->clean($this->omnivalt_settings['api_user']) . '</partner>
            <interchange msg_type="info11">
              <header file_id="' . current_time('YmdHms') . '" sender_cd="' . $this->clean($this->omnivalt_settings['api_user']) . '" >                
              </header>
              <item_list>';
  }

  private function xml_footer()
  {
    return '  </item_list>
            </interchange>
          </xsd:businessToClientMsgRequest>
        </soapenv:Body>
      </soapenv:Envelope>';
  }

  private function get_shipping_service( $shipping_params, $pickup_method, $send_method )
  {
    $method = $pickup_method . ' ' . $send_method;
    $matches = $shipping_params['services'];

    return ( isset($matches[$method]) ) ? $matches[$method] : '';
  }

  private function get_required_field( $type, $value, $current_text = false ) {
    $add_text = '';
    $value = trim($value);
    
    if ( $type === 'mobile' ) {
      $phone = preg_replace("/[^0-9\+]/", "", $value);
      $add_text = '<mobile>' . $phone . '</mobile>';
    }
    if ( $type === 'email' ) {
      $add_text = '<email>' . $value . '</email>';
    }

    if ( $add_text === '' || $current_text === false ) {
      return $add_text;
    }

    if ( strpos($current_text, $add_text) === false ) {
      return $current_text . $add_text;
    }

    return $current_text;
  }

  private function cod( $order_id, $cod = 0, $amount = 0 )
  {
    $company = $this->omnivalt_settings['company'];
    $bank_account = $this->omnivalt_settings['bank_account'];
    if ( $cod ) {
      return '<monetary_values>
        <cod_receiver>' . $company . '</cod_receiver>
        <values code="item_value" amount="' . $amount . '"/>
      </monetary_values>
      <account>' . $bank_account . '</account>
      <reference_number>' . $this->getReferenceNumber($order_id) . '</reference_number>';
    }
    
    return '';
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
      'address_1' => $this->clean($order->shipping->address_1),
      'postcode' => $this->clean($order->shipping->postcode),
      'city' => $this->clean($order->shipping->city),
      'country' => $this->clean($order->shipping->country),
      'email' => $this->clean($order->shipping->email),
      'phone' => $this->clean($order->shipping->phone),
    );

    if ( empty($data['postcode']) && empty($data['city']) && empty($data['address_1']) && empty($data['country']) ) {
      $data['postcode'] = $this->clean($order->billing->postcode);
      $data['city'] = $this->clean($order->billing->city);
      $data['address_1'] = $this->clean($order->billing->address_1);
      $data['country'] = $this->clean($order->billing->country);
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

  private function api_request( $request )
  {
    OmnivaLt_Debug::debug_request($request);
    $barcodes = array();
    $errors = array();
    $headers = array(
      "Content-type: text/xml;charset=\"utf-8\"",
      "Accept: text/xml",
      "Cache-Control: no-cache",
      "Pragma: no-cache",
      "Content-length: " . strlen($request),
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->api_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERPWD, $this->clean($this->omnivalt_settings['api_user']) . ":" . $this->clean($this->omnivalt_settings['api_pass']));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $xmlResponse = curl_exec($ch);
    $debug_response = OmnivaLt_Debug::debug_response($xmlResponse);

    if ( $xmlResponse === false ) {
      $errors[] = curl_error($ch);
    } else {
      $errorTitle = '';
      if ( strlen(trim($xmlResponse)) > 0 ) {
        $xml = $this->makeReadableXmlResponse($xmlResponse);
        if ( ! is_object($xml) ) {
          $errors[] = $this->get_xml_error_from_response($xmlResponse);
        }

        if ( is_object($xml) && is_object($xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo) ) {
          foreach ($xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo as $data) {
            $errors[] = $data->clientItemId . ' - ' . $data->barcode . ' - ' . $data->message;
          }
          if ( is_object($xml->Body->businessToClientMsgResponse->prompt)
            && strpos($xml->Body->businessToClientMsgResponse->prompt, 'AppException:') !== false ) {
            $errors[] = str_replace('AppException: ', '', $xml->Body->businessToClientMsgResponse->prompt);
          }
        }

        if ( empty($errors) ) {
          if ( is_object($xml) && is_object($xml->Body->businessToClientMsgResponse->savedPacketInfo->barcodeInfo) ) {
            foreach ($xml->Body->businessToClientMsgResponse->savedPacketInfo->barcodeInfo as $data) {
              $barcodes[] = (string) $data->barcode;
            }
          } elseif ( is_object($xml->Body->businessToClientMsgResponse->prompt) ) {
            $errors[] = __('API error', 'omnivalt') . ' - '. $xml->Body->businessToClientMsgResponse->prompt;
          }
        }
      }
    }

    if ( ! empty($errors) ) {
      return array(
        'status' => false,
        'msg' => implode('. ', $errors),
        'debug' => $debug_response
      );
    } else {
      if ( ! empty($barcodes) ) return array(
        'status' => true,
        'barcodes' => $barcodes,
        'debug' => $debug_response
      );
      $errors[] = __('No saved barcodes received', 'omnivalt');
      return array(
        'status' => false,
        'msg' => implode('. ', $errors),
        'debug' => $debug_response
      );
    }
  }

  protected static function getReferenceNumber( $order_number )
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

  private function makeReadableXmlResponse( $xmlResponse )
  {
    $xmlResponse = str_ireplace(['SOAP-ENV:', 'SOAP:', 'ns3:'], '', $xmlResponse);
    $xml = simplexml_load_string($xmlResponse);

    // Another possible preparation variant
    /*$xmlWithNamespaces = simplexml_load_string($xmlResponse);
    $xml = str_replace(array_map(function($e) { return "$e:"; }, array_keys($xmlWithNamespaces->getNamespaces(true))), array(), $xmlResponse);*/

    return $xml;
  }

  private function get_xml_error_from_response( $response )
  {
    if ( strpos($response, 'HTTP Status 401') !== false
      && strpos($response, 'This request requires HTTP authentication.') !== false ) {
      return __('Bad API logins', 'omnivalt');
    }
    
    return __('Response is in the wrong format', 'omnivalt');
  }

  private function clean( $string ) {
    return str_replace('"',"'",$string);
  }
}
