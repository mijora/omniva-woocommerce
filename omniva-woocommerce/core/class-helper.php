<?php
class OmnivaLt_Helper
{
  public static function get_order_services( $order )
  {
    $services = OmnivaLt_Product::get_order_items_services($order->items, true);
    $services = self::override_with_order_services($order, $services);

    return $services;
  }

  public static function override_with_order_services( $order, $services )
  {
    $configs_services = OmnivaLt_Core::get_configs('additional_services');
    $order_services = array();
    
    foreach ($configs_services as $service_key => $service_values) {
      if ($service_key == 'arrival_email' && self::check_service_email_on_arrive()) {
        $order_services[$service_key] = 'yes';
      }
      if ($service_key == 'cod' && self::is_cod_payment($order->payment->method)) {
        $order_services[$service_key] = 'yes';
      }

      $current_value = self::get_value_from_array($order->meta_data, '_omnivalt_' . $service_key, '');
      if (!$service_values['add_always'] && $current_value != '') {
        $order_services[$service_key] = $current_value;
      }
    }

    foreach ($order_services as $service => $value) {
      if (!empty($value)) { //TODO: Upgrade when services will be other then yes/no
        if ($value === 'yes' && !in_array($service, $services)) {
          $services[] = $service;
        }
        if ($value === 'no') {
          if (($key = array_search($service, $services)) !== false) {
            unset($services[$key]);
          }
        }
      }
    }

    return $services;
  }

  public static function check_service_email_on_arrive()
  {
    $configs = OmnivaLt_Core::get_configs();
    $settings = get_option($configs['plugin']['settings_key']);

    if ( isset($settings['send_email_on_arrive']) && $settings['send_email_on_arrive'] === 'yes' ) {
      return true;
    }

    return false;
  }

  public static function is_cod_payment( $payment_key )
  {
    $cod_payments = OmnivaLt_Core::get_configs('cod');
    return (in_array($payment_key, $cod_payments));

    if ( in_array($payment_key, $cod_payments) ) {
      return true;
    }

    return false;
  }

  public static function get_methods_asociations()
  {
    $asociations = array();
    $shipping_methods = OmnivaLt_Core::get_configs('method_params');
    foreach ( $shipping_methods as $method_name => $method_params ) {
      if ( ! isset($method_params['key']) || ! $method_params['is_shipping_method'] ) continue;
      if ( $method_name === 'terminal' ) $method_name = 'pickup'; //Fix old value

      $asociations[$method_params['key']] = $method_name;
    }

    return $asociations;
  }

  public static function explode_shipping_set( $shipping_set )
  {
    $shipping_sets = array(
      'send' => false,
      'receive' => false,
    );

    if ( strpos($shipping_set, ' ') === false ) {
      return false;
    }

    $exploded = explode(' ', $shipping_set);

    if ( ! empty($exploded[0]) ) {
      $shipping_sets['send'] = $exploded[0];
    }
    if ( ! empty($exploded[1]) ) {
      $shipping_sets['receive'] = $exploded[1];
    }

    return $shipping_sets;
  }

  public static function get_allowed_methods( $set_name )
  {
    $configs = OmnivaLt_Core::get_configs();

    if ( ! isset($configs['shipping_sets'][$set_name]) ) {
      return array('status' => 'error', 'error_code' => '003');
    }

    $allowed_methods = array();
    foreach ( $configs['shipping_sets'][$set_name] as $combination => $service ) {
      if ( $combination === 'courier_call' ) continue;
      $exploded = explode(' ', $combination);
      if ( ! isset($exploded[1]) ) continue;
      if ( ! in_array($exploded[1], $allowed_methods) ) {
        $allowed_methods[] = $exploded[1];
      }
    }

    return $allowed_methods;
  }

  public static function add_msg( $msg, $type )
  {
    if (!session_id()) {
      session_start();
    }
    if (!isset($_SESSION['omnivalt_notices'])) {
      $_SESSION['omnivalt_notices'] = array();
    }
    $_SESSION['omnivalt_notices'][] = array('msg' => $msg, 'type' => $type);
  }

