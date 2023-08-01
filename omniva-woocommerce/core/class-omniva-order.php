<?php
class OmnivaLt_Omniva_Order
{
    public static function set_method( $order_id, $order_methods_list )
    {
        if ( empty($order_id) || empty($order_methods_list) ) {
            return false;
        }

        if ( ! is_array($order_methods_list) ) {
            $order_methods_list = array($order_methods_list);
        }

        $configs = OmnivaLt_Core::get_configs();

        foreach ( $order_methods_list as $ship_method ) {
            foreach ( $configs['method_params'] as $method_name => $method_values ) {
                if ( ! $method_values['is_shipping_method'] ) continue;
                if ( $ship_method == "omnivalt_" . $method_values['key'] ) {
                    OmnivaLt_Wc_Order::update_meta($order_id, $configs['meta_keys']['method'], $ship_method);
                    return true;
                }
            }
        }

        return false;
    }

    public static function get_method( $order_id )
    {
        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        $send_methods = OmnivaLt_Wc_Order::get_shipping_methods($order_id);
        
        foreach ( $send_methods as $method ) {
            if ( $method == 'omnivalt' ) {
                return OmnivaLt_Wc_Order::get_meta($order_id, $meta_keys['method']);
            }
        }

        return false;
    }

    public static function get_method_key_from_id( $woo_method_id )
    {
        return str_replace('omnivalt_', '', $woo_method_id);
    }

    public static function get_method_id_from_key( $method_key )
    {
        return 'omnivalt_' . $method_key;
    }

    public static function set_terminal_id( $order_id, $terminal_id )
    {
        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        return OmnivaLt_Wc_Order::update_meta($order_id, $meta_keys['terminal_id'], $terminal_id);
    }

    public static function get_terminal_id( $order_id )
    {
        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        return OmnivaLt_Wc_Order::get_meta($order_id, $meta_keys['terminal_id']);
    }

    public static function set_barcodes( $order_id, $barcodes )
    {
        if ( empty($barcodes) ) {
            $barcodes = '';
        }
        if ( ! is_array($barcodes) && ! empty($barcodes) ) {
            $barcodes = array($barcodes);
        }

        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        return OmnivaLt_Wc_Order::update_meta($order_id, $meta_keys['barcodes'], $barcodes);
    }

    public static function get_barcodes( $order_id )
    {
        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        $barcodes = OmnivaLt_Wc_Order::get_meta($order_id, $meta_keys['barcodes']);
        
        if ( ! empty($barcodes) && ! is_array($barcodes) ) { //Compatibility with old
            $barcodes = array($barcodes);
        }

        return $barcodes;
    }

    public static function set_error( $order_id, $error_msg )
    {
        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        return OmnivaLt_Wc_Order::update_meta($order_id, $meta_keys['error'], $error_msg);
    }

    public static function get_error( $order_id )
    {
        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        return OmnivaLt_Wc_Order::get_meta($order_id, $meta_keys['error']);
    }

    public static function set_dimmensions( $order_id, $dimmensions )
    {
        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        return OmnivaLt_Wc_Order::update_meta($order_id, $meta_keys['dimmensions'], $dimmensions);
    }

    public static function get_dimmensions( $order_id )
    {
        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        return OmnivaLt_Wc_Order::get_meta($order_id, $meta_keys['dimmensions']);
    }

    public static function set_manifest_date( $order_id, $manifest_date )
    {
        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        return OmnivaLt_Wc_Order::update_meta($order_id, $meta_keys['manifest_date'], $manifest_date);
    }

    public static function get_manifest_date( $order_id )
    {
        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        return OmnivaLt_Wc_Order::get_meta($order_id, $meta_keys['manifest_date']);
    }
}
