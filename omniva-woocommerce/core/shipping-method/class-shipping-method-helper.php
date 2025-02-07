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
      $pass = self::check_dimension($products_for_dim, array(
        'width' => (!empty($max_dimension[1])) ? $max_dimension[1] : 999999,
        'height' => (!empty($max_dimension[2])) ? $max_dimension[2] : 999999,
        'length' => (!empty($max_dimension[0])) ? $max_dimension[0] : 999999
      ));
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

  public static function is_rate_allowed($key, $country) {
    $available_methods = OmnivaLt_Method::get_all_available_shipping_methods();

    if ( ! isset($available_methods[$country]) ) {
      return false;
    }

    $method_exists = false;
    foreach ( $available_methods[$country] as $method ) {
      if ( $key === $method['key'] ) {
        $method_exists = true;
      }
    }
    if ( ! $method_exists ) {
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
    $products_measurements = OmnivaLt_Helper::get_products_measurements_list($products_for_dim);
    $box_size = OmnivaLt_Helper::predict_order_size($products_measurements, $max_dimension);

    return ($box_size) ? true : false;
  }

  public static function get_omniva_box_sizes( $convert_unit = true )
  {
    $box_sizes = array( // All values in cm
      'S' => array('length' => 64,'width' => 38, 'height' => 9),
      'M' => array('length' => 64,'width' => 38, 'height' => 19),
      'L' => array('length' => 64,'width' => 38, 'height' => 39),
    );

    if ( $convert_unit ) {
      $units = OmnivaLt_Wc::get_units();
      foreach ( $box_sizes as $box => $sizes ) {
        foreach ( $sizes as $size => $value ) {
          $box_sizes[$box][$size] = OmnivaLt_Wc::get_dimension($value, $units->dimension, 'cm');
        }
      }
    }

    return $box_sizes;
  }

  public static function check_omniva_box_size()
  {
    $cart = OmnivaLt_Wc::get_cart();
    if ( empty($cart) || empty($cart->cart_contents) ) {
      return false;
    }
    $cart_items = self::get_splited_cart_products($cart->cart_contents);
    $products_measurements = OmnivaLt_Helper::get_products_measurements_list($cart_items);

    $box_size = false;
    foreach ( self::get_omniva_box_sizes() as $key => $values ) {
      $result = OmnivaLt_Helper::predict_order_size($products_measurements, $values);
      if ( $result ) {
        $box_size = $key;
        break;
      }
    }

    return $box_size;
  }

  public static function get_splited_cart_products( $cart_contents )
  {
    $products = array();

    foreach ( $cart_contents as $item_id => $values ) {
      $product = $values['data'];
      for ( $i = 0; $i < $values['quantity']; $i++ ) {
        array_push($products, $product);
      }
    }

    return $products;
  }
}
