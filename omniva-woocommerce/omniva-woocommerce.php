<?php
/**
 * Plugin Name: Omniva shipping
 * Description: Official Omniva shipping plugin for WooCommerce
 * Author: Omniva
 * Author URI: https://www.omniva.lt/
 * Plugin URI: https://iskiepiai.omnivasiunta.lt/
 * Version: 1.20.4
 * Domain Path: /languages
 * Text Domain: omnivalt
 * 
 * Requires at least: 5.1
 * Tested up to: 6.8
 * WC requires at least: 6.0.0
 * WC tested up to: 9.8.1
 * Requires PHP: 7.2
 * PHP tested up to: 8.1.13
 */

if (!defined('WPINC')) {
  die;
}

define('OMNIVALT_VERSION', '1.20.4');
define('OMNIVALT_DIR', plugin_dir_path(__FILE__));
define('OMNIVALT_URL', plugin_dir_url(__FILE__));
define('OMNIVALT_BASENAME', plugin_basename(__FILE__));
define('OMNIVALT_CUSTOM_CHANGES', array()); //If plugin have custom changes, add changes descriptions to this array. When are values in this array, in the plugins list will display a warning message about custom changes in the plugin and will be listed the values written here

function omnivalt_configs($section_name = false) {
  $params = array();

  /**
   * Available methods by API country value for each country
   * 
   * Structure:
   * 'API_country' => array('delivery_country' => array('methods'))
   */
  $params['available_methods'] = array(
    'LT' => array(
      'LT' => array('pickup', 'courier'),
      'LV' => array('pickup', 'courier'),
      'EE' => array('pickup', 'courier'),
      'FI' => array('pickup'),
    ),
    'LV' => array(
      'LT' => array('pickup', 'courier'),
      'LV' => array('pickup', 'courier'),
      'EE' => array('pickup', 'courier'),
      'FI' => array('pickup'),
    ),
    'EE' => array(
      'LT' => array('pickup', 'courier'),
      'LV' => array('pickup', 'courier'),
      'EE' => array('pickup', 'courier', 'courier_plus', 'post_near', 'post_specific', 'letter_courier', 'letter_post'),
      'FI' => array('pickup', 'courier_plus', 'private_customer'),
    )
  );

  /*
   * Every shipping method params. Array key is sender country. All bellow array fields is required.
   *
   * title - Country name
   * methods - Value of one of this: courier, courier_plus, pickup, post_near, private_customer //TODO: Need to make it take the value from available_methods instead
   * shipping_sets - Array of destination countries, other services and sets for them
   * comment_lang - Identifier for terminals map
   */
  $params['shipping_params'] = array(
    'LT' => array(
      'type' => 'country',
      'methods' => array('pickup', 'courier'),
      'shipping_sets' => array(
        'LT' => 'baltic',
        'LV' => 'baltic',
        'EE' => 'baltic',
        'FI' => 'finland',
        'call' => 'baltic',
      ),
      'comment_lang' => 'lit',
      'tracking_url' => 'https://mano.omniva.lt/track/',
    ),
    'LV' => array(
      'type' => 'country',
      'methods' => array('pickup', 'courier'),
      'shipping_sets' => array(
        'LT' => 'baltic',
        'LV' => 'baltic',
        'EE' => 'baltic',
        'FI' => 'finland',
        'call' => 'baltic',
      ),
      'comment_lang' => 'lav',
      'tracking_url' => 'https://mana.omniva.lv/track/',
    ),
    'EE' => array(
      'type' => 'country',
      'methods' => array('pickup', 'courier', 'courier_plus', 'post_near', 'post_specific', 'letter_courier', 'letter_post'),
      'shipping_sets' => array(
        'LT' => 'estonia',
        'LV' => 'estonia',
        'EE' => 'estonia',
        'FI' => 'finland',
        'call' => 'estonia',
      ),
      'comment_lang' => 'est',
      'tracking_url' => 'https://minu.omniva.ee/track/',
    ),
    'FI' => array(
      'type' => 'country',
      'methods' => array('pickup', 'courier_plus', 'private_customer'),
      'shipping_sets' => array(
        'LT' => 'estonia',
        'LV' => 'estonia',
        'EE' => 'estonia',
        'FI' => 'finland',
        'call' => 'estonia',
      ),
      'comment_lang' => 'eng',
      'tracking_url' => 'https://minu.omniva.ee/track/',
    ),
  );

  /**
   * Params for API requests
   */
  $params['api'] = array(
    'type' => 'omx',
  );

  /*
   * List of Cash of delivery payment methods key
   */
  $params['cod'] = array('cod');

  /*
   * Post offices and terminals params
   */
  $params['locations'] = array(
    'source_url' => 'https://www.omniva.ee/locationsfull.json',
  );

  /*
   * Params for update
   */
  $params['update'] = array(
    'check_url' => 'https://api.github.com/repos/mijora/omniva-woocommerce/releases/latest',
    'download_url' => 'https://github.com/mijora/omniva-woocommerce/releases/latest/download/omniva-woocommerce.zip',
  );

  /*
   * Variables, which using to replace some value in string. Using like {variable_key}.
   */
  $params['text_variables'] = array(
    'order_id' => __('Order ID', 'omnivalt'),
    'order_number' => __('Order number', 'omnivalt'),
  );

  $params['meta_keys'] = array(
    'manifest_date_old' => '_manifest_generation_date',
  );

  /*
   * Debug params
   *
   * delete_after (integer) - The number of days after which to delete old debug files
   */
  $params['debug'] = array(
    'delete_after' => 30,
  );

  /*
   * Returns
   */
  if (!empty($section_name) && isset($params[$section_name])) {
    return $params[$section_name];
  }

  return $params;
}

/**
 * Plugin loading
 */
require_once OMNIVALT_DIR . 'core/class-core.php';
$omnivalt_core = new OmnivaLt_Core();
$omnivalt_core->init();
