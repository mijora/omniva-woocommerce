<?php
class OmnivaLt_Statistics
{
    public static function collect_data()
    {
        $settings = OmnivaLt_Core::get_settings();
        $configs = OmnivaLt_Core::get_configs();
        $wc_orders = self::get_orders();
        $orders_data = array();

        foreach ( $wc_orders as $wc_order ) {
            $orders_data[] = OmnivaLt_Wc_Order::get_data($wc_order, array('shipment', 'payment', 'omniva'));
        }

        $oldest_order = current_time('Y-m-d H:i:s');
        $orders_count = array();
        $shipments_count = array();
        $shipments_income = array();
        foreach ( $orders_data as $order ) {
            if ( $order->created < $oldest_order ) {
                $oldest_order = $order->created;
            }
            self::add_to_counter($orders_count, $order->omniva->method, 1);
            self::add_to_counter($shipments_count, $order->omniva->method, count($order->omniva->barcodes));
            self::add_to_counter($shipments_income, $order->omniva->method, $order->payment->total_shipping);
            OmnivaLt_Wc_Order::update_meta($order->id, $configs['meta_keys']['order_tracked'], current_time('Y-m-d H:i:s'));
        }

        return array(
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_version' => WC_VERSION,
            'plugin_version' => OMNIVALT_VERSION,
            'api_user' => (isset($settings['api_user'])) ? $settings['api_user'] : null,
            'client_name' => (isset($settings['company'])) ? $settings['company'] : null,
            'client_country' => (isset($settings['shop_countrycode'])) ? $settings['shop_countrycode'] : null,
            'total_orders' => $orders_count,
            'track_since' => $oldest_order,
            'total_shipments' => $shipments_count,
            'shipments_income' => $shipments_income,
            'shipping_prices' => OmnivaLt_Helper::get_shipping_methods_prices(),
        );
    }

    private static function get_orders()
    {
        $configs = OmnivaLt_Core::get_configs();
        $shipping_methods = array_values(OmnivaLt_Method::get_all_wc_methods_keys());
        $shipping_methods[] = 'omnivalt';

        $args = array(
            'limit' => -1,
            'status' => array('wc-completed'),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => $configs['meta_keys']['method'],
                    'value' => $shipping_methods,
                    'compare' => 'IN',
                ),
                array(
                    'key' => $configs['meta_keys']['order_tracked'],
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );
        return OmnivaLt_Wc_Order::get_orders($args, true);
    }

    private static function add_to_counter( &$counter, $key, $increase_value )
    {
        $method = OmnivaLt_Method::get_by_key($key);
        $method_key = ($method) ? $method['key'] : 'unknown';
        
        if ( ! is_array($counter) ) {
            $counter = array();
        }
        if ( ! isset($counter[$method_key]) ) {
            $counter[$method_key] = 0;
        }
        $counter[$method_key] += $increase_value;
    }
}
