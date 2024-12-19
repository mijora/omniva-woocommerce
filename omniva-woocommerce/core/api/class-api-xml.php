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

class OmnivaLt_Api_Xml extends OmnivaLt_Api_Core
{
    protected function set_auth( $object )
    {
        if( method_exists($object, 'setAuth') ) {
            $settings = $this->get_settings();
            $object->setAuth(
                $this->clean($settings['api_user']),
                $this->clean($settings['api_pass']),
                $this->clean($this->clear_api_url($settings['api_url'])),
                OmnivaLt_Debug::check_debug_enabled()
            );
        }
    }

    /**
     * Gets the service code based on provided parameters.
     *
     * @param mixed ...$args The arguments for the service code. Expected in the following order:
     *                       - $args[0] (sender_country): The country of the sender (string).
     *                       - $args[1] (receiver_country): The country of the receiver (string).
     *                       - $args[2] (shipping_type): The type of shipping, expected format: "pt c" (string).
     * @throws Exception Error if the service code could not be received.
     * @return string Service code
     */
    public function get_service_code( ...$args )
    {
        $sender_country = (isset($args[0])) ? $args[0] : false;
        $receiver_country = (isset($args[1])) ? $args[1] : false;
        $shipping_type = (isset($args[2])) ? $args[2] : false;
        if ( $sender_country === false || $receiver_country === false || $shipping_type === false ) {
            throw new \Exception(__('Missing required parameter', 'omnivalt'));
        }

        $shipping_set = OmnivaLt_Helper::get_shipping_set($sender_country, $receiver_country);
        if ( ! $shipping_set ) {
            throw new \Exception(__('Failed to get service set', 'omnivalt'));
        }
        
        $services = self::get_services_by_shipping_set($shipping_set);
        if ( empty($services) ) {
            throw new \Exception(__('Failed to get available services list', 'omnivalt'));
        }

        if ( ! isset($services[$shipping_type]) ) {
            throw new \Exception($this->build_not_found_type_error_msg($shipping_type, $sender_country, $receiver_country));
        }

        return $services[$shipping_type];
    }

    /**
     * Retrieves the shipping services for a specified shipping set.
     *
     * @param string|false $shipping_set The name of the shipping set to retrieve services for. 
     *                                   If not provided (or set to `false`), all shipping sets are returned.
     *
     * @return array An associative array of shipping services for the specified shipping set. 
     *               If the shipping set does not exist, an empty array is returned. 
     *               If no shipping set is provided, all shipping sets are returned.
     */
    public static function get_services_by_shipping_set( $shipping_set = false )
    {
        $shipping_sets = array(
            'baltic' => array(
                'pt pt' => 'PA',
                'pt c' => 'PK',
                'c pt' => 'PU',
                'c c' => 'QH',
                'c pn' => 'DD',
                'c ps' => 'DE',
                'courier_call' => 'QH',
            ),
            'estonia' => array(
                'pt pt' => 'PA',
                'pt pn' => 'PO',
                'pt c' => 'PK',
                'c pt' => 'PU',
                'c c' => 'CI',
                'c cp' => 'LX', //not sure
                'c pn' => 'DD',
                'c ps' => 'DE',
                'po cp' => 'LH',
                'po pt' => 'PV',
                'po pn' => 'CD',
                'po c' => 'CE',
                'lg pt' => 'PP',
                'courier_call' => 'CI',
            ),
            'finland' => array(
                'pt pt' => 'CD', //Matkahulto
                'c pt' => 'CD', //Matkahulto
                'c pc' => 'QB', //QB in documentation
                'c pn' => 'CD', //not sure
                'c cp' => 'CE', //not sure
                'po pt' => 'CD', //Matkahulto
                'lg pt' => 'CD', //Matkahulto
                'courier_call' => 'CE',
            ),
        );

        if ( ! $shipping_set ) {
            return $shipping_sets;
        }

        return (isset($shipping_sets[$shipping_set])) ? $shipping_sets[$shipping_set] : array();
    }

    private function build_not_found_type_error_msg( $shipping_type, $sender_country, $receiver_country )
    {
        $exploded_shipping_type = $this->explode_shipping_type($shipping_type);

        return sprintf(
            __('Shipping from %1$s (%2$s) to %3$s (%4$s) is not available', 'omnivalt'),
            ($exploded_shipping_type['send']) ? OmnivaLt_Method::get_title($exploded_shipping_type['send']) : '-',
            OmnivaLt_Wc::get_country_name($sender_country),
            ($exploded_shipping_type['receive']) ? OmnivaLt_Method::get_title($exploded_shipping_type['receive']) : '-',
            OmnivaLt_Wc::get_country_name($receiver_country)
        );
    }

    private function explode_shipping_type( $shipping_type )
    {
        $shipping_types = array(
            'send' => false,
            'receive' => false,
        );

        if ( strpos($shipping_type, ' ') === false ) {
            return $shipping_types;
        }

        $exploded = explode(' ', $shipping_type);

        if ( ! empty($exploded[0]) ) {
            $shipping_types['send'] = $exploded[0];
        }
        if ( ! empty($exploded[1]) ) {
            $shipping_types['receive'] = $exploded[1];
        }

        return $shipping_types;
    }