  public static function get_formated_time( $value, $value_if_not )
  {
    if (!preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $value)) {
      if ((string)(int)$value === $value || is_int($value)) {
        return $value . ':00';
      } else {
        return $value_if_not;
      }
    }
    
    return $value;
  }

  public static function clear_file_name( $file_name )
  {
    return preg_replace("/[^a-zA-Z0-9\.\-\_]+/", "", $file_name);
  }

  public static function get_shipping_service( $sender_country, $receiver_country )
  {
    $shipping_params = OmnivaLt_Core::get_configs('shipping_params');

    if ( ! isset($shipping_params[$sender_country]) ) {
      return false;
    }

    if ( ! isset($shipping_params[$sender_country]['shipping_sets'][$receiver_country]) ) {
      return false;
    }

    return $shipping_params[$sender_country]['shipping_sets'][$receiver_country];
  }

  public static function get_shipping_service_code( $sender_country, $receiver_country, $get_for )
  {
    $shipping_sets = OmnivaLt_Core::get_configs('shipping_sets');

    $service_set = self::get_shipping_service($sender_country, $receiver_country);
    if ( ! $service_set ) {
      return array('status' => 'error', 'msg' => __('Failed to get service set', 'omnivalt'));
    }
    
    if ( ! isset($shipping_sets[$service_set]) ) {
      return array('status' => 'error', 'msg' => OmnivaLt_Core::get_error_text('003'));
    }

    if ( ! isset($shipping_sets[$service_set][$get_for]) ) {
      $shipping_set = self::explode_shipping_set($get_for);
      if ( ! $shipping_set ) {
        $shipping_set = array('send' => $get_for, 'receive' => $get_for);
      }

      return array('status' => 'error', 'error_code' => '004', 'msg' => sprintf(
        __('Shipping from %1$s (%2$s) to %3$s (%4$s) is not available', 'omnivalt'),
        OmnivaLt_Configs::get_method_title($shipping_set['send']),
        WC()->countries->countries[$sender_country],
        OmnivaLt_Configs::get_method_title($shipping_set['receive']),
        WC()->countries->countries[$receiver_country]
      ));
    }
    
    return $shipping_sets[$service_set][$get_for];
  }

  public static function get_shipping_sets( $sender_country, $exclude_additional = true )
  {
    $configs = OmnivaLt_Core::get_configs();

    if ( ! isset($configs['shipping_params'][$sender_country]) ) {
      return array('status' => 'error', 'error_code' => '001');
    }

    $shipping_sets = $configs['shipping_params'][$sender_country]['shipping_sets'];
    if ( $exclude_additional && isset($shipping_sets['call']) ) {
      unset($shipping_sets['call']);
    }

    return $shipping_sets;
  }

  public static function convert_method_name_to_short( $asociations, $method_name, $reverse = false )
  {
    foreach ( $asociations as $key => $value ) {
      if ( ! $reverse ) {
        if ( $method_name === $value ) {
          return $key;
        }
      } else {
        if ( $method_name === $key ) {
          return $value;
        }
      }
    }

    return $method_name;
  }

  public static function predict_order_size( $items_data, $max_dimension = array() )
  {
    $all_order_dim_length = 0;
    $all_order_dim_width = 0;
    $all_order_dim_height = 0;
    $max_dim_length = (!empty($max_dimension['length'])) ? $max_dimension['length'] : 999999;
    $max_dim_width = (!empty($max_dimension['width'])) ? $max_dimension['width'] : 999999;
    $max_dim_height = (!empty($max_dimension['height'])) ? $max_dimension['height'] : 999999;

    foreach ( $items_data as $item ) {
      $item_dim_length = (!empty($item['length'])) ? $item['length'] : 0;
      $item_dim_width = (!empty($item['width'])) ? $item['width'] : 0;
      $item_dim_height = (!empty($item['height'])) ? $item['height'] : 0;

      //Add to length
      if ( ($item_dim_length + $all_order_dim_length) <= $max_dim_length 
        && $item_dim_width <= $max_dim_width && $item_dim_height <= $max_dim_height )
      {
        $all_order_dim_length = $all_order_dim_length + $item_dim_length;
        $all_order_dim_width = ($item_dim_width > $all_order_dim_width) ? $item_dim_width : $all_order_dim_width;
        $all_order_dim_height = ($item_dim_height > $all_order_dim_height) ? $item_dim_height : $all_order_dim_height;
      }
      //Add to width
      else if ( ($item_dim_width + $all_order_dim_width) <= $max_dim_width 
        && $item_dim_length <= $max_dim_length && $item_dim_height <= $max_dim_height )
      {
        $all_order_dim_length = ($item_dim_length > $all_order_dim_length) ? $item_dim_length : $all_order_dim_length;
        $all_order_dim_width = $all_order_dim_width + $item_dim_width;
        $all_order_dim_height = ($item_dim_height > $all_order_dim_height) ? $item_dim_height : $all_order_dim_height;
      }
      //Add to height
      else if ( ($item_dim_height + $all_order_dim_height) <= $max_dim_height 
        && $item_dim_length <= $max_dim_length && $item_dim_width <= $max_dim_width )
      {
        $all_order_dim_length = ($item_dim_length > $all_order_dim_length) ? $item_dim_length : $all_order_dim_length;
        $all_order_dim_width = ($item_dim_width > $all_order_dim_width) ? $item_dim_width : $all_order_dim_width;
        $all_order_dim_height = $all_order_dim_height + $item_dim_height;
      }
      //If all fails
      else {
        return false;
      }
    }

    return array(
      'length' => $all_order_dim_length,
      'width' => $all_order_dim_width,
      'height' => $all_order_dim_height,
    );
  }

  public static function purge_meta_data( $meta_data )
  {
    $purged_meta_data = array();

    foreach ($meta_data as $meta_item) {
      $data = $meta_item->get_data();
      if ( ! isset($data['key']) || ! isset($data['value']) ) {
        OmnivaLt_Debug::log('notice', "Meta data is incorrect:\n" . print_r($data, true));
        continue;
      }
      $purged_meta_data[$data['key']] = $data['value'];
    }

    return $purged_meta_data;
  }

  public static function get_value_from_array( $array, $key, $value_if_not = null )
  {
    return $array[$key] ?? $value_if_not;
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

  public static function convert_unit( $value, $new_unit, $current_unit = false, $unit_type = 'weight' )
  {
    $woo_units = self::get_units(false);
    if ( ! isset($woo_units[$unit_type]) ) {
      return $value;
    }

    switch ($unit_type) {
      case 'weight':
        if ( ! $current_unit ) {
          $current_unit = $woo_units['weight'];
        }
        return wc_get_weight($value, $new_unit, $current_unit);
        break;
      case 'dimension':
        if ( ! $current_unit ) {
          $current_unit = $woo_units['dimension'];
        }
        return wc_get_dimension($value, $new_unit, $current_unit);
        break;
    }

    return $value;
  }

  public static function is_omniva_method( $method_id )
  {
    $configs = OmnivaLt_Core::get_configs();

    foreach ( $configs['method_params'] as $method_key => $method_values ) {
      if ( $method_id == 'omnivalt_' . $method_values['key'] ) {
        return true;
      }
    }

    return false;
  }

  public static function convert_array_to_meta_query_param( $meta_key, $array, $compare = 'LIKE' ) //TODO: The function is not used, but maybe it can be used
  {
    $meta_query_params = array();

    if ( ! is_array($array) ) {
      $array = array($array);
    }

    foreach ( $array as $array_value ) {
      $build = array(
        'key' => $meta_key,
        'compare' => $compare,
      );
      if ( $compare != 'NOT EXISTS' ) {
        $build['value'] = $array_value;
      }

      $meta_query_params[] = $build;
    }

    return $meta_query_params;
  }
}
