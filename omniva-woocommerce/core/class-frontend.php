<?php
class OmnivaLt_Frontend
{
  public static function add_logo_to_method($label, $method)
  {
    if ( $method->method_id != 'omnivalt' ) {
      return $label;
    }

    $settings = OmnivaLt_Core::get_settings();
    $image = '';

    $label_design = $settings['label_design'] ?? 'classic';
    if ( $label_design == 'full' || $label_design == 'logo' ) {
      $method_key = OmnivaLt_Omniva_Order::get_method_key_from_id($method->id);
      $method_params = OmnivaLt_Helper::get_omniva_method_by_key($method_key);
      if ( ! $method_params ) {
        return $label;
      }
      $image_file = $method_params['title_logo'];
      $country = OmnivaLt_Wc::get_customer_from_global()->get_shipping_country();
      if ( isset($method_params['display_by_country'][$country]) ) {
        $image_file = $method_params['display_by_country'][$country]['title_logo'];
      }
      $image = '<img class="omnivalt-logo" src="' . OMNIVALT_URL . 'assets/img/logos/' . $image_file . '" alt="Omniva"/>';
    }
    
    return $label = $image . $label;;
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

  public static function change_payment_list_by_shipping_method( $available_gateways )
  {
    /*** Initiation ***/
    if ( is_admin() ||  OmnivaLt_Wc::is_endpoint_url('order-pay') ) {
      return $available_gateways;
    }

    $customer_data = OmnivaLt_Wc::get_customer_from_global();
    if ( empty($customer_data) ) {
      return $available_gateways;
    }

    $chosen_shipping_methods = (array) OmnivaLt_Wc::get_session('chosen_shipping_methods');
    $chosen_country = ($customer_data->get_shipping_country()) ? $customer_data->get_shipping_country() : $customer_data->get_billing_country();

    if ( empty($chosen_shipping_methods) || empty($chosen_country) ) {
      return $available_gateways;
    }

    $omniva_methods_ids = array(
      'terminal' => OmnivaLt_Helper::get_omniva_method_shipping_id('terminal'),
    );

    /*** Payment methods changing ***/
    /* Disable COD for FI Matkahulto */
    if ( $chosen_country == 'FI' && in_array($omniva_methods_ids['terminal'], $chosen_shipping_methods) ) {
      $disable_payment_methods = array('cod');
      
      foreach ( $disable_payment_methods as $payment_method_key ) {
        unset($available_gateways[$payment_method_key]);
      }
    }

    /*** Output ***/
    return $available_gateways;
  }
}
