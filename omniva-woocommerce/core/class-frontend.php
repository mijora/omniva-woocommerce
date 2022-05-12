<?php
class OmnivaLt_Frontend
{
  public static function add_logo_to_method($label, $method)
  {
    $settings = OmnivaLt_Core::get_settings();
    $image = '';

    if ( isset($settings['show_logo']) && $settings['show_logo'] === 'yes' ) {
      $image = '<img src="' . OMNIVALT_URL . 'assets/img/omniva_logo_s.png" alt="Omniva"/>';
    }

    if ( $method->method_id === 'omnivalt' ) {
      $label = $image . $label;
    }
    
    return $label;
  }
}
