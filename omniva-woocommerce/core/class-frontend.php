<?php
class OmnivaLt_Frontend
{
  public static function add_logo_to_method($label, $method)
  {
    $settings = OmnivaLt_Core::get_settings();
    $image = '';

    if ( isset($settings['show_logo']) && $settings['show_logo'] === 'yes' ) {
      $image = '<img src="' . OMNIVALT_URL . 'assets/img/logos/omniva_horizontal_s.png" alt="Omniva"/>';
    }

    if ( $method->method_id === 'omnivalt' ) {
      $label = $image . $label;
    }
    
    return $label;
  }

  public static function change_methods_position($rates, $package)
  {
    if ( ! $rates ) return $rates;

    $settings = OmnivaLt_Core::get_settings();
    $replaced_key = "_%02d";

    if ( empty($settings['position']) ) return $rates;
    if ( empty(json_decode($settings['position'])) ) return $rates;

    $new_rates = array();
    $positions = json_decode($settings['position'], true);
    asort($positions);
    $new_positions = array();

    foreach ( $positions as $position_key => $position ) {
      $rate_key = 'omnivalt_' . $position_key;
      if ( isset($rates[$rate_key]) && ! empty($position) ) {
        $position = ($position > 0) ? $position - 1 : 0;
        while ( isset($new_positions[sprintf($replaced_key, $position)]) ) {
          $position++;
        }
        $position = sprintf($replaced_key, $position);
        $new_positions[$position] = $rate_key;
      }
    }

    $current_position = 0;
    foreach ( $rates as $rate_key => $rate ) {
      if ( ! in_array($rate_key, $new_positions) ) {
        for ( $i = 0; $i <= $current_position + 1; $i++) {
          if ( ! isset($new_positions[sprintf($replaced_key, $i)]) ) {
            $new_positions[sprintf($replaced_key, $i)] = $rate_key;
            break;
          }
        }
      }
      $current_position++;
    }
    ksort($new_positions);

    foreach ( $new_positions as $rate_key ) {
      $new_rates[$rate_key] = $rates[$rate_key];
    }

    return $new_rates;
  }
}
