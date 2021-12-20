<?php
class OmnivaLt_Helper
{
  public static function override_with_order_services($order_id, $services)
  {
    $configs_services = OmnivaLt_Core::get_configs('additional_services');
    $order_services = array();
    foreach ($configs_services as $service_key => $service_values) {
      if (!$service_values['add_always']) {
        $order_services[$service_key] = get_post_meta($order_id, '_omnivalt_' . $service_key, true);
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
      /*foreach ( $exploded as $method_key ) {
        if ( ! in_array($method_key, $allowed_methods) ) {
          $allowed_methods[] = $method_key;
        }
      }*/
    }

    return $allowed_methods;
  }

  /*public static function get_allowed_methods_by_country($country)
  {
    $configs = OmnivaLt_Core::get_configs();

    if ( ! isset($configs['shipping_params'][$country]) ) {
      return array('status' => 'error', 'error_code' => '001');
    }

    $allowed_methods = array();
    foreach ( $configs['shipping_params'][$country]['shipping_sets'] as $country => $set_name ) {
      $set_methods = self::get_allowed_methods($set_name);
      foreach ( $set_methods as $method ) {
        if ( ! in_array($method, $allowed_methods) ) {
          $allowed_methods[] = $method;
        }
      }
    }

    return $allowed_methods;
  }*/

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

  public static function get_shipping_service_code($sender_country, $receiver_country, $get_for)
  {
    $configs = OmnivaLt_Core::get_configs();
    
    if ( ! isset($configs['shipping_params'][$sender_country]) ) {
      return array('status' => 'error', 'error_code' => '001');
    }
    if ( ! isset($configs['shipping_params'][$sender_country]['shipping_sets'][$receiver_country]) ) {
      return array('status' => 'error', 'error_code' => '002');
    }
    $service_set = $configs['shipping_params'][$sender_country]['shipping_sets'][$receiver_country];
    if ( ! isset($configs['shipping_sets'][$service_set]) ) {
      return array('status' => 'error', 'error_code' => '003');
    }
    if ( ! isset($configs['shipping_sets'][$service_set][$get_for]) ) {
      return array('status' => 'error', 'error_code' => '004');
    }
    
    return $configs['shipping_sets'][$service_set][$get_for];
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
}
