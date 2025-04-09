<?php
class OmnivaLt_Wc_Order
{
    public static function get_orders( $args, $get_result_object = false )
    {
        if ( empty($args) ) {
            return false;
        }
        
        $results = wc_get_orders($args);
        return ($get_result_object) ? $results : $results->orders;
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

    public static function get_data( $wc_order, $get_sections = array() )
    {
        if ( ! is_object($wc_order) ) {
            $wc_order = self::get_order($wc_order);
        }
        if ( ! $wc_order ) {
            return false;
        }

        if ( self::is_order_type_refund($wc_order) ) {
            return (object) array(
                'id' => $wc_order->get_id(),
                'type' => 'refund',
            );
        }

        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');

        if ( empty($get_sections) || in_array('shipment', $get_sections) || in_array('items', $get_sections) ) {
            $order_items = self::get_items_data($wc_order);
        }

        if ( empty($get_sections) || in_array('shipment', $get_sections) || in_array('omniva', $get_sections) ) {
            $order_shipping_method = OmnivaLt_Omniva_Order::get_method($wc_order);
            $order_shipping_method_meta_data = array();
            foreach ( $wc_order->get_shipping_methods() as $method ) {
                if ( $method->get_method_id() == 'omnivalt' ) {
                    $order_shipping_method_meta_data['size'] = $method->get_meta('Size', true);
                }
            }
        }

        if ( empty($get_sections) || in_array('shipment', $get_sections) ) {
            $order_saved_dimmension = json_decode($wc_order->get_meta($meta_keys['dimmensions'], true), true);
            $order_size = ($order_saved_dimmension !== NULL) ? OmnivaLt_Order::organize_size_values($order_saved_dimmension) : false;
            if ( ! $order_size ) {
                $order_size = self::get_order_size($order_items, $order_shipping_method_meta_data['size'] ?? '');
            }
            $order_size['weight'] = OmnivaLt_Order::count_order_weight($order_items);
            $order_total_shipments = $wc_order->get_meta($meta_keys['total_shipments'], true);
            if ( empty($order_total_shipments) ) {
                $order_total_shipments = OmnivaLt_Order::count_order_total_shipments($order_items);
            }
        }

        $order_date = $wc_order->get_date_created();

        $data = array(
            'id' => $wc_order->get_id(),
            'number' => $wc_order->get_order_number(),
            'type' => 'order',
            'status' => $wc_order->get_status(),
            'created' => (! empty($order_date)) ? $order_date->format('Y-m-d H:i:s') : '2000-01-01 00:00:00',
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
                'method_meta' => $order_shipping_method_meta_data,
                'total_shipments' => $order_total_shipments,
                'size' => $order_size,
                'formated_shipping_address' => $wc_order->get_formatted_shipping_address(),
                'country' => $wc_order->get_shipping_country()
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
                'order_tracked' => $wc_order->get_meta($meta_keys['order_tracked'], true),
            );
        }

        if ( empty($get_sections) || in_array('meta_data', $get_sections) ) {
            $data['meta_data'] = OmnivaLt_Helper::purge_meta_data($wc_order->get_meta_data());
        }

        $data['units'] = OmnivaLt_Wc::get_units();

        return (object) $data;
    }

    public static function is_order_type_refund( $wc_order )
    {
        if ( ! is_object($wc_order) ) {
            $wc_order = self::get_order($wc_order);
        }
        if ( ! $wc_order ) {
            return false;
        }

        return $wc_order instanceof WC_Order_Refund;
    }

    private static function get_order_size( $order_items, $get_box_size = '' )
    {
        $box_sizes = OmnivaLt_Shipmethod_Helper::get_omniva_box_sizes();
        $prepared_items = OmnivaLt_Helper::get_products_measurements_list(OmnivaLt_Order::spread_items($order_items));
        if ( ! empty($get_box_size) && isset($box_sizes[$get_box_size]) ) {
            $order_size = OmnivaLt_Helper::predict_order_size($prepared_items, $box_sizes[$get_box_size]);
        } else {
            $order_size = OmnivaLt_Helper::predict_order_size($prepared_items);
        }

        return ($order_size) ? $order_size : array('length' => 0, 'width' => 0, 'height' => 0);
    }

    public static function get_items_data( $wc_order )
    {
        $order_items = array();

        foreach ( $wc_order->get_items() as $item_id => $product_item ) {
            $item_data = array(
                'product_id' => $product_item->get_product_id(),
                'variation_id' => $product_item->get_variation_id(),
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
                if ( $item_data['variation_id'] ) {
                    $parent_product = OmnivaLt_Wc_Product::get_product($item_data['product_id']);
                    if ( $parent_product ) {
                        $item_data['product_meta_data'] = OmnivaLt_Helper::purge_meta_data($parent_product->get_meta_data());
                    }
                }
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

    public static function update_multi_meta( $wc_order_id, $meta_values )
    {
        $wc_order = self::get_order($wc_order_id);
        if ( ! $wc_order || ! is_array($meta_values) ) {
            return false;
        }

        foreach ( $meta_values as $meta_key => $value ) {
            $wc_order->update_meta_data($meta_key, $value);
        }
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

    public static function meta_exists( $wc_order_id, $meta_key )
    {
        $wc_order = self::get_order($wc_order_id);
        if ( ! $wc_order ) {
            return false;
        }

        $meta_value = self::get_meta($wc_order_id, $meta_key);
        return $meta_value !== '' && $meta_value !== null && $meta_value !== array();
    }

    public static function remove_meta( $wc_order_id, $meta_key )
    {
        $wc_order = self::get_order($wc_order_id);
        if ( ! $wc_order ) {
            return false;
        }
        if ( ! self::meta_exists($wc_order_id, $meta_key) ) {
            return true;
        }

        $wc_order->delete_meta_data($meta_key);
        $wc_order->save();

        return true;
    }

    public static function add_note( $wc_order_id, $note )
    {
        $wc_order = self::get_order($wc_order_id);
        if ( ! $wc_order || ! method_exists($wc_order, 'add_order_note') ) {
            return false;
        }

        $wc_order->add_order_note($note);
    }

    public static function get_all_statuses()
    {
        return wc_get_order_statuses();
    }
}
