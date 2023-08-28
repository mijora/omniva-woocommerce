<?php
class OmnivaLt_Wc_Order
{
    public static function get_orders( $args )
    {
        if ( empty($args) ) {
            return false;
        }
        
        $results = wc_get_orders($args);
        return $results->orders;
    }

    public static function get_order( $wc_order_id )
    {
        if ( empty($wc_order_id) ) {
            return false;
        }

        $wc_order = wc_get_order($wc_order_id);
        if ( ! $wc_order ) {
            return false;
        }

        return $wc_order;
    }

    public static function get_data( $wc_order_id, $get_sections = array() )
    {
        $wc_order = self::get_order($wc_order_id);
        if ( ! $wc_order ) {
            return false;
        }

        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');

        if ( empty($get_sections) || in_array('shipment', $get_sections) || in_array('items', $get_sections) ) {
            $order_items = self::get_items_data($wc_order);
        }

        if ( empty($get_sections) || in_array('shipment', $get_sections) ) {
            $order_saved_dimmension = json_decode($wc_order->get_meta($meta_keys['dimmensions'], true), true);
            $order_size = OmnivaLt_Order::get_order_items_size($order_items, $order_saved_dimmension);
            $order_size['weight'] = OmnivaLt_Order::count_order_weight($order_items);
        }

        if ( empty($get_sections) || in_array('shipment', $get_sections) || in_array('omniva', $get_sections) ) {
            $order_shipping_method = OmnivaLt_Omniva_Order::get_method($wc_order);
        }

        $data = array(
            'id' => $wc_order->get_id(),
            'number' => $wc_order->get_order_number(),
            'status' => $wc_order->get_status(),
            'created' => $wc_order->get_date_created()->format('Y-m-d H:i:s'),
        );

        if ( empty($get_sections) || in_array('admin', $get_sections) ) {
            $data['admin'] = (object) array(
                'url_edit' => $wc_order->get_edit_order_url(),
            );
        }

        if ( empty($get_sections) || in_array('shipping', $get_sections) ) {
            $data['shipping'] = (object) array(
                'name' => $wc_order->get_shipping_first_name(),
                'surname' => $wc_order->get_shipping_last_name(),
                'company' => $wc_order->get_shipping_company(),
                'address_1' => $wc_order->get_shipping_address_1(),
                'postcode' => $wc_order->get_shipping_postcode(),
                'city' => $wc_order->get_shipping_city(),
                'country' => $wc_order->get_shipping_country(),
                'email' => $wc_order->get_billing_email(),
                'phone' => $wc_order->get_shipping_phone(),
            );
        }

        if ( empty($get_sections) || in_array('billing', $get_sections) ) {
            $data['billing'] = (object) array(
                'name' => $wc_order->get_billing_first_name(),
                'surname' => $wc_order->get_billing_last_name(),
                'company' => $wc_order->get_billing_company(),
                'address_1' => $wc_order->get_billing_address_1(),
                'postcode' => $wc_order->get_billing_postcode(),
                'city' => $wc_order->get_billing_city(),
                'country' => $wc_order->get_billing_country(),
                'email' => $wc_order->get_billing_email(),
                'phone' => $wc_order->get_billing_phone(),
            );
        }

        if ( empty($get_sections) || in_array('payment', $get_sections) ) {
            $data['payment'] = (object) array(
                'method' => $wc_order->get_payment_method(),
                'total_discount' => $wc_order->get_total_discount(),
                'subtotal' => $wc_order->get_subtotal(),
                'total_tax' => $wc_order->get_total_tax(),
                'total_shipping' => $wc_order->get_shipping_total(),
                'total' => $wc_order->get_total(),
            );
        }

        if ( empty($get_sections) || in_array('shipment', $get_sections) ) {
            $data['shipment'] = (object) array(
                'method' => $order_shipping_method,
                'size' => $order_size,
                'formated_shipping_address' => $wc_order->get_formatted_shipping_address(),
            );
        }

        if ( empty($get_sections) || in_array('items', $get_sections) ) {
            $data['items'] = $order_items;
        }

        if ( empty($get_sections) || in_array('omniva', $get_sections) ) {
            $data['omniva'] = (object) array(
                'method' => OmnivaLt_Omniva_Order::get_method_key_from_id($order_shipping_method),
                'terminal_id' => $wc_order->get_meta($meta_keys['terminal_id'], true),
                //'barcodes' => $wc_order->get_meta($meta_keys['barcodes'], true), //Faster way
                'barcodes' => OmnivaLt_Omniva_Order::get_barcodes($wc_order->get_id()), //Compatibility with old
                'manifest_date' => $wc_order->get_meta($meta_keys['manifest_date'], true),
                'error' => $wc_order->get_meta($meta_keys['error'], true),
            );
        }

        if ( empty($get_sections) || in_array('meta_data', $get_sections) ) {
            $data['meta_data'] = OmnivaLt_Helper::purge_meta_data($wc_order->get_meta_data());
        }

        $data['units'] = OmnivaLt_Helper::get_units();

        return (object) $data;
    }

    public static function get_items_data( $wc_order )
    {
        $order_items = array();

        foreach ( $wc_order->get_items() as $item_id => $product_item ) {
            $item_data = array(
                'product_id' => $product_item->get_product_id(),
                'quantity' => $product_item->get_quantity(),
                'weight' => 0,
                'length' => 0,
                'width' => 0,
                'height' => 0,
                'meta_data' => OmnivaLt_Helper::purge_meta_data($product_item->get_meta_data()),
                'product_meta_data' => array(),
            );

            $product = $product_item->get_product();
            if ( $product ) {
                if ( ! empty($product->get_weight()) ) $item_data['weight'] = (float) $product->get_weight();
                if ( ! empty($product->get_length()) ) $item_data['length'] = (float) $product->get_length();
                if ( ! empty($product->get_width()) ) $item_data['width'] = (float) $product->get_width();
                if ( ! empty($product->get_height()) ) $item_data['height'] = (float) $product->get_height();
                $item_data['product_meta_data'] = OmnivaLt_Helper::purge_meta_data($product->get_meta_data());
            }

            $order_items[$item_id] = $item_data;
        }

        return $order_items;
    }

    public static function get_shipping_methods( $wc_order_id )
    {
        $methods = array();

        $wc_order = self::get_order($wc_order_id);
        if ( ! $wc_order ) {
            return $methods;
        }

        foreach ( $wc_order->get_items('shipping') as $item_id => $shipping_item_obj ) {
            $methods[] = $shipping_item_obj->get_method_id();
        }

        return $methods;
    }

    public static function update_meta( $wc_order_id, $meta_key, $value )
    {
        $wc_order = self::get_order($wc_order_id);
        if ( ! $wc_order ) {
            return false;
        }

        $wc_order->update_meta_data($meta_key, $value);
        $wc_order->save();

        return true;
    }

    public static function get_meta( $wc_order_id, $meta_key )
    {
        $wc_order = self::get_order($wc_order_id);
        if ( ! $wc_order ) {
            return false;
        }

        return $wc_order->get_meta($meta_key, true);
    }

    public static function add_note( $wc_order_id, $note )
    {
        $wc_order = self::get_order($wc_order_id);
        if ( ! $wc_order ) {
            return false;
        }

        $wc_order->add_order_note($note);
    }

    public static function get_all_statuses()
    {
        return wc_get_order_statuses();
    }
}
