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

  public static function get_all_api_plans()
  {
    $available_shippings = OmnivaLt_Core::get_configs('shipping_available');

    return array_keys($available_shippings);
  }

  public static function get_api_plan( $api_country = false, $get_by_plan = false )
  {
    $associations = array(
      'LT' => 'baltic',
      'LV' => 'latvia',
      'EE' => 'estonia',
    );
    $default_country = 'LT';

    if ( $get_by_plan ) {
      foreach ( $associations as $country => $plan ) {
        if ( $api_country == $plan ) {
          return $country;
        }
      }

      return $default_country;
    }

    if ( ! $api_country ) {
      $settings = OmnivaLt_Core::get_settings();
      $api_country = (! empty($settings['api_country'])) ? $settings['api_country'] : $default_country;
    }

    return $associations[$api_country] ?? $associations[$default_country];

    switch ( $api_country ) {
      case 'LT':
        $api_plan = 'baltic';
        break;
      case 'EE':
        $api_plan = 'estonia';
        break;
      default:
        $api_plan = 'baltic';
    }

    return $api_plan;
  }

  public static function get_available_methods()
  {
    $configs = OmnivaLt_Core::get_configs();
    $settings = OmnivaLt_Core::get_settings();

    $api_plan = self::get_api_plan();
    $all_countries = array_keys($configs['shipping_params']);

    $available_methods = array();
    foreach ( $all_countries as $country ) {
      $available_methods[$country] = $configs['shipping_params'][$country];
      unset($available_methods[$country]['methods']);
      $available_methods[$country]['all_methods'] = $configs['shipping_params'][$country]['methods'];
      $available_methods[$country]['available_methods'] = array();
      if ( isset($configs['shipping_available'][$api_plan][$country]) ) {
        $available_methods[$country]['available_methods'] = $configs['shipping_available'][$api_plan][$country];
      }
      
      $shipping_sets = array();
      foreach ( $configs['shipping_params'][$country]['shipping_sets'] as $shipping_set_country => $shipping_set_plan ) {
        if ( $shipping_set_country == 'call' ) {
          continue;
        }
        $shipping_sets[$shipping_set_country] = $configs['shipping_sets'][$shipping_set_plan];
      }
      $available_methods[$country]['shipping_sets'] = $shipping_sets;
    }

    return $available_methods;
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

  public static function get_courier_calls()
  {
    $configs = OmnivaLt_Core::get_configs();

    $current_calls = get_option($configs['meta_keys']['courier_calls'], array());
    if ( ! empty($current_calls) && is_array($current_calls) ) {
      foreach ( $current_calls as $call_key => $call_values ) {
        if ( strtotime(current_time('Y-m-d H:i:s')) > strtotime($call_values['end']) ) {
          unset($current_calls[$call_key]);
        }
      }
    }

    return (! empty($current_calls)) ? $current_calls : array();
  }

  public static function update_courier_calls( $add_new_call = array() )
  {
    $configs = OmnivaLt_Core::get_configs();
    $current_calls = self::get_courier_calls();

    if ( ! empty($add_new_call) ) {
      $current_calls[] = $add_new_call;
      update_option($configs['meta_keys']['courier_calls'], array_values($current_calls));
    }

    return $current_calls;
  }

  public static function remove_courier_calls( $call_id )
  {
    $configs = OmnivaLt_Core::get_configs();
    $current_calls = self::get_courier_calls();

    foreach ( $current_calls as $call_key => $call_values ) {
      if ( $call_values['id'] == $call_id ) {
        unset($current_calls[$call_key]);
      }
    }

    update_option($configs['meta_keys']['courier_calls'], array_values($current_calls));
  }

  /**
   * Add a message to the session so that admin notices can be created from it
   * It is recommended to use functions that work in all PHP versions in use
   * 
   * @param (string) $message - Notice text
   * @param (string) $type - Notice type
   * @param (string|boolean) $prefix - Bold text that will appear at the beginning of the notice
   * @param (boolean) $dismissible - Allow to disable notice display
   **/
  public static function add_msg( $message, $type, $prefix = false, $dismissible = false )
  {
    if ( ! session_id() ) {
      session_start();
    }
    if ( ! isset($_SESSION['omnivalt_notices']) ) {
      $_SESSION['omnivalt_notices'] = array();
    }
    $_SESSION['omnivalt_notices'][] = array('msg' => $message, 'type' => $type, 'prefix' => $prefix, 'dismissible' => $dismissible);
  }

  /**
   * Show all notices from the session
   * It is recommended to use functions that work in all PHP versions in use
   **/
  public static function show_notices()
  {
    if ( ! session_id() ) {
      session_start();
    }
    if ( is_array($_SESSION) && array_key_exists('omnivalt_notices', $_SESSION) ) {
      foreach ( $_SESSION['omnivalt_notices'] as $notice ) {
        $prefix = isset($notice['prefix']) ? $notice['prefix'] : false;
        $dismissible = isset($notice['dismissible']) ? $notice['dismissible'] : false;
        echo self::build_notice($notice['msg'], $notice['type'], $notice['prefix'], $notice['dismissible']);
      }
      unset( $_SESSION['omnivalt_notices'] );
    }
  }

  /**
   * Creating a notice block structure
   * It is recommended to use functions that work in all PHP versions in use
   * 
   * @param (string) $message - Notice text
   * @param (string) $type - Notice type
   * @param (string|boolean) $prefix - Bold text that will appear at the beginning of the notice
   * @param (boolean) $dismissible - Allow to disable notice display
   * @return (string) - Builded HTML block of notice
   **/
  public static function build_notice( $message, $type, $prefix = false, $dismissible = false )
  {
    $wp_notices = array('error', 'warning', 'success', 'info');
    $class = (in_array($type, $wp_notices)) ? 'notice notice-' . $type : $type;
    if ( $dismissible ) {
        $class .= ' is-dismissible';
    }
    if ( $prefix ) {
        $message = '<b>' . $prefix . ':</b> ' . $message;
    }

    return '<div class="' . $class . '"><p>' . $message . '</p></div>';
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
    $all_methods = OmnivaLt_Core::get_configs('method_params');

    foreach ( $all_methods as $method_key => $method_values ) {
      if ( $method_id == self::get_omniva_method_shipping_id($method_values['key']) ) {
        return true;
      }
    }

    return false;
  }

  public static function is_omniva_terminal_method( $method_id )
  {
    if ( ! self::is_omniva_method( $method_id ) ) {
      return false;
    }
    $method_key = OmnivaLt_Omniva_Order::get_method_key_from_id($method_id);

    return ($method_key == 'pt' || $method_key == 'ps');
  }

  public static function get_omniva_method_shipping_id( $key )
  {
    $found_key = $key;

    $all_methods = OmnivaLt_Core::get_configs('method_params');
    foreach ( $all_methods as $method_key => $method ) {
      if ( $key == $method_key || $key == $method['key'] ) {
        $found_key = 'omnivalt_' . $method['key'];
        break;
      }
    }

    return $found_key;
  }

  public static function get_omniva_method_by_key( $key )
  {
    $all_methods = OmnivaLt_Core::get_configs('method_params_new');
    foreach ( $all_methods as $method_key => $method ) {
      if ( $key == $method['key'] ) {
        return $method;
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

  public static function custom_tip( $text )
  {
    ob_start();
    ?>
    <div class="omnivalt-tip noselect">
      <span class="dashicons dashicons-info"></span>
      <span class="tip-text"><?php echo $text; ?></span>
    </div>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }

  public static function get_mobile_regex( $country )
  {
    $all_regex = array(
      'LT' => '/^(8|0|\+370)6\d{7}$/',
      'LV' => '/^\+3712\d{7}$/',
      'EE' => '/^\+372(5|8)\d{6,7}$/',
      'FI' => '/^(0|\+358)(4|5)\d{8}$/',
    );

    return $all_regex[$country] ?? '';
  }
}
