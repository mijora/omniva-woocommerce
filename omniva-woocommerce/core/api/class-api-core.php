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
use \Mijora\Omniva\PowerBi\OmnivaPowerBi;

class OmnivaLt_Api_Core
{
    private $omnivalt_settings;
    private $omnivalt_configs;
    private $need_convert = false;

    public function __construct()
    {
        $this->omnivalt_configs = OmnivaLt_Core::get_configs();
        $this->omnivalt_settings = get_option($this->omnivalt_configs['plugin']['settings_key']);
    }

    protected function get_settings()
    {
        return $this->omnivalt_settings;
    }

    protected function get_configs()
    {
        return $this->omnivalt_configs;
    }

    public function set_need_convert( $need_convert )
    {
        $this->need_convert = (bool) $need_convert;
        return $this;
    }

    public function is_need_convert()
    {
        return $this->need_convert;
    }

    public function get_service_code( ...$args )
    {
        return false;
    }

    public static function get_additional_services()
    {
        return array();
    }

    public function register_shipment( $id_order )
    {
        return array('status' => false, 'barcodes' => array(), 'msg' => __('The used API cant get register shipment', 'omnivalt'));
    }

    public function call_courier( $params )
    {
        return array('status' => false, 'msg' => __('The used API does not have a courier call option', 'omnivalt'));
    }

    public function cancel_courier_call( $call_id )
    {
        return array('status' => false, 'call_id' => $call_id, 'msg' => __('The used API does not have a courier cancel option', 'omnivalt'));
    }

    public function get_labels( $barcodes )
    {
        $output = array(
            'status' => false,
            'msg' => '',
            'debug' => '',
            'labels' => array()
        );

        try {
            $api_label = new Label();
            $this->set_auth($api_label);

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

    public function download_labels( $barcodes )
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
            $this->set_auth($api_label);

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
                ->setPhone($data_shop->phone)
                ->setMobile($data_shop->mobile)
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
        } catch (OmnivaException $e) {
            $output['msg'] = $e->getMessage();
            $output['debug'] = $e->getData();
        }

        return $output;
    }

    public function send_statistics()
    {
        $output = array(
            'status' => false,
            'msg' => '',
            'debug' => ''
        );
        $shipments_data = OmnivaLt_Statistics::collect_data();
        $test_mode = (OmnivaLt_Debug::is_development_mode_enabled()) ? true : false;

        $prepared_prices = array();
        foreach ( $shipments_data['shipping_prices'] as $country => $methods_prices ) {
            $preparing_prices = array();
            foreach ( $methods_prices as $method_key => $prices_data ) {
                if ( ! $prices_data['enabled'] ) {
                    continue;
                }
                $price_values = array(
                    'min' => null,
                    'max' => null,
                );
                if ( is_array($prices_data['prices']) ) {
                    $min_price = 9999999;
                    $max_price = 0;
                    foreach ( $prices_data['prices'] as $price_range ) {
                        if ( $price_range['price'] < $min_price ) {
                            $min_price = $price_range['price'];
                        }
                        if ( $price_range['price'] > $max_price ) {
                            $max_price = $price_range['price'];
                        }
                    }
                    $price_values['min'] = $min_price;
                    $price_values['max'] = $max_price;
                } else {
                    $price_values['min'] = $prices_data['prices'];
                }

                $preparing_prices[] = array(
                    'method' => $method_key,
                    'prices' => $price_values,
                );
            }

            $prepared_prices[$country] = array(
                'courier' => null,
                'pickup' => null,
            );
            foreach ( $preparing_prices as $price ) {
                if ( $price['method'] == 'courier' ) {
                    $prepared_prices[$country]['courier'] = $price['prices'];
                }
                if ( $price['method'] == 'pickup' ) {
                    $prepared_prices[$country]['pickup'] = $price['prices'];
                }
            }
        }

        try {
            $api_powerbi = new OmnivaPowerBi($this->clean($this->omnivalt_settings['api_user']), $test_mode);
            $api_powerbi
                ->setPluginVersion($shipments_data['plugin_version'])
                ->setPlatform('Wordpress v' . $shipments_data['wordpress_version'] . ' WooCommerce v' . $shipments_data['woocommerce_version'])
                ->setSenderName($shipments_data['client_name'])
                ->setSenderCountry($shipments_data['client_country'])
                ->setDateTimeStamp($shipments_data['track_since'])
                ->setOrderCountCourier((isset($shipments_data['total_orders']['courier'])) ? $shipments_data['total_orders']['courier'] : 0)
                ->setOrderCountTerminal((isset($shipments_data['total_orders']['pickup'])) ? $shipments_data['total_orders']['pickup'] : 0);
            foreach ( $prepared_prices as $country => $prices ) {
                if ( $prices['courier'] !== null ) {
                    $api_powerbi->setCourierPrice($country, $prices['courier']['min'], $prices['courier']['max']);
                }
                if ( $prices['pickup'] !== null ) {
                    $api_powerbi->setTerminalPrice($country, $prices['pickup']['min'], $prices['pickup']['max']);
                }
            }

            $result = $api_powerbi->send();
            if ( $result ) {
                $output['status'] = true;
            }
        } catch (OmnivaException $e) {
            $output['msg'] = $e->getMessage();
            $output['debug'] = $e->getData();
        }

        return $output;
    }

