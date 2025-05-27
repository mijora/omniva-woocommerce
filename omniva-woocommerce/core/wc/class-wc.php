<?php
class OmnivaLt_Wc
{
    public static function get_page_recognition_ids( $type_key )
    {
        $all_screen_ids = array(
            'admin_order_edit' => array('shop_order', 'woocommerce_page_wc-orders'),
            'admin_manifest' => array('woocommerce_page_omniva-manifest'),
        );

        return $all_screen_ids[$type_key] ?? false;
    }

    public static function get_post_type_by_id( $page_id )
    {
        return get_post_type($page_id);
    }

    public static function get_current_screen_id()
    {
        if ( ! function_exists('get_current_screen') ) { 
            return false;
        }

        $screen = get_current_screen();
        if ( is_object($screen) && isset($screen->id) && ! empty($screen->id) ) {
            return $screen->id;
        }

        return false;
    }

    public static function is_endpoint_url( $endpoint )
    {
        return is_wc_endpoint_url($endpoint);
    }

    public static function get_session( $session_key )
    {
        return WC()->session->get($session_key);
    }

    public static function get_customer_from_global()
    {
        return WC()->customer;
    }

    public static function get_cart()
    {
        return WC()->cart;
    }

    public static function get_country_name( $country_code )
    {
        $countries = WC()->countries->countries;
        return $countries[$country_code] ?? $country_code;
    }

    public static function get_units( $get_as_object = true )
    {
        $units = array(
            'weight' => get_option('woocommerce_weight_unit'),
            'dimension' => get_option('woocommerce_dimension_unit'),
            'currency' => get_option('woocommerce_currency'),
            'currency_symbol' => get_woocommerce_currency_symbol(),
        );

        return ($get_as_object) ? (object) $units : $units;
    }

    public static function get_weight( $weight, $to_unit, $from_unit = '' )
    {
        return wc_get_weight($weight, $to_unit, $from_unit);
    }

    public static function get_dimension( $dimension, $to_unit, $from_unit = '' )
    {
        return wc_get_dimension($dimension, $to_unit, $from_unit);
    }

    public static function get_coupons( $args = array() )
    {
        $default_args = array(
          'posts_per_page'   => -1,
          'orderby'          => 'title',
          'order'            => 'asc',
          'post_type'        => 'shop_coupon',
          'post_status'      => 'publish',
        );
        $args = array_replace($default_args, $args);
        return get_posts($args);
    }

    public static function is_using_hpos()
    {
        if ( method_exists('\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled') ) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }
}