    public static function get_additional_services()
    {
        return array(
            'arrival_sms' => array(
                'title' => __('Arrival SMS', 'omnivalt'),
                'code' => 'ST',
                'in_product' => false,
                'in_order' => false,
                'add_always' => true,
                'required_fields' => array('receiver_phone'),
            ),
            'arrival_email' => array(
                'title' => __('Arrival email', 'omnivalt'),
                'code' => 'SF',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
                'required_fields' => array('receiver_email'),
            ),
            'cod' => array(
                'title' => __('Cash on delivery', 'omnivalt'),
                'code' => 'BP',
                'in_product' => false,
                'in_order' => false,
                'add_always' => false,
            ),
            'fragile' => array(
                'title' => __('Fragile', 'omnivalt'),
                'code' => 'BC',
                'in_product' => 'checkbox',
                'in_order' => 'checkbox',
                'add_always' => false,
                'desc_product' => __('If this item will be added to the shipment, mark that shipment as fragile', 'omnivalt'),
            ),
            'private_customer' => array(
                'title' => __('Delivery to private customer', 'omnivalt'),
                'code' => 'CL',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
            ),
            'doc_return' => array(
                'title' => __('Document return', 'omnivalt'),
                'code' => 'XT',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
            ),
            'paid_by_receiver' => array(
                'title' => __('Paid by receiver', 'omnivalt'),
                'code' => 'BS',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
            ),
            'insurance' => array(
                'title' => __('Insurance', 'omnivalt'),
                'code' => 'BI',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
            ),
            'personal_delivery' => array(
                'title' => __('Personal delivery', 'omnivalt'),
                'code' => 'BK',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
            ),
            'paid_parcel_sms' => array(
                'title' => __('Paid parcel SMS', 'omnivalt'),
                'code' => 'GN',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
            ),
            'paid_parcel_email' => array(
                'title' => __('Paid parcel email', 'omnivalt'),
                'code' => 'GM',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
            ),
            'return_notification_sms' => array(
                'title' => __('Return notification SMS', 'omnivalt'),
                'code' => 'SB',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
            ),
            'return_notification_email' => array(
                'title' => __('Return notification email', 'omnivalt'),
                'code' => 'SG',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
            ),
            'persons_over_18' => array(
                'title' => __('Issue to persons at the age of 18+', 'omnivalt'),
                'code' => 'PC',
                'in_product' => 'checkbox',
                'in_order' => 'checkbox',
                'add_always' => false,
                'desc_product' => __('If this item will be added to the shipment, the shipment receiver will have to show the document before picking up the shipment', 'omnivalt'),
            ),
            'delivery_confirmation_sms' => array(
                'title' => __('Delivery confirmation SMS to sender', 'omnivalt'),
                'code' => 'SS',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
                'required_fields' => array('sender_phone'),
            ),
            'delivery_confirmation_email' => array(
                'title' => __('Delivery confirmation e-mail to sender', 'omnivalt'),
                'code' => 'SE',
                'in_product' => false,
                'in_order' => 'checkbox',
                'add_always' => false,
                'required_fields' => array('sender_email'),
            ),
        );
    }

    protected function get_additional_services_for_shipment( $order, $shipment_service )
    {
        $order_services = OmnivaLt_Helper::get_order_services($order);
        $service_additional_services = Shipment::getAdditionalServicesForShipment($shipment_service);
        $additional_services = array();

        foreach ( $this->get_additional_services() as $service_key => $service_values ) {
            $add_service = (in_array($service_key, $order_services)) ? true : false;

            if ( $service_values['add_always'] ) {
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
            foreach ( $data_packages as $data_package ) {
                /* Create package */
                $shipment_service = $this->get_service_code($data_shop->country, $data_client->country, $data_settings->pickup_method . ' ' . $data_package->method);
                if ( ! is_string($shipment_service) ) {
                    throw new OmnivaException(__('Failed to get shipment service', 'omnivalt'));
                }

                $api_package = new Package();
                $api_package
                    ->setId($data_package->id)
                    ->setService($shipment_service);

                /* Set additional services */
                $additional_services = $this->get_additional_services_for_shipment($order, $shipment_service);
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
                ->setMobile($shop->mobile)
                ->setPhone($shop->phone)
                ->setPersonName($shop->name);

            $api_call = new CallCourier();
            $this->set_auth($api_call);
            $api_call
                ->setSender($api_sender)
                ->setEarliestPickupTime($pickStart)
                ->setLatestPickupTime($pickFinish)
                ->setDestinationCountry(OmnivaLt_Helper::get_shipping_set($shop->api_country, 'call'))
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

    private function clear_api_url( $api_url )
    {
        $api_url = esc_url(preg_replace('{/$}', '', $api_url));
        $url_path = '/epmx/services/messagesService.wsdl';
        if ( ! str_contains($api_url, $url_path) ) {
            //$api_url .= $url_path; // Disabled because the API library puts it on itself
        }

        return $api_url;
    }
}