    protected function set_auth( $object )
    {
        if( method_exists($object, 'setAuth') ) {
            $object->setAuth(
                $this->clean($this->omnivalt_settings['api_user']),
                $this->clean($this->omnivalt_settings['api_pass']),
                '',
                OmnivaLt_Debug::check_debug_enabled()
            );
        }
    }

    protected function get_shop_data( $object = true )
    {
        $settings_data = array(
            'name' => $this->omnivalt_settings['shop_name'] ?? '',
            'street' => $this->omnivalt_settings['shop_address'] ?? '',
            'city' => $this->omnivalt_settings['shop_city'] ?? '',
            'country' => $this->omnivalt_settings['shop_countrycode'] ?? '',
            'postcode' => $this->omnivalt_settings['shop_postcode'] ?? '',
            'phone' => $this->omnivalt_settings['shop_phone'] ?? '',
            'mobile' => $this->omnivalt_settings['shop_mobile'] ?? '',
            'email' => $this->omnivalt_settings['shop_email'] ?? '',
            'pick_day' => '',
            'pick_from' => $this->omnivalt_settings['pick_up_start'] ?? '',
            'pick_until' => $this->omnivalt_settings['pick_up_end'] ?? '',
            'api_country' => $this->omnivalt_settings['api_country'] ?? '',
            'courier_comment' => $this->omnivalt_settings['pickup_comment'] ?? '',
        );

        $data = array();
        foreach ( $settings_data as $key => $value ) {
            $value = $this->clean($value);
            
            if ( $key == 'email' && empty($value) ) {
                $value = get_bloginfo('admin_email');
            }
            if ( $key == 'pick_day' && empty($value) ) {
                $value = current_time('Y-m-d');
            }
            if ( $key == 'pick_from' && empty($value) ) {
                $value = '8:00';
            }
            if ( $key == 'pick_until' && empty($value) ) {
                $value = '17:00';
            }

            $data[$key] = $value;
        }

        if ( current_time('timestamp') > strtotime($data['pick_day'] . ' ' . $data['pick_from']) ) {
            $data['pick_day'] = date('Y-m-d', strtotime($data['pick_day'] . "+1 days"));
        }

        return ($object) ? (object) $data : $data;
    }

    protected function get_client_data( $order, $object = true )
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

        $data['postcode'] = $this->fix_postcode($data['country'], $data['postcode']);

        return ($object) ? (object) $data : $data;
    }

    protected function get_client_fullname( $client_data )
    {
        if ( ! empty($client_data->company) ) {
            return trim($client_data->company);
        }

        return trim($client_data->name . ' ' . $client_data->surname);
    }

    protected function get_client_fulladress( $order )
    {    
        $address = OmnivaLt_Order::get_customer_full_address($order);
        if ( ! empty($order->omniva->terminal_id) ) {
            $address = OmnivaLt_Terminals::get_terminal_address($order->omniva->terminal_id, true);
        }

        return trim(OmnivaLt_Order::get_customer_fullname_or_company($order) . ', ' . $address);
    }

    protected function get_settings_data()
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

    protected function get_packages_data( $order )
    {
        $data = array();

        for ( $i = 0; $i < $order->shipment->total_shipments; $i++ ) { // Preparing for multiple packages
            $shipment_size = $this->prepare_package_size($order->shipment->size, $order->units);

            $shipment_data = array(
                'id' => $order->id,
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

    protected function get_shipments_data( $order )
    {
        $data = array(
            'barcodes' => $order->omniva->barcodes,
            'weight' => $order->shipment->size['weight'],
        );

        return (object) $data;
    }

    protected function fill_comment_variables( $comment, $variables, $order )
    {
        foreach ( $variables as $key => $title ) {
            $value = '';

            if ( $key === 'order_id' ) $value = $order->id;
            if ( $key === 'order_number' ) $value = $order->number;

            $comment = str_replace('{' . $key . '}', $value, $comment);
        }

        return $comment;
    }

    protected function get_additional_services_for_shipment( $order, $shipment_service )
    {
        return array();
    }

    protected function get_reference_number( $order_number )
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

    private function get_return_code_sending()
    {
        $add_to_sms = false;
        $add_to_email = false;

        if ( isset($this->omnivalt_settings['send_return_code']) ) {
            switch ($this->omnivalt_settings['send_return_code']) {
                case 'all':
                    $add_to_sms = true;
                    $add_to_email = true;
                    break;
                case 'sms':
                    $add_to_sms = true;
                    break;
                case 'email':
                    $add_to_email = true;
                    break;
                case 'yes':
                    $add_to_sms = true;
                    $add_to_email = true;
                    break;
            }
        }

        return (object)array(
            'sms' => $add_to_sms,
            'email' => $add_to_email,
        );
    }

    protected function fix_postcode( $country, $postcode )
    {
        $postcode = preg_replace("/[^0-9]/", "", $postcode);
        if ($country == 'LV') {
            $postcode = 'LV-' . $postcode;
        }

        return $postcode;
    }

    protected function clean( $string )
    {
        return trim($string);
    }
}
