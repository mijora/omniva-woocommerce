<?php
class OmnivaLt_Shipmethod_Helper
{
  public static function get_current_method_params($all_methods_params, $method_key)
  {
    $method_params = array();
    foreach ( $all_methods_params as $method ) {
      if ( $method['key'] === $method_key ) {
        $method_params = $method;
        break;
      }
    }

    return $method_params;
  }

  public static function get_available_shipping_methods($configs)
  {
    $available_methods = array();

    foreach ( $configs['method_params'] as $method_name => $method_values ) {
      if ($method_values['is_shipping_method'] === false) continue;

      $available = false;
      foreach ( $configs['shipping_params'] as $ship_params ) {
        $method_key = ($method_name === 'terminal') ? 'pickup' : $method_name;
        if ( in_array($method_key, $ship_params['methods']) ) {
          $available = true;
        }
      }
      if ( ! $available ) continue;

      $available_methods[$method_name] = $method_values;
    }

    return $available_methods;
  }

  public static function check_restrictions($settings, $key, $weight = false, $products_for_dim = false)
  {
    $settings_keys = array(
      'weight' => ($key === 'pt') ? 'weight' : 'weight_' . $key,
      'size' => 'size_' . $key,
    );

    if ( $weight && isset($settings[$settings_keys['weight']]) ) {
      $max_weight = $settings[$settings_keys['weight']];
      $pass = self::check_weight($weight, $max_weight);
      if ( ! $pass ) {
        return false;
      }
    }

    if ( $products_for_dim ) {
      $max_dimension = (isset($settings[$settings_keys['size']])) ? json_decode($settings[$settings_keys['size']]) : array(999999,999999,999999);
      $pass = self::check_dimension($products_for_dim, $max_dimension);
      if ( ! $pass ) {
        return false;
      }
    }

    return true;
  }

  public static function get_rate_name($method, $country, $settings)
  {
    $rate_name = $method['front_title'];
    $prefix = $method['prefix'];
    
    if ( isset($method['display_by_country'][$country]) ) {
      $rate_name = $method['display_by_country'][$country]['front_title'];
      $prefix = $method['display_by_country'][$country]['prefix'];
    }

    $show_prefix_on = array('classic', 'full');
    if ( ! isset($settings['label_design']) || (isset($settings['label_design']) && in_array($settings['label_design'], $show_prefix_on)) ) {
      $rate_name = $prefix . ' ' . strtolower($rate_name);
    }

    if ( ! empty($method['fields']['label']) ) {
      $rate_name = $method['fields']['label'];
    }

    return $rate_name;
  }

  public static function is_rate_allowed($key, $country, $settings) {
    $shipping_params = OmnivaLt_Core::get_configs('shipping_params');
    $asociations = OmnivaLt_Helper::get_methods_asociations();
    $available_methods = OmnivaLt_Helper::get_available_methods();
    
    if ( ! isset($shipping_params[$settings['api_country']]) ) {
      return false;
    }

    $shipping_sets = $shipping_params[$settings['api_country']]['shipping_sets'];
    if ( ! isset($shipping_sets[$country]) ) {
      return false;
    }

    $methods = OmnivaLt_Helper::get_allowed_methods($shipping_sets[$country]);
    if ( empty($methods) ) {
      return false;
    }

    $allowed_methods = $available_methods[$country]['available_methods'];
    foreach ( $allowed_methods as $method_key => $method ) {
      $allowed_methods[$method_key] = OmnivaLt_Helper::convert_method_name_to_short($asociations, $method);
    }
    foreach ( $methods as $method_key => $method ) {
      $method = OmnivaLt_Helper::convert_method_name_to_short($asociations, $method, true);
    }

    if ( ! in_array($key, $methods) || ! in_array($key, $allowed_methods) ) {
      return false;
    }

    return true;
  }

  private static function check_weight($weight, $max_value)
  {
    return (floatval($max_value) >= $weight || floatval($max_value) == 0);
  }

  private static function check_dimension($products_for_dim, $max_dimension)
  {   
    if ( (isset($max_dimension[0]) && ! empty($max_dimension[0]))
      || (isset($max_dimension[1]) && ! empty($max_dimension[1]))
      || (isset($max_dimension[2]) && ! empty($max_dimension[2])) )
    {
        return self::cart_size_prediction($products_for_dim, $max_dimension);
    }

    return true;
  }

