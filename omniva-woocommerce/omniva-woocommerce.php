<?php
/**
 * Plugin Name: Omniva shipping
 * Description: Official Omniva shipping plugin for WooCommerce
 * Author: Omniva
 * Author URI: https://www.omniva.lt/
 * Plugin URI: https://iskiepiai.omnivasiunta.lt/
 * Version: 1.10.1
 * Domain Path: /languages
 * Text Domain: omnivalt
 * Requires at least: 5.1
 * Tested up to: 5.9.1
 * WC requires at least: 3.0.0
 * WC tested up to: 6.3.0
 * Requires PHP: 7.2
 */

if (!defined('WPINC')) {
  die;
}

define('OMNIVALT_VERSION', '1.10.1');
define('OMNIVALT_CUSTOM_VERSION', false);
define('OMNIVALT_DIR', plugin_dir_path(__FILE__));
define('OMNIVALT_URL', plugin_dir_url(__FILE__));
define('OMNIVALT_BASENAME', plugin_basename(__FILE__));

function omnivalt_configs($section_name = false) {
  $params = array();

  /*
   * Shipping services sets
   */
  $params['shipping_sets'] = array(
    'baltic' => array(
      'pt pt' => 'PA',
      'pt c' => 'PK',
      'c pt' => 'PU',
      'c c' => 'QH',
      'courier_call' => 'QH',
    ),
    'estonia' => array(
      'pt pt' => 'PA',
      'pt po' => 'PO',
      'pt c' => 'PK',
      'c pt' => 'PU',
      'c c' => 'CI',
      'c cp' => 'LX', //not sure
      'po cp' => 'LH',
      'po pt' => 'PV',
      'po po' => 'CD',
      'po c' => 'CE',
      'lc pt' => 'PP',
      'courier_call' => 'CI',
    ),
    'finland' => array(
      'c pc' => 'QB', //QB in documentation
      'c po' => 'CD', //not sure
      'c cp' => 'CE', //not sure
      'courier_call' => 'CE',
    ),
  );

  /*
   * Every shipping method params. Array key is sender country. All bellow array fields is required.
   *
   * title - Country name
   * methods - Value of one of this: courier, courier_plus, pickup, post, private_customer
   * shipping_sets - Array of destination countries, other services and sets for them
   * comment_lang - Identifier for terminals map
   */
  $params['shipping_params'] = array(
    'LT' => array(
      'title' => __('Lithuania', 'omnivalt'),
      'methods' => array('pickup', 'courier'),
      'shipping_sets' => array(
        'LT' => 'baltic',
        'LV' => 'baltic',
        'EE' => 'baltic',
        'call' => 'baltic',
      ),
      'comment_lang' => 'lit',
      'tracking_url' => 'https://www.omniva.lt/verslo/siuntos_sekimas?barcode=',
    ),
    'LV' => array(
      'title' => __('Latvia', 'omnivalt'),
      'methods' => array('pickup', 'courier'),
      'shipping_sets' => array(
        'LT' => 'baltic',
        'LV' => 'baltic',
        'EE' => 'baltic',
        'call' => 'baltic',
      ),
      'comment_lang' => 'lav',
      'tracking_url' => 'https://www.omniva.lv/privats/sutijuma_atrasanas_vieta?barcode=',
    ),
    'EE' => array(
      'title' => __('Estonia', 'omnivalt'),
      'methods' => array('pickup', 'courier', 'courier_plus'),
      'shipping_sets' => array(
        'LT' => 'estonia',
        'LV' => 'estonia',
        'EE' => 'estonia',
        'FI' => 'finland',
        'call' => 'estonia',
      ),
      'comment_lang' => 'est',
      'tracking_url' => 'https://www.omniva.ee/era/jalgimine?barcode=',
    ),
    'FI' => array(
      'title' => __('Finland', 'omnivalt'),
      'methods' => array('courier_plus', 'private_customer'),
      'shipping_sets' => array(
        'LT' => 'estonia',
        'LV' => 'estonia',
        'EE' => 'estonia',
        'FI' => 'finland',
        'call' => 'estonia',
      ),
      'comment_lang' => '',
      'tracking_url' => '',
    ),
  );

  /*
   * Params for every shipping method
   *
   * Required values:
   * key (string) - Method key
   * title (string) - Method title
   * is_shipping_method (boolean) - If this method is shipping method. Using to exclude methods which using only in "send off" parameter.
   */
  $params['method_params'] = array(
    'terminal' => array(
      'key' => 'pt',
      'title' => __('Parcel terminal', 'omnivalt'),
      'is_shipping_method' => true,
      'description' => __('Activate this service, when you want to send parcels to parcel terminals.', 'omnivalt'),
      'sizes' => array(
        'min' => array(2, 9, 14),
        'S' => array(9, 38, 64),
        'M' => array(19, 38, 64),
        'L' => array(39, 38, 64),
      ),
      'titles' => array(
        'S' => _x('Small', 'Box size', 'omnivalt'),
        'M' => _x('Medium', 'Box size', 'omnivalt'),
        'L' => _x('Large', 'Box size', 'omnivalt'),
      ),
      'weight' => array(
        'default' => 30,
      ),
    ),
    'courier' => array(
      'key' => 'c',
      'title' => __('Courier Baltic', 'omnivalt'),
      'is_shipping_method' => true,
      'description' => __('Activate this service, when you want to send parcels within Latvia and Lithuania.', 'omnivalt'),
      'weight' => array(
        'default' => 100,
      ),
    ),
    'courier_plus' => array(
      'key' => 'cp',
      'title' => __('Courier', 'omnivalt'),
      'is_shipping_method' => true,
      'description' => __('Activate this service, when your e-shop customers would like to receive parcels in Estonia.', 'omnivalt') . '  ' . __('Available for Estonian customers only.', 'omnivalt'),
      'weight' => array(
        'default' => 100,
      ),
    ),
    'private_customer' => array(
      'key' => 'pc',
      'title' => __('Courier Finland', 'omnivalt'),
      'is_shipping_method' => true,
      'description' => __('Activate this service, when you want to send parcels to private persons in Finland.', 'omnivalt')  . '  ' . __('Available for Estonian customers only.', 'omnivalt'),
      'weight' => array(
        'default' => 100,
      ),
    ),
    'post' => array(
      'key' => 'po',
      'title' => __('Post office', 'omnivalt'),
      'is_shipping_method' => true,
      'description' => __('Activate this service, when you want to send parcels to post offices.', 'omnivalt'),
      'weight' => array(
        'default' => 100,
      ),
    ),
    'logistic' => array(
      'key' => 'lc',
      'title' => __('Logistics center', 'omnivalt'),
      'is_shipping_method' => false,
    ),
  );

  /*
   * Additional services
   *
   * title (string) - Service title
   * code (string) - Service code
   * only_for (string / array) - Use this service only for listed in array shipping services. If value is 'all', then add service always
   * in_product (string / boolean) - Service option type in Product edit page. If not use, then false.
   * in_order (string / boolean) - Service option type in Order edit page. If not use, then false.
   * add_always (boolean) - Add always this service to labels
   * desc_product (string) - Parameter desription in Product edit page.
   *
   * Available service types: checkbox.
   */
  $params['additional_services'] = array(
    'arrival_sms' => array(
      'title' => __('Arrival SMS', 'omnivalt'),
      'code' => 'ST',
      'only_for' => array('PA', 'PU', 'PP', 'PO', 'PV', 'CD', 'CE', 'LX', 'LH'),
      'in_product' => false,
      'in_order' => false,
      'add_always' => true,
    ),
    'arrival_email' => array(
      'title' => __('Arrival email', 'omnivalt'),
      'code' => 'SF',
      'only_for' => array('PA', 'PU', 'PP', 'PO', 'PV', 'CD', 'CE', 'LX', 'LH'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'fragile' => array(
      'title' => __('Fragile', 'omnivalt'),
      'code' => 'BC',
      'only_for' => 'all',
      'in_product' => 'checkbox',
      'in_order' => 'checkbox',
      'add_always' => false,
      'desc_product' => __('If this item will be added to the shipment, mark that shipment as fragile', 'omnivalt'),
    ),
    'private_customer' => array(
      'title' => __('Delivery to private customer', 'omnivalt'),
      'code' => 'CL',
      'only_for' => array('CI'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'doc_return' => array(
      'title' => __('Document return', 'omnivalt'),
      'code' => 'XT',
      'only_for' => array('LA', 'LE', 'LZ', 'LG', 'LX', 'LH', 'CI', 'QK', 'QP', 'LL', 'CE', 'CD', 'CB', 'QH', 'QL'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'paid_by_receiver' => array(
      'title' => __('Paid by receiver', 'omnivalt'),
      'code' => 'BS',
      'only_for' => array('LX', 'LH'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'insurance' => array(
      'title' => __('Insurance', 'omnivalt'),
      'code' => 'BI',
      'only_for' => array('LX', 'LH', 'QB', 'CE', 'CD'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'personal_delivery' => array(
      'title' => __('Personal delivery', 'omnivalt'),
      'code' => 'BK',
      'only_for' => array('LX', 'LH', 'CE', 'CD'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'paid_parcel_sms' => array(
      'title' => __('Paid parcel SMS', 'omnivalt'),
      'code' => 'GN',
      'only_for' => array('CE', 'CD'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'paid_parcel_email' => array(
      'title' => __('Paid parcel email', 'omnivalt'),
      'code' => 'GM',
      'only_for' => array('CE', 'CD'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'return_notification_sms' => array(
      'title' => __('Return notification SMS', 'omnivalt'),
      'code' => 'SB',
      'only_for' => array('CE', 'CD', 'LX', 'LH'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'return_notification_email' => array(
      'title' => __('Return notification email', 'omnivalt'),
      'code' => 'SG',
      'only_for' => array('CE', 'CD', 'LX', 'LH'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'persons_over_18' => array(
      'title' => __('Issue to persons at the age of 18+', 'omnivalt'),
      'code' => 'PC',
      'only_for' => array('CE', 'CD'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'delivery_confirmation_sms' => array(
      'title' => __('Delivery confirmation SMS to sender', 'omnivalt'),
      'code' => 'SS',
      'only_for' => array('LX', 'LH'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
    'delivery_confirmation_email' => array(
      'title' => __('Delivery confirmation e-mail to sender', 'omnivalt'),
      'code' => 'SE',
      'only_for' => array('LX', 'LH'),
      'in_product' => false,
      'in_order' => 'checkbox',
      'add_always' => false,
    ),
  );

  /*
   * Post offices and terminals params
   */
  $params['locations'] = array(
    'source_url' => 'https://www.omniva.ee/locations.json',
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
require_once OMNIVALT_DIR . 'includes/class-core.php';
$omnivalt_core = new OmnivaLt_Core();
$omnivalt_core->init();
