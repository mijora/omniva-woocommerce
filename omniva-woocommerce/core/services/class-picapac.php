<?php
defined('OMNIVALT_VERSION') or die();

class OmnivaLt_Picapac
{
    private const PICAPAC_SUPPORTED_COUNTRIES = array('EE');
    private const PICAPAC_TERMINAL_ID = '96331';
    private const PICAPAC_RATE_ID = 'omnivalt_picapac';
    private const PICAPAC_METHOD_KEY = 'picapac';
    private const OMNIVA_TERMINAL_METHOD_KEY = 'pt';

    public static function is_supported_country( $country )
    {
        return in_array(strtoupper((string) $country), self::PICAPAC_SUPPORTED_COUNTRIES, true);
    }

    public static function get_rate_id()
    {
        return self::PICAPAC_RATE_ID;
    }

    public static function get_terminal_id()
    {
        return self::PICAPAC_TERMINAL_ID;
    }

    public static function is_terminal_id( $terminal_id )
    {
        return self::PICAPAC_TERMINAL_ID === (string) $terminal_id;
    }

    public static function get_method_key()
    {
        return self::PICAPAC_METHOD_KEY;
    }

    public static function get_omniva_method_key()
    {
        return self::OMNIVA_TERMINAL_METHOD_KEY;
    }

    public static function get_omniva_method_id()
    {
        return 'omnivalt_' . self::OMNIVA_TERMINAL_METHOD_KEY;
    }

    public static function get_label()
    {
        return __('Picapac by Omniva - your home parcel machine', 'omnivalt');
    }

    public static function get_info_url()
    {
        return 'https://picapac.com';
    }

    public static function get_info_label()
    {
        return __('What is Picapac?', 'omnivalt');
    }

    public static function is_rate( $rate_id )
    {
        if ( ! is_string($rate_id) ) {
            return false;
        }

        $rate_id = explode(':', $rate_id)[0];

        return self::PICAPAC_RATE_ID === $rate_id;
    }

    public static function is_selected( $shipping_methods )
    {
        if ( empty($shipping_methods) ) {
            return false;
        }

        if ( ! is_array($shipping_methods) ) {
            $shipping_methods = array($shipping_methods);
        }

        foreach ( $shipping_methods as $shipping_method ) {
            if ( self::is_rate($shipping_method) ) {
                return true;
            }
        }

        return false;
    }
}