  private static function cart_size_prediction($products, $max_dimension)
  {
    $all_cart_dim_length = 0;
    $all_cart_dim_width = 0;
    $all_cart_dim_height = 0;
    $max_dim_length = (!empty($max_dimension[0])) ? $max_dimension[0] : 999999;
    $max_dim_width = (!empty($max_dimension[1])) ? $max_dimension[1] : 999999;
    $max_dim_height = (!empty($max_dimension[2])) ? $max_dimension[2] : 999999;

    foreach ($products as $product) {
      $prod_dim_length = (!empty($product->get_length())) ? $product->get_length() : 0;
      $prod_dim_width = (!empty($product->get_width())) ? $product->get_width() : 0;
      $prod_dim_height = (!empty($product->get_height())) ? $product->get_height() : 0;

      //Add to length
      if ( ($prod_dim_length + $all_cart_dim_length) <= $max_dim_length 
        && $prod_dim_width <= $max_dim_width && $prod_dim_height <= $max_dim_height )
      {
        $all_cart_dim_length = $all_cart_dim_length + $prod_dim_length;
        $all_cart_dim_width = ($prod_dim_width > $all_cart_dim_width) ? $prod_dim_width : $all_cart_dim_width;
        $all_cart_dim_height = ($prod_dim_height > $all_cart_dim_height) ? $prod_dim_height : $all_cart_dim_height;
      }
      //Add to width
      else if ( ($prod_dim_width + $all_cart_dim_width) <= $max_dim_width 
        && $prod_dim_length <= $max_dim_length && $prod_dim_height <= $max_dim_height )
      {
        $all_cart_dim_length = ($prod_dim_length > $all_cart_dim_length) ? $prod_dim_length : $all_cart_dim_length;
        $all_cart_dim_width = $all_cart_dim_width + $prod_dim_width;
        $all_cart_dim_height = ($prod_dim_height > $all_cart_dim_height) ? $prod_dim_height : $all_cart_dim_height;
      }
      //Add to height
      else if ( ($prod_dim_height + $all_cart_dim_height) <= $max_dim_height 
        && $prod_dim_length <= $max_dim_length && $prod_dim_width <= $max_dim_width )
      {
        $all_cart_dim_length = ($prod_dim_length > $all_cart_dim_length) ? $prod_dim_length : $all_cart_dim_length;
        $all_cart_dim_width = ($prod_dim_width > $all_cart_dim_width) ? $prod_dim_width : $all_cart_dim_width;
        $all_cart_dim_height = $all_cart_dim_height + $prod_dim_height;
      }
      //If all fails
      else {
        return false;
      }
    }
    
    return true;
  }

  public static function check_omniva_box_size()
  {
    OmnivaLt_Core::add_required_directories();

    // Check if all cart items have all dimensions
    $dimensions_present = true;
    $cart_items = self::get_cart_items_dimensions();
    foreach ( $cart_items as $cart_item ) {
      foreach ( $cart_item as $cart_item_value ) {
        if ( empty($cart_item_value) ) {
          file_put_contents(OMNIVALT_DIR . 'var/logs/boxsize.log', PHP_EOL . date('Y-m-d H:i:s') . ' BAD DIMMENSIONS ' . json_encode($cart_item) . PHP_EOL, FILE_APPEND);
          $dimensions_present = false;
          break;
        }
      }
    }
    if ( ! $dimensions_present ) {
      return false;
    }

    // Pack
    $arranged_cart_items = self::arrange_cart_items($cart_items);
    $packer = new OmnivaLt_Packer($arranged_cart_items);
    $box_size = $packer->pack();

    if ( ! $box_size ) {
      file_put_contents(OMNIVALT_DIR . 'var/logs/boxsize.log', PHP_EOL . date('Y-m-d H:i:s') . ' NO BOX TO FIT. CART ITEMS DIMMENSIONS: ' . json_encode($cart_items) . PHP_EOL, FILE_APPEND);
    }

    return $box_size;
  }

  private static function get_cart_items_dimensions()
  {
    $items_dimensions = [];
    $dimension_unit = get_option( 'woocommerce_dimension_unit' );

    // Get rate
    switch ($dimension_unit) {
      case 'mm':
        $rate = 1;
        break;
      case 'cm':
        $rate = pow(10, 1);
        break;
      case 'm':
        $rate = pow(10, 2);
        break;
      default:
        $rate = null;
    }

    foreach(WC()->cart->get_cart() as $cart_item) {
      $product = $cart_item['data'];
      $qty     = $cart_item['quantity'];

      $length = floatval($product->get_length()) * $rate;
      $width = floatval($product->get_width()) * $rate;
      $height = floatval($product->get_height()) * $rate;

      $items_dimensions[] = [
        'product_id' => $product->get_id(),
        'product_name' => $product->get_name(),
        'length'    => $length,
        'width'     => $width,
        'height'    => $height,
        'volume'    => $length * $height * $width,
        'qty'       => $qty,
      ];
    }

    // Sort by largest first
    usort($items_dimensions, function($a, $b) {
      $a = $a['volume'];
      $b = $b['volume'];

      if ($a === $b) {
        return 0;
      }

      return ($a < $b) ? -1 : 1;
    });

    return $items_dimensions;
  }

  private static function arrange_cart_items($cart_items)
  {
    $arranged_cart_items = [];

    foreach ($cart_items as $cart_item) {
      if ($cart_item['qty'] > 1) {
        for($i = 0; $i < $cart_item['qty']; $i++) {
          $arranged_cart_items[] = [
            'length' => $cart_item['length'],
            'width'  => $cart_item['width'],
            'height' => $cart_item['height'],
          ];
        }
      } else {
        $arranged_cart_items[] = [
          'length' => $cart_item['length'],
          'width'  => $cart_item['width'],
          'height' => $cart_item['height'],
        ];
      }
    }

    return $arranged_cart_items;
  }
}
