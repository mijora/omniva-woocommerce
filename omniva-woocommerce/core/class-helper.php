<?php
use Mijora\BoxCalculator\Elements\Item as BoxCalcItem;
use Mijora\BoxCalculator\CalculateBox as BoxCalcCalculate;

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

  public static function get_api_plan()
  {
    $settings = OmnivaLt_Core::get_settings();
    return (! empty($settings['api_country'])) ? $settings['api_country'] : 'LT';
  }

  public static function get_shipping_methods_prices()
  {
    $settings = OmnivaLt_Core::get_settings();
    $shipping_params = OmnivaLt_Core::get_configs('shipping_params');
    $shipping_countries = array_keys($shipping_params);
    $shipping_prices = array();

    foreach ( $shipping_countries as $country ) {
      $prices_key = (array_key_exists($country, $shipping_params)) ? 'prices_' . $country : 'prices_LT';
      $prices_data = (isset($settings[$prices_key])) ? json_decode($settings[$prices_key]) : array();
      
      $prices = array();
      foreach ( $shipping_params[$country]['methods'] as $method_name ) {
        $method = OmnivaLt_Method::get_by_key($method_name, true);
        $method_key = ($method) ? $method['key'] : '';
        $prices[$method_name] = self::parse_shipping_methods_prices($method_key, $prices_data);
      }
      $shipping_prices[$country] = $prices;
    }

    return $shipping_prices;
  }

  private static function parse_shipping_methods_prices( $method_key, $prices_data )
  {
    $keys = array(
      'single' => $method_key . '_price_single',
      'type' => $method_key . '_price_type',
      'weight' => $method_key . '_price_by_weight',
      'amount' => $method_key . '_price_by_amount',
      'boxsize' => $method_key . '_price_by_boxsize',
    );
    $settings = OmnivaLt_Core::get_settings();

    $type = 'unknown';
    $enabled = (isset($prices_data->{$method_key . '_enable'})) ? (bool) $prices_data->{$method_key . '_enable'} : false;
    if ( isset($settings['method_' . $method_key]) && $settings['method_' . $method_key] !== 'yes' ) {
      $enabled = false;
    }
    $prices = (isset($prices_data->{$keys['single']})) ? $prices_data->{$keys['single']} : '';

    if ( isset($prices_data->{$keys['type']}) ) {
      $type = $prices_data->{$keys['type']};
      
      if ( $prices_data->{$keys['type']} == 'weight' && isset($prices_data->{$keys['weight']}) ) {
        $prices = array();
        $from = 0;
        foreach ( $prices_data->{$keys['weight']} as $weight_prices ) {
          $prices[] = array(
            'from' => (string) $from,
            'to' => $weight_prices->value,
            'price' => $weight_prices->price
          );
          if ( $weight_prices->value !== '' ) {
            $from = $weight_prices->value + 0.001;
          }
        }
      }

      if ( $prices_data->{$keys['type']} == 'amount' && isset($prices_data->{$keys['amount']}) ) {
        $prices = array();
        $from = 0;
        foreach ( $prices_data->{$keys['amount']} as $amount_prices ) {
          $prices[] = array(
            'from' => $from,
            'to' => $amount_prices->value,
            'price' => $amount_prices->price
          );
          if ( $amount_prices->value !== '' ) {
            $from = $amount_prices->value + 0.01;
          }
        }
      }

      if ( $prices_data->{$keys['type']} == 'boxsize' && isset($prices_data->{$keys['boxsize']}) ) {
        $prices = array();
        foreach ( $prices_data->{$keys['boxsize']} as $amount_prices ) {
          $prices[] = array(
            'from' => $amount_prices->value,
            'to' => '',
            'price' => $amount_prices->price
          );
        }
      }

    }

    return array(
      'type' => $type,
      'enabled' => $enabled,
      'prices' => $prices
    );
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
    $new_message_data = array('msg' => $message, 'type' => $type, 'prefix' => $prefix, 'dismissible' => $dismissible);
    $message_exists = false;
    foreach ( $_SESSION['omnivalt_notices'] as $notice ) {
      if ( $notice['msg'] === $new_message_data['msg'] && $notice['type'] === $new_message_data['type'] ) {
        $message_exists = true;
      }
    }
    if ( ! $message_exists ) {
      $_SESSION['omnivalt_notices'][] = array('msg' => $message, 'type' => $type, 'prefix' => $prefix, 'dismissible' => $dismissible);
    }
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

  public static function get_shipping_set( $sender_country, $receiver_country )
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

  public static function get_products_measurements_list( $products )
  {
    $products_measurements = array();
    if ( ! is_array($products) ) {
      return $products_measurements;
    }

    foreach ( $products as $product ) {
      $product_data = array(
        'length' => 0,
        'width' => 0,
        'height' => 0
      );
      if ( is_object($product) ) {
        $product_data['length'] = (!empty($product->get_length())) ? $product->get_length() : $product_data['length'];
        $product_data['width'] = (!empty($product->get_width())) ? $product->get_width() : $product_data['width'];
        $product_data['height'] = (!empty($product->get_height())) ? $product->get_height() : $product_data['height'];
      } else if ( is_array($product) ) {
        $product_data['length'] = (!empty($product['length'])) ? $product['length'] : $product_data['length'];
        $product_data['width'] = (!empty($product['width'])) ? $product['width'] : $product_data['width'];
        $product_data['height'] = (!empty($product['height'])) ? $product['height'] : $product_data['height'];
      }
      $products_measurements[] = $product_data;
    }

    return $products_measurements;
  }

  public static function predict_order_size( $products_measurements, $max_dimension = array() )
  {
    $items_list = array();
    foreach ( $products_measurements as $prod ) {
      $items_list[] = new BoxCalcItem($prod['width'], $prod['height'], $prod['length']);
    }

    $box_calculator = new BoxCalcCalculate($items_list);
    $box_calculator->setBoxWallThickness(0);
    $box_calculator->enableDebug(true);

    if ( ! empty($max_dimension) ) {
      $box_calculator->setMaxBoxSize(
        (!empty($max_dimension['width'])) ? $max_dimension['width'] : 999999,
        (!empty($max_dimension['height'])) ? $max_dimension['height'] : 999999,
        (!empty($max_dimension['length'])) ? $max_dimension['length'] : 999999
      );
      $box_size = $box_calculator->findBoxSizeUntilMaxSize();
    } else {
      $box_size = $box_calculator->findMinBoxSize();
    }

    return (! $box_size) ? false : array('length' => $box_size->getLength(), 'width' => $box_size->getWidth(), 'height' => $box_size->getHeight());
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

  public static function convert_unit( $value, $new_unit, $current_unit = false, $unit_type = 'weight' )
  {
    $woo_units = OmnivaLt_Wc::get_units(false);
    if ( ! isset($woo_units[$unit_type]) ) {
      return $value;
    }

    switch ($unit_type) {
      case 'weight':
        if ( ! $current_unit ) {
          $current_unit = $woo_units['weight'];
        }
        return OmnivaLt_Wc::get_weight($value, $new_unit, $current_unit);
        break;
      case 'dimension':
        if ( ! $current_unit ) {
          $current_unit = $woo_units['dimension'];
        }
        return OmnivaLt_Wc::get_dimension($value, $new_unit, $current_unit);
        break;
    }

    return $value;
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

  public static function get_local_timezone_string()
  {
    $timezone_string = get_option('timezone_string');
    if ( ! empty( $timezone_string ) ) {
      return $timezone_string;
    }
    $offset = get_option('gmt_offset');
    $hours = (int) $offset;

    return timezone_name_from_abbr("", $hours * 3600, true);
  }

  public static function get_timezone_offset( $timezone_string )
  {
    $date = new DateTime('now', new DateTimeZone($timezone_string));
    return $date->format('P');
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
