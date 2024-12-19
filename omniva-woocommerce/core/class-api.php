<?php

class OmnivaLt_Api
{
    private $api;
    private $api_type;

    public function __construct()
    {
        $configs_api = OmnivaLt_Core::get_configs('api');
        $this->set_api_type($configs_api['type'])->load_api();
    }

    private function load_api()
    {
        $this->api = self::get_api_class($this->get_api_type());
        
        return $this;
    }

    public static function get_api_class( $api_type, $use_static = false )
    {
        $api_classes = [
            'xml' => 'OmnivaLt_Api_Xml',
            'omx' => 'OmnivaLt_Api_Omx',
            'international' => 'OmnivaLt_Api_Omx_International',
        ];

        $class = isset($api_classes[$api_type]) ? $api_classes[$api_type] : 'OmnivaLt_Api_Xml';
        return ($use_static) ? $class : new $class();
    }

    public function set_api_type( $api_type )
    {
        $allowed_types = array('xml', 'omx', 'international');
        $this->api_type = (in_array($api_type, $allowed_types)) ? $api_type : 'xml';

        return $this;
    }

    public function get_api_type()
    {
        return $this->api_type;
    }

    public function change_api_type( $new_api_type )
    {
        $this->set_api_type($new_api_type)->load_api();
    }

    public function get_tracking_number( $id_order )
    {
        return $this->api->register_shipment($id_order);
    }

    public function get_shipment_labels( $barcodes )
    {
        return $this->api->get_labels($barcodes);
    }

    public function download_shipment_labels( $barcodes )
    {
        return $this->api->download_labels($barcodes);
    }

    public function get_manifest( $orders_ids )
    {
        return $this->api->get_manifest($orders_ids);
    }

    public function call_courier( $params )
    {    
        return $this->api->call_courier($params);
    }

    public function cancel_courier_call( $call_id )
    {
        return $this->api->cancel_courier_call($call_id);
    }

    public function send_statistics()
    {
        return $this->api->send_statistics();
    }
}
