<?php
class OmnivaLt_Helper
{
  public static function get_order_services($order)
  {
    $services = OmnivaLt_Product::get_order_items_services($order, true);
    $services = self::override_with_order_services($order->get_id(), $services);

    return $services;
  }

  public static function override_with_order_services($order_id, $services)
  {
    $configs_services = OmnivaLt_Core::get_configs('additional_services');
    $order_services = array();
    
    foreach ($configs_services as $service_key => $service_values) {
      if ($service_key == 'arrival_email' && self::check_service_email_on_arrive()) {
        $order_services[$service_key] = 'yes';
      }
      if ($service_key == 'cod' && self::check_service_cod($order_id)) {
        $order_services[$service_key] = 'yes';
      }

      $current_value = get_post_meta($order_id, '_omnivalt_' . $service_key, true);
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
    $settings = get_option($configs['settings_key']);

    if ( isset($settings['send_email_on_arrive']) && $settings['send_email_on_arrive'] === 'yes' ) {
      return true;
    }

    return false;
  }

  public static function check_service_cod($order_id)
  {
    $cod_payments = OmnivaLt_Core::get_configs('cod');
    $current_payment = get_post_meta($order_id, '_payment_method', true);

    if ( in_array($current_payment, $cod_payments) ) {
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

  public static function get_allowed_methods($set_name)
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

  public static function add_msg($msg, $type)
  {
    if (!session_id()) {
      session_start();
    }
    if (!isset($_SESSION['omnivalt_notices'])) {
      $_SESSION['omnivalt_notices'] = array();
    }
    $_SESSION['omnivalt_notices'][] = array('msg' => $msg, 'type' => $type);
  }

  public static function get_formated_time($value, $value_if_not)
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

  public static function get_shipping_service($sender_country, $receiver_country)
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

  public static function get_shipping_service_code($sender_country, $receiver_country, $get_for)
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

  public static function get_shipping_sets($sender_country, $exclude_additional = true)
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

  public static function convert_method_name_to_short($asociations, $method_name, $reverse = false)
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

  /**
   * Get method key from Woocommerce shipping method ID
   * 
   * @param string $woo_method_id - Woocommerce method ID
   * @return string
   */
  public static function get_method_key_from_woo_method_id( $woo_method_id )
  {
    return str_replace('omnivalt_', '', $woo_method_id);
  }

  /**
   * Get Woocommerce shipping method ID from method key
   * 
   * @param string $method_key - method key (short form)
   * @return string
   */
  public static function get_woo_method_id_from_method_key( $method_key )
  {
    return 'omnivalt_' . $method_key;
  }
}
