<?php
class OmnivaLt_Helper
{
  public static function override_with_order_services($order_id, $services)
  {
    $configs_services = omnivalt_configs('additional_services');
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
}
