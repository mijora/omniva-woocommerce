<?php
defined('OMNIVALT_VERSION') or die();

class OmnivaLt_Method
{
    public static function get_all()
    {
        return OmnivaLt_Core::get_configs('method_params');
    }

    public static function get_by_key( $key, $match_by_name = false )
    {
        foreach ( self::get_all() as $method_name => $method ) {
            if ( $key == $method['key'] ) {
                return $method;
            }
            if ( $match_by_name && $key == $method_name ) {
                return $method;
            }
        }

        return false;
    }

    public static function get_all_shipping_methods()
    {
        $shipping_methods = array();
        foreach ( self::get_all() as $method_key => $method ) {
            if ( $method['is_shipping_method'] ) {
                $shipping_methods[$method_key] = $method;
            }
        }

        return $shipping_methods;
    }

    public static function get_all_available_shipping_methods()
    {
        $shipping_params = OmnivaLt_Core::get_configs('shipping_params');
        $api_plan = OmnivaLt_Helper::get_api_plan();
        $all_countries = array_keys($shipping_params);

        $available_methods = array();
        foreach ( $all_countries as $country ) {
            $available_methods[$country] = array();
            foreach ( self::get_all_shipping_methods() as $method_key => $method ) {
                if ( (! empty($method['restrict_api']) && ! in_array(strtoupper($api_plan), $method['restrict_api']))
                    || (! empty($method['restrict_country']) && ! in_array(strtoupper($country), $method['restrict_country']))
                ) {
                    continue;
                }
                $available_methods[$country][$method_key] = $method;
            }
        }

        return $available_methods;
    }

    public static function get_all_wc_methods_domestic_keys()
    {
        $methods_keys = array();
        foreach ( self::get_all_shipping_methods() as $method ) {
            $methods_keys[$method['key']] = OmnivaLt_Omniva_Order::get_method_id_from_key($method['key']);
        }

        return $methods_keys;
    }

    public static function get_method_param( $method_key, $param_key, $fail_value = false )
    {
        $method = self::get_by_key($method_key);
        if ( $method && ! empty($method[$param_key]) ) {
            return $method[$param_key];
        }

        return $fail_value;
    }

    public static function get_title( $method_key )
    {
        return self::get_method_param($method_key, 'title', __('Unknown', 'omnivalt'));
    }

    public static function get_terminal_type( $method_key )
    {
        return self::get_method_param($method_key, 'terminals_type');
    }

    public static function is_omniva_domestic( $method_key )
    {
        $method_key = OmnivaLt_Omniva_Order::get_method_key_from_id($method_key);
        
        foreach ( self::get_all() as $method ) {
            if ( $method_key == $method['key'] ) {
                return true;
            }
        }

        return false;
    }

    public static function is_omniva_domestic_terminal( $method_key )
    {
        if ( ! self::is_omniva_domestic($method_key) ) {
            return false;
        }

        $method_key = OmnivaLt_Omniva_Order::get_method_key_from_id($method_key);
        $method = self::get_by_key($method_key);

        return ($method['terminals_type']) ? true : false;
    }

    public static function get_all_international_keys()
    {
        $methods_keys = [];
        $api = new OmnivaLt_Api_International();
        foreach ( $api->get_available_packages() as $package_key => $package_zones ) {
            foreach ( $package_zones as $zone => $zone_countries ) {
                $international_data = array(
                    'key' => $package_key,
                    'title' => $api->get_package_title($package_key),
                );
                $shipping_international = new OmnivaLt_Shipping_Method_International($international_data, '');
                $shipping_international->setCurrentMethodKey($zone);
                $method = $shipping_international->getCurrentMethod();
                $methods_keys[] = $method['key'];
            }
        }

        return $methods_keys;
    }

    public static function get_all_wc_methods_international_keys()
    {
        $methods_keys = array();
        foreach ( self::get_all_international_keys() as $international_method_key ) {
            $methods_keys[$international_method_key] = OmnivaLt_Omniva_Order::get_method_id_from_key($international_method_key);
        }

        return $methods_keys;
    }

    public static function is_omniva_international( $method_key )
    {
        $method_key = OmnivaLt_Omniva_Order::get_method_key_from_id($method_key);
        $all_keys = self::get_all_international_keys();

        return (in_array($method_key, $all_keys)) ? true : false;
    }

    public static function get_all_wc_methods_keys()
    {
        $domestic_keys = self::get_all_wc_methods_domestic_keys();
        $international_keys = self::get_all_wc_methods_international_keys();

        return $domestic_keys + $international_keys;
    }
}
