<?php
/**
 * Plugin Name: Omniva shipping
 * Description: Official Omniva shipping plugin for WooCommerce
 * Author: Omniva
 * Author URI: https://www.omniva.lt/
 * Plugin URI: https://iskiepiai.omnivasiunta.lt/
 * Version: 1.8.4-ee
 * Domain Path: /languages
 * Text Domain: omnivalt
 * Requires at least: 5.1
 * Tested up to: 5.8.1
 * WC requires at least: 3.0.0
 * WC tested up to: 5.9.0
 * Requires PHP: 7.2
 */

if (!defined('WPINC')) {
  die;
}

define('OMNIVALT_VERSION', '1.8.4');
define('OMNIVALT_DIR', plugin_dir_path(__FILE__));
define('OMNIVALT_URL', plugin_dir_url(__FILE__));

function omnivalt_configs($section_name = false) {
  $params = array();

  /*
   * Omniva settings key in Wordpress options
   */
  $params['settings_key'] = 'woocommerce_omnivalt_settings';

  /*
   * Post offices and terminals params
   */
  $params['locations'] = array(
    'source_url' => 'https://www.omniva.ee/locations.json',
  );

  /*
   * Every shipping method params. All bellow array fields is required.
   *
   * title - Country name
   * methods - Value of one of this: courier, courier_plus, pickup, post
   * services - Omniva service codes for country
   * comment_lang - Identifier for terminals map
   */
  $params['shipping_params'] = array(
    'LT' => array(
      'title' => __('Lithuania', 'omnivalt'),
      'methods' => array('pickup', 'courier'),
      'services' => array(
        'pt pt' => 'PA',
        'pt po' => 'PO',
        'pt c' => 'PK',
        'c pt' => 'PU',
        'c c' => 'CI',
        'po pt' => 'PV',
        'lc pt' => 'PP',
      ),
      'comment_lang' => 'lit',
    ),
    'LV' => array(
      'title' => __('Latvia', 'omnivalt'),
      'methods' => array('pickup', 'courier'),
      'services' => array(
        'pt pt' => 'PA',
        'pt po' => 'PO',
        'pt c' => 'PK',
        'c pt' => 'PU',
        'c c' => 'CI',
        'po pt' => 'PV',
        'lc pt' => 'PP',
      ),
      'comment_lang' => 'lav',
    ),
    'EE' => array(
      'title' => __('Estonia', 'omnivalt'),
      'methods' => array('pickup', 'courier', 'courier_plus', 'post'),
      'services' => array(
        'pt pt' => 'PA',
        'pt po' => 'PO',
        'pt c' => 'PK',
        'c pt' => 'PU',
        'c c' => 'CI',
        //'c+ ???' => 'LX', //TODO: ?
        'po cp' => 'LH',
        'po pt' => 'PV',
        'po po' => 'CD',
        'po c' => 'CE',
        'lc pt' => 'PP',
      ),
      'comment_lang' => 'est',
    ),
    'FI' => array(
      'title' => __('Finland', 'omnivalt'),
      'methods' => array('pickup', 'courier', 'post'),
      'services' => array(
        'c pc' => 'QG', //TODO: ?
        'c po' => 'CD', //TODO: ?
        'c c' => 'CE', //TODO: ?
      ),
      'comment_lang' => '',
    ),
  );

  /*
   * Params for every shipping method
   */
  $params['method_params'] = array(
    'terminal' => array(
      'key' => 'pt',
      'title' => __('Parcel terminal', 'omnivalt'),
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
    ),
    'courier' => array(
      'key' => 'c',
      'title' => __('Courier', 'omnivalt'),
    ),
    'courier_plus' => array(
      'key' => 'cp',
      'title' => __('Courier Plus', 'omnivalt'),
    ),
    'post' => array(
      'key' => 'po',
      'title' => __('Post office', 'omnivalt'),
    ),
    'logistic' => array(
      'key' => 'lc',
      'title' => __('Logistics center', 'omnivalt'),
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
   *
   * Available service types: checkbox.
   */
  $params['additional_services'] = array(
    'arrival_sms' => array(
      'title' => __('Arrival SMS', 'omnivalt'),
      'code' => 'ST',
      'only_for' => array('PA', 'PU', 'PP', 'PO', 'PV', 'CD', 'CE'),
      'in_product' => false,
      'in_order' => false,
      'add_always' => true,
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
      'only_for' => array('CI', 'QH', 'QL'),
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
  );

  /*
   * Params for update
   */
  $params['update'] = array(
    'check_url' => 'https://api.github.com/repos/mijora/omniva-woocommerce/releases/latest',
    'download_url' => 'https://github.com/mijora/omniva-woocommerce/releases/latest/download/omniva-woocommerce.zip',
  );

  /*
   * Variables, which using to replace some value in string
   */
  $params['text_variables'] = array(
    'order_number' => __('Order number', 'omnivalt'),
  );

  /*
   * Debug params
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
require_once OMNIVALT_DIR . 'includes/class-debug.php';
require_once OMNIVALT_DIR . 'includes/class-helper.php';
require_once OMNIVALT_DIR . 'includes/class-emails.php';
require_once OMNIVALT_DIR . 'includes/class-api.php';
require_once OMNIVALT_DIR . 'includes/class-admin-html.php';
require_once OMNIVALT_DIR . 'includes/class-packer.php';
require_once OMNIVALT_DIR . 'includes/class-product.php';
require_once OMNIVALT_DIR . 'includes/class-cronjob.php';

add_action( 'init', 'omnivalt_load_textdomain' );
function omnivalt_load_textdomain() {
  load_plugin_textdomain( 'omnivalt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

function omnivalt_add_required_directories() {
  $directories = array('logs', 'pdf', 'debug');

  foreach ($directories as $dir) {
    if (!file_exists(OMNIVALT_DIR . $dir)) {
      mkdir(OMNIVALT_DIR . $dir, 0755, true);
    }
  }
}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'omnivalt_settings_link');
function omnivalt_settings_link( $links ) {
  array_unshift($links, '<a href="' .
    admin_url( 'admin.php?page=wc-settings&tab=shipping&section=omnivalt' ) .
    '">' . __('Settings', 'omnivalt') . '</a>');
  return $links;
}

function omnivalt_notices(){
  if ( !session_id() ) {
    session_start();
  }
  if ( array_key_exists( 'omnivalt_notices', $_SESSION ) ) {
    foreach ($_SESSION['omnivalt_notices'] as $notice):
    ?>
      <div class="<?php echo $notice['type']; ?>">
          <p><?php echo $notice['msg']; ?></p>
      </div><?php
    endforeach;
    unset( $_SESSION['omnivalt_notices'] );
  }
}
add_action('admin_notices', 'omnivalt_notices');

function omnivalt_check_update($current_version = '') {
  $update_params = omnivalt_configs('update');
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_URL, $update_params['check_url']);
  curl_setopt($ch, CURLOPT_USERAGENT,'Awesome-Octocat-App');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $response_data = json_decode(curl_exec($ch)); 
  curl_close($ch);

  if (isset($response_data->tag_name)) {
    $update_info = array(
      'version' => str_replace('v', '', $response_data->tag_name),
      'url' => (isset($response_data->html_url)) ? $response_data->html_url : '#',
    );
    if (empty($current_version)) {
      $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);
      $current_version = $plugin_data['Version'];
    }
    return (version_compare($current_version, $update_info['version'], '<')) ? $update_info : false;
  }
  
  return false;
}

function omnivalt_plugin_update_message( $file, $plugin ) {
  $check_update = omnivalt_check_update($plugin['Version']);
  $update_params = omnivalt_configs('update');
  if ($check_update) {
    echo '<tr class="plugin-update-tr installer-plugin-update-tr js-otgs-plugin-tr active">';
    echo '<td class="plugin-update" colspan="100%">';
    echo '<div class="update-message notice inline notice-warning notice-alt">';
    echo '<p>' . sprintf(__('A newer version of the plugin (%s) has been released.', 'omnivalt'), '<a href="' . $check_update['url'] . '" target="_blank">v' . $check_update['version'] . '</a>') . ' ' . sprintf(__('You can download it by pressing %s.', 'omnivalt'), '<a href="' . $update_params['download_url'] . '">' . __('here', 'omnivalt') . '</a>') . '</p>';
    echo '</div>';
    echo '</td></tr>';
  }
}
add_action( "after_plugin_row_" . plugin_basename(__FILE__), 'omnivalt_plugin_update_message', 10, 3 );

/**
 * Cronjob
 */
register_activation_hook(__FILE__, 'omnivalt_activation');
function omnivalt_activation()
{
  if (!wp_next_scheduled('omnivalt_location_update')) {
    wp_schedule_event(time(), 'daily', 'omnivalt_location_update');
  }
}

OmnivaLt_Cronjob::init();

register_deactivation_hook(__FILE__, 'omnivalt_deactivation');
function omnivalt_deactivation()
{
  wp_clear_scheduled_hook('omnivalt_location_update');
}

/*
* Check if WooCommerce is active
*/

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

  add_action( 'init', 'OmnivaLt_Product::init' );
  
  // add select2 js script
  add_action('wp_enqueue_scripts', 'omnivalt_scripts', 99);
  function omnivalt_scripts()
  {
    if (is_cart() || is_checkout()) {

      wp_enqueue_script('omniva-helper', plugins_url('/js/omniva_helper.js', __FILE__), array('jquery'), OMNIVALT_VERSION);
      wp_enqueue_script('omniva', plugins_url('/js/omniva.js', __FILE__), array('jquery'), OMNIVALT_VERSION);

      wp_enqueue_style('omniva', plugins_url('/css/omniva.css', __FILE__), array(), OMNIVALT_VERSION);

      wp_enqueue_script('leaflet', plugins_url('/js/leaflet.js', __FILE__), array('jquery'), null, true);
      wp_enqueue_style('leaflet', plugins_url('/css/leaflet.css', __FILE__));    

      wp_localize_script('omniva', 'omnivadata', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'omniva_plugin_url' => OMNIVALT_URL,
        'text_select_terminal' => __('Select terminal', 'omnivalt'),
        'text_select_post' => __('Select post office', 'omnivalt'),
        'text_search_placeholder' => __('Enter postcode', 'omnivalt'),
        'not_found' => __('Place not found', 'omnivalt'),
        'text_enter_address' => __('Enter postcode/address', 'omnivalt'),
        'text_show_in_map' => __('Show in map', 'omnivalt'),
        'text_show_more' => __('Show more', 'omnivalt'),
        'text_modal_title_terminal' => __('Omniva parcel terminals', 'omnivalt'),
        'text_modal_search_title_terminal' => __('Parcel terminals addresses', 'omnivalt'),
        'text_modal_title_post' => __('Omniva post offices', 'omnivalt'),
        'text_modal_search_title_post' => __('Post offices addresses', 'omnivalt'),
      ));
    }
  }

  add_action('wp_footer', 'footer_modal');
  function footer_modal()
  {
    if (is_cart() || is_checkout()) {
      echo terminalsModal();
    }
  }
  function add_asyncdefer_attribute($tag, $handle)
  {
    if (strpos($handle, 'async') !== false) {
      $tag = str_replace('<script ', '<script async ', $tag);
    }
    if (strpos($handle, 'defer') !== false) {
      $tag =  str_replace('<script ', '<script defer ', $tag);
    }
    return $tag;
  }
  add_filter('script_loader_tag', 'add_asyncdefer_attribute', 10, 2);

  add_action('omniva_admin_manifest_head', 'omnivalt_admin_manifest_scripts');
  function omnivalt_admin_manifest_scripts()
  {
    wp_enqueue_style('omnivalt_admin_manifest', plugins_url('/css/omniva_admin_manifest.css', __FILE__));
    wp_enqueue_style('bootstrap-datetimepicker', plugins_url('/js/datetimepicker/bootstrap-datetimepicker.min.css', __FILE__));
    wp_enqueue_script('moment', plugins_url('/js/moment.min.js', __FILE__), array(), null, true);
    wp_enqueue_script('bootstrap-datetimepicker', plugins_url('/js/datetimepicker/bootstrap-datetimepicker.min.js', __FILE__), array('jquery', 'moment'), null, true);
  }

  add_action('admin_enqueue_scripts', 'omnivalt_admin_settings_scripts');
  function omnivalt_admin_settings_scripts($hook)
  {
    if ($hook == 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] == 'omnivalt') {
      wp_enqueue_style('omnivalt_admin_settings', plugins_url('/css/omniva_admin_settings.css', __FILE__), array(), OMNIVALT_VERSION);
      wp_enqueue_script('omniva_admin_settings', plugins_url( '/js/omniva_admin_settings.js', __FILE__ ), array('jquery'), OMNIVALT_VERSION );
    }
  }

  add_action('admin_enqueue_scripts', 'omnivalt_admin_order_scripts');
  function omnivalt_admin_order_scripts($hook)
  {
    global $post;

    if ($hook == 'post-new.php' || $hook == 'post.php') {
      if ($post->post_type === 'shop_order') {
        wp_enqueue_script('omniva_admin_order', plugins_url( '/js/omniva_admin_order.js', __FILE__ ), array('jquery'), OMNIVALT_VERSION );
      }
    }
  }

  add_action('wp_ajax_nopriv_add_terminal_to_session', 'add_terminal_to_session');
  add_action('wp_ajax_add_terminal_to_session', 'add_terminal_to_session');
  function add_terminal_to_session()
  {
    if (isset($_POST['terminal_id']) && is_numeric($_POST['terminal_id'])) {
      WC()->session->set('omnivalt_terminal_id', $_POST['terminal_id']);
    }
    wp_die();
  }

  function omnivalt_shipping_method()
  {
    include OMNIVALT_DIR . 'includes/class-shipping-method.php';
  }
  add_action('woocommerce_shipping_init', 'omnivalt_shipping_method');

  add_action( 'woocommerce_after_shipping_rate', 'omnivalt_after_shipping_rate', 20, 2 );
  function omnivalt_after_shipping_rate ( $method, $index ) {
    if( is_cart() ) return; // Exit on cart page

    $customer = WC()->session->get('customer');
    if ( ! isset($customer['country']) ) {
      return;
    }

    $omnivalt_Shipping_Method = new Omnivalt_Shipping_Method();
    if ( ! isset($omnivalt_Shipping_Method->settings['prices_' . $customer['country']]) ) {
      return;
    }

    $rate_settings = json_decode($omnivalt_Shipping_Method->settings['prices_' . $customer['country']]);

    if ( ! empty($rate_settings->pt_description) && $method->id === 'omnivalt_pt' ) {
      echo '<span class="omnivalt-shipping-description">' . $rate_settings->pt_description . '</span>';
    }
    if ( ! empty($rate_settings->c_description) && $method->id === 'omnivalt_c' ) {
      echo '<span class="omnivalt-shipping-description">' . $rate_settings->c_description . '</span>';
    }
  }

  add_filter('woocommerce_shipping_methods', 'add_omnivalt_shipping_method');
  function add_omnivalt_shipping_method($methods)
  {
    $methods['omnivalt'] = 'Omnivalt_Shipping_Method';
    return $methods;
  }

  add_action('woocommerce_after_shipping_rate', 'omnivalt_show_terminals');
  function omnivalt_show_terminals($method)
  {
    $customer = WC()->session->get('customer');
    $country = "ALL";
    if (isset($customer['country']))
      $country = $customer['country'];
    
    $termnal_id = WC()->session->get('omnivalt_terminal_id');
    
    $selected_shipping_method = WC()->session->get('chosen_shipping_methods');
    if (empty($selected_shipping_method)) {
      $selected_shipping_method = array();
    }
    if (!is_array($selected_shipping_method)) {
      $selected_shipping_method = array($selected_shipping_method);
    }

    if ( $method->id == "omnivalt_pt" && in_array("omnivalt_pt", $selected_shipping_method) ) {
      echo omnivaltGetTerminalsOptions($termnal_id, $country);
    }
    if ( $method->id == "omnivalt_po" && in_array("omnivalt_po", $selected_shipping_method) ) {
      echo omnivaltGetTerminalsOptions($termnal_id, $country, 'post');
    }
  }

  /**
   * Restrict Omniva Shipping methods if cart products has restricted categories
   */
  add_filter('woocommerce_package_rates', 'restrict_omnivalt_shipping_methods', 10, 1);
  function restrict_omnivalt_shipping_methods($rates)
  {
    global $woocommerce;
    $cart_categories_ids = array();

    foreach( $woocommerce->cart->get_cart() as $cart_item ) {
      $cats = get_the_terms( $cart_item['product_id'], 'product_cat' );
      foreach ($cats as $cat) {
        $cart_categories_ids[] = $cat->term_id;
        if ($cat->parent != 0) {
          $cart_categories_ids[] = $cat->parent;
        }
      }
    }

    $cart_categories_ids = array_unique($cart_categories_ids);

    $omniva_options = get_option('woocommerce_omnivalt_settings');
    $restricted_categories = $omniva_options['restricted_categories'];
    if (!is_array($restricted_categories)) {
      $restricted_categories = array($restricted_categories);
    }

    foreach ($cart_categories_ids as $cart_product_categories_id) {
      if (in_array($cart_product_categories_id, $restricted_categories)) {
        unset($rates['omnivalt_pt']);
        unset($rates['omnivalt_c']);
        unset($rates['omnivalt_cp']);
        unset($rates['omnivalt_po']);
        break;
      }
    }

    return $rates;
  }

  add_action('woocommerce_checkout_update_order_meta', 'add_terminal_id_to_order');
  function add_terminal_id_to_order($order_id)
  {
    if (isset($_POST['omnivalt_terminal']) && $order_id) {
      update_post_meta($order_id, '_omnivalt_terminal_id', $_POST['omnivalt_terminal']);
    }
    if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method'])) {
      foreach ($_POST['shipping_method'] as $ship_method) {
        if ($ship_method == "omnivalt_pt" || $ship_method == "omnivalt_c" || $ship_method == "omnivalt_cp" || $ship_method == "omnivalt_po") {
          update_post_meta($order_id, '_omnivalt_method', $ship_method);
        }
      }
    }
  }

  function omnivalt_validate_order($posted)
  {
    $packages = WC()->shipping->get_packages();
    $chosen_methods = WC()->session->get('chosen_shipping_methods');
    if (is_array($chosen_methods) && in_array('omnivalt', $chosen_methods)) {
      foreach ($packages as $i => $package) {
        if ($chosen_methods[$i] != "omnivalt") {
          continue;
        }

        $omnivalt_Shipping_Method = new Omnivalt_Shipping_Method();
        $weightLimit = (int) $omnivalt_Shipping_Method->settings['weight'];
        $weight = 0;
        foreach ($package['contents'] as $item_id => $values) {
          $_product = $values['data'];
          $weight = $weight + $_product->get_weight() * $values['quantity'];
        }

        $weight = wc_get_weight($weight, 'kg');
        if ($weight > $weightLimit) {
          $message = sprintf(__('Sorry, %d kg exceeds the maximum weight of %d kg for %s', 'omnivalt'), $weight, $weightLimit, $omnivalt_Shipping_Method->title);
          $messageType = "error";
          if (!wc_has_notice($message, $messageType)) {
            wc_add_notice($message, $messageType);
          }
        }
      }
    }
  }

  function omnivaltGetTerminalsOptions($selected = '', $country = "ALL", $get_list = 'terminal')
  {
    $terminals_json_file_dir = dirname(__file__) . '/' . "locations.json";
    $terminals_file = fopen($terminals_json_file_dir, "r");
    $terminals = fread($terminals_file, filesize($terminals_json_file_dir) + 10);
    fclose($terminals_file);
    $terminals = json_decode($terminals, true);
    $parcel_terminals = '';
    if (is_array($terminals)) {
      $grouped_options = array();
      foreach ($terminals as $terminal) {
        if ( ($get_list === 'terminal' && intval($terminal['TYPE']) === 1)
          || ($get_list === 'post' && intval($terminal['TYPE']) === 0) ) {
          continue;
        }
        if ($terminal['A0_NAME'] != $country && $country != "ALL") continue;
        if (!isset($grouped_options[$terminal['A1_NAME']])) $grouped_options[(string) $terminal['A1_NAME']] = array();
        $grouped_options[(string) $terminal['A1_NAME']][(string) $terminal['ZIP']] = $terminal['NAME'];
      }
      $counter = 0;
      foreach ($grouped_options as $city => $locs) {
        $parcel_terminals .= '<optgroup data-id = "' . $counter . '" label = "' . $city . '">';
        foreach ($locs as $key => $loc) {
          $parcel_terminals .= '<option value = "' . $key . '" ' . ($key == $selected ? 'selected' : '') . '>' . $loc . '</option>';
        }

        $parcel_terminals .= '</optgroup>';
        $counter++;
      }
    }

    $nonce = wp_create_nonce("omniva_terminals_json_nonce");
    $omniva_settings = get_option('woocommerce_omnivalt_settings');
    $parcel_terminals = '<option value = "">' . __('Select parcel terminal', 'omnivalt') . '</option>' . $parcel_terminals;
    $set_autoselect = (isset($omniva_settings['auto_select'])) ? $omniva_settings['auto_select'] : 'yes';
    $script = "<script style='display:none;'>
      var omnivaTerminals = JSON.stringify(" . json_encode(getTerminalForMap('', $country, $get_list)) . ");
    </script>";
    $script .= "<script style='display:none;'>
      var omniva_current_country = '" . $country . "';
      var omnivaSettings = {
        auto_select:'" . $set_autoselect . "'
      };
      var omniva_type = '" . $get_list . "';
      var omniva_current_terminal = '" . $selected . "';
      jQuery('document').ready(function($){        
        $('.omnivalt_terminal').omniva();
        $(document).trigger('omnivalt.checkpostcode');
      });
      </script>";
    $button = '';
    if (!isset($omniva_settings['show_map']) || isset($omniva_settings['show_map']) && $omniva_settings['show_map'] == "yes") {
      $title = ($get_list === 'terminal') ? __("Show parcel terminals map", "omnivalt") : '';
      $title = ($get_list === 'post') ? __("Show post offices map", "omnivalt") : $title;
      $button = '<button type="button" id="show-omniva-map" class="btn btn-basic btn-sm omniva-btn" style = "display: none;">' . __('Show in map', 'omnivalt') . '<img src = "' . OMNIVALT_URL . '/sasi.png" title = "' . $title . '"/></button>';
    }
    return '<div class="terminal-container"><select class="omnivalt_terminal" name="omnivalt_terminal">' . $parcel_terminals . '</select>
      ' . $button . ' </div>' . $script;
  }

  function omnivaltGetTerminalsList($country = "ALL") {
    $terminals_json_file_dir = dirname(__file__) . '/' . "locations.json";
    $terminals_file = fopen($terminals_json_file_dir, "r");
    $terminals = fread($terminals_file, filesize($terminals_json_file_dir) + 10);
    fclose($terminals_file);
    $terminals = json_decode($terminals, true);
    $grouped_options = array();
    if (is_array($terminals)) {
      foreach ($terminals as $terminal) {
        if (intval($terminal['TYPE']) == 1) {
          continue;
        }
        //if ($terminal['A0_NAME'] != $country && $country != "ALL") continue;
        if (!isset($grouped_options[$terminal['A0_NAME']])) $grouped_options[(string) $terminal['A0_NAME']] = array();
        if (!isset($grouped_options[$terminal['A0_NAME']][$terminal['A1_NAME']])) $grouped_options[(string) $terminal['A0_NAME']][(string) $terminal['A1_NAME']] = array();
        $grouped_options[(string) $terminal['A0_NAME']][(string) $terminal['A1_NAME']][(string) $terminal['ZIP']] = $terminal['NAME'];
      }
    }
    $grouped_options = omnivaltSortTerminalList($grouped_options);
    return ($country != "ALL" && isset($grouped_options[$country])) ? $grouped_options[$country] : $grouped_options;
  }

  function omnivaltSortTerminalList($list) 
  {
    $sorted_list = array();
    foreach ($list as $key => $elem) {
      ksort($elem);
      $sorted_list[$key] = $elem;
    }
    return $sorted_list;
  }

  function omnivaltTerminalName($terminal_code)
  {
    $terminals_json_file_dir = dirname(__file__) . '/' . "locations.json";
    $terminals_file = fopen($terminals_json_file_dir, "r");
    $terminals = fread($terminals_file, filesize($terminals_json_file_dir) + 10);
    fclose($terminals_file);
    $terminals = json_decode($terminals, true);
    $parcel_terminals = '';
    if (is_array($terminals)) {
      foreach ($terminals as $terminal) {
        if ((string) $terminal['ZIP'] == $terminal_code)
          return (string) $terminal['NAME'] . ', ' . $terminal['A1_NAME'];
      }
    }
    return false;
  }

  // returns omniva method id or false if not omniva method
  function getOmnivaMethod($order)
  {
    $wc_order = wc_get_order((int) $order->get_id());
    $send_method = "";
    foreach ($wc_order->get_items('shipping') as $item_id => $shipping_item_obj) {
      $send_method = $shipping_item_obj->get_method_id();
    }
    if ($send_method == 'omnivalt') {
      return get_post_meta($order->get_id(), '_omnivalt_method', true);
    }
    return false;
  }

  // $order must have omniva shipping method
  function getOmnivaTerminalAddress($order)
  {
    $terminal_id = get_post_meta($order->get_id(), '_omnivalt_terminal_id', true);
    $terminal_name = omnivaltTerminalName($terminal_id);
    if (!$terminal_name)
      $terminal_name  = __('Parcel terminal not found!!!', 'omnivalt');
    return $terminal_name;
  }

  add_action('woocommerce_order_details_after_order_table', 'show_terminal_details', 10, 1);
  add_action('woocommerce_email_after_order_table', 'show_terminal_details', 10, 1);
  function show_terminal_details($order)
  {
    $send_method = getOmnivaMethod($order);
    if ($send_method == 'omnivalt_pt') {
      echo "<p>" . __('Omniva parcel terminal', 'omnivalt') . ": " . getOmnivaTerminalAddress($order) . "</p>";
    }

    if ($send_method) {
      printTrackingLink($order, false, true);
    }
  }

  // Add custom order meta data to make it accessible in Order preview template
  add_filter('woocommerce_admin_order_preview_get_order_details', 'admin_order_preview_add_custom_meta_data', 10, 2);
  function admin_order_preview_add_custom_meta_data($data, $order)
  {
    $send_method = getOmnivaMethod($order);
    if ($send_method == 'omnivalt_pt') {
      $data['shipping_via'] = __('Omniva parcel terminal', 'omnivalt') . ": " . getOmnivaTerminalAddress($order);
    }

    if ($send_method) {
      $wc_shipping = new WC_Shipping();
      $barcode = $order->get_meta('_omnivalt_barcode');
      $omnivalt = new Omnivalt_Shipping_Method();
      $country_code = $omnivalt->settings['shop_countrycode'];
      $data['omnivalt_tracking_link'] = getTrackingLink($country_code, $barcode, true);
      $data['omnivalt_barcode'] = $barcode;
    }

    return $data;
  }

  add_action( 'woocommerce_admin_order_preview_end', 'custom_display_order_data_in_admin' );
  function custom_display_order_data_in_admin(){
    // Call the stored value and display it    
    echo '<# if ( data.omnivalt_barcode ) { #>' .
      '<p><div class="wc-order-preview-addresses">' .
      '<div class="wc-order-preview-address">' .
      '<strong>' . __('Omniva tracking number', 'omnivalt') .':</strong><a href="{{data.omnivalt_tracking_link}}" target="_blank">{{data.omnivalt_barcode}}</a>' .
      '</div></div></p>' .
      '<# } #>';
  }

  add_action('woocommerce_review_order_before_cart_contents', 'omnivalt_validate_order', 10);
  add_action('woocommerce_after_checkout_validation', 'omnivalt_validate_order', 10);

  add_filter('bulk_actions-edit-shop_order', 'omnivalt_shop_order_bulk_actions', 20);
  function omnivalt_shop_order_bulk_actions($actions)
  {
    $actions['omnivalt_labels'] = __('Print Omniva labels', 'omnivalt');
    $actions['omnivalt_manifest'] = __('Print Omniva manifest', 'omnivalt');
    return $actions;
  }

  add_filter('handle_bulk_actions-edit-shop_order', 'omnivalt_handle_shop_order_bulk_actions', 20, 3);
  function omnivalt_handle_shop_order_bulk_actions($redirect_to, $action, $ids)
  {
    if ($action == "omnivalt_labels") {
      $wc_shipping = new WC_Shipping();
      $omnivalt = new Omnivalt_Shipping_Method();
      $omnivalt->printLabels($ids);
      return 0;
    }

    if ($action == "omnivalt_manifest") {
      $wc_shipping = new WC_Shipping();
      $omnivalt = new Omnivalt_Shipping_Method();
      $omnivalt->printBulkManifests($ids);
      return 0;
    }

    return $redirect_to;
  }

  add_filter('admin_post_omnivalt_labels', 'omnivalt_post_label_actions', 20, 3);
  function omnivalt_post_label_actions()
  {
    $wc_shipping = new WC_Shipping();
    $omnivalt = new Omnivalt_Shipping_Method();
    $omnivalt->printLabels($_REQUEST['post']);
  }

  add_filter('admin_post_omnivalt_manifest', 'omnivalt_post_manifest_actions', 20, 3);
  function omnivalt_post_manifest_actions()
  {
    $wc_shipping = new WC_Shipping();
    $omnivalt = new Omnivalt_Shipping_Method();
    $omnivalt->printBulkManifests($_REQUEST['post']);
  }

  add_filter('woocommerce_admin_order_actions_end', 'omnivalt_order_actions', 10, 1);
  function omnivalt_order_actions($order)
  {
    $order_id = $order->get_id();
    $send_method = get_post_meta($order_id, '_shipping_method', true);
    if ((isset($send_method[0]) && ($send_method[0] == 'omnivalt_pt' || $send_method[0] == 'omnivalt_c' || $send_method[0] == 'omnivalt'))) {
      echo '<a class="button tips omnivalt_generate_label" href="' . wp_nonce_url(admin_url('admin-ajax.php?action=generate_omnivalt_label&order_id=' . $order_id), 'woocommerce-mark-order-status') . '" data-tip="' . __('Generate Omniva label', 'omnivalt') . '"> </a>';
      if (file_exists(OMNIVALT_DIR . "pdf/" . $order_id . '.pdf')) {
        echo '<a class="button tips omnivalt_view_label" href="' . plugins_url('pdf/' . $order_id . '.pdf', __FILE__) . '" target = "_blank" data-tip="' . __('VIew Omniva label', 'omnivalt') . '"> </a>';
      }
    }
  }

  add_action('wp_ajax_generate_omnivalt_label', 'generate_omnivalt_label');
  function generate_omnivalt_label()
  {
    if (current_user_can('edit_shop_orders') && check_admin_referer('woocommerce-mark-order-status')) {
      $wc_shipping = new WC_Shipping();
      $omnivalt = new Omnivalt_Shipping_Method();
      $omnivalt->printLabels($_GET['order_id'], false);
    }
    wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url('edit.php?post_type=shop_order'));
    exit;
  }

  //add weight to order
  add_action('woocommerce_checkout_update_order_meta', 'woo_add_cart_weight');
  function woo_add_cart_weight($order_id)
  {
    global $woocommerce;
    $weight = $woocommerce->cart->cart_contents_weight;
    update_post_meta($order_id, '_cart_weight', $weight);
  }

  //add manifest order page
  add_action('admin_menu', 'register_omniva_manifest_menu_page');
  function register_omniva_manifest_menu_page()
  {
    add_submenu_page(
      'woocommerce',
      __('Omniva manifest', 'omnivalt'),
      __('Omniva manifest', 'omnivalt'),
      'manage_woocommerce',
      'omniva-manifest',
      'manifest_page',
      //plugins_url('omniva-woocommerce/images/icon.png'),
      1
    );
  }

  function manifest_page()
  {
    include_once("manifest_page.php");
  }

  function getTrackingLink($country_code, $barcode, $link_only = false)
  {
    $country_code = strtoupper($country_code);
    $omniva_tracking_url = array(
      'LT' => 'https://www.omniva.lt/verslo/siuntos_sekimas?barcode=',
      'LV' => 'https://www.omniva.lv/privats/sutijuma_atrasanas_vieta?barcode=',
      'EE' => 'https://www.omniva.ee/era/jalgimine?barcode='
    );
    if (!isset($omniva_tracking_url[$country_code])) {
      return $barcode;
    }
    if ($link_only) {
      return $omniva_tracking_url[$country_code] . $barcode;
    }
    return "<a href=\"" . $omniva_tracking_url[$country_code]. "$barcode\" target=\"_blank\">$barcode</a>\n";
  }

  function printTrackingLink($order, $admin_panel = true, $print = true)
  {
    $wc_shipping = new WC_Shipping();
    $omnivalt = new Omnivalt_Shipping_Method();
    $barcode = $order->get_meta('_omnivalt_barcode');
    if ($admin_panel) {
      $country_code = $omnivalt->settings['shop_countrycode'];
      $text = __('Omniva tracking number', 'omnivalt');
    } else {
      $country_code = $order->get_shipping_country();
      $text = __('You can track your parcel with this number', 'omnivalt');
    }

    $html = '';
    if (!empty($barcode)) {
      $html = '<p><strong>' . $text . ':</strong> <br/>' . getTrackingLink($country_code, $barcode) . '</p>';
    }
    if (!$print) {
      return $html;
    }
    echo $html;
  }

  add_action('print_omniva_tracking_url', 'print_omniva_tracking_url_action', 10, 2);
  function print_omniva_tracking_url_action($country_code = 'LT', $barcode)
  {
    echo getTrackingLink($country_code, $barcode);
  }

  add_filter('admin_post_omnivalt_call_courier', 'omnivalt_post_call_courier_actions');
  function omnivalt_post_call_courier_actions()
  {
    $wc_shipping = new WC_Shipping();
    $omnivalt_api = new OmnivaLt_Api();
    $callCarrierReturn = $omnivalt_api->call_courier();
    if ($callCarrierReturn['status'] == true)
      OmnivaLt_Helper::add_msg(__("Omniva courier called", 'omnivalt'), 'omniva-notice');
    else
      OmnivaLt_Helper::add_msg(__("There was an error calling Omniva courier. Error: " . $callCarrierReturn['msg'], 'omnivalt'), 'error');
    wp_safe_redirect(wp_get_referer());
  }
   
  /**
   * Display field value on the order edit page
   */
  add_action('woocommerce_admin_order_data_after_shipping_address', 'omniva_terminal_field_display_admin_order_meta', 10, 2);
  function omniva_terminal_field_display_admin_order_meta($order, $print_barcode = true, $admin_panel = true)
  {
    $configs_services = omnivalt_configs('additional_services');
    $send_method = getOmnivaMethod($order);
    if ($send_method != 'omnivalt_pt' && $send_method != 'omnivalt_c' && $send_method != 'omnivalt_cp' && $send_method != 'omnivalt_po') {
      return;
    }
    global $post_type;
    $only_in_order = true;
    if ('shop_order' != $post_type) {
      $only_in_order = false;
    }

    if ($only_in_order) {
      echo '<br class="clear"/>';
      echo '<hr style="margin-top:20px;">';
      echo '<h4>' . __('Omniva Shipping', 'omnivalt') . '</h4>';
    }
    echo '<div class="address">';
    if ($send_method == 'omnivalt_pt') {
      echo '<p><strong class="title">' . __('Parcel terminal', 'omnivalt') . ':</strong> ' . getOmnivaTerminalAddress($order) . '</p>';
    } else if ($send_method == 'omnivalt_c') {
      echo '<p><strong class="title">' . __('Courier', 'omnivalt') . ':</strong> ' . $order->get_formatted_shipping_address() . '</p>';
    } else if ($send_method == 'omnivalt_cp') {
      echo '<p><strong class="title">' . __('Courier Plus', 'omnivalt') . ':</strong> ' . $order->get_formatted_shipping_address() . '</p>';
    } else if ($send_method == 'omnivalt_po') {
      echo '<p><strong class="title">' . __('Post office', 'omnivalt') . ':</strong> ' . getOmnivaTerminalAddress($order) . '</p>';
    }

    $services = OmnivaLt_Product::get_order_items_services($order, true);
    $services = OmnivaLt_Helper::override_with_order_services($order->get_id(), $services);
    if (!(empty($services))) {
      echo '<p><strong class="title">' . __('Services', 'omnivalt') . ':</strong> ';
      $output = '';
      foreach ($services as $service) {
        if (!empty($output)) $output .= ', ';
        foreach ($configs_services as $service_key => $service_values) {
          if ($service === $service_key) $output .= $service_values['title'];
        }
      }
      echo $output . '</p>';
    }

    if ($print_barcode) {
      echo str_replace('<br/>', '', printTrackingLink($order, $admin_panel, false));
    }
    echo '</div>';

    if (!$only_in_order) {
      return;
    }
    echo '<div class="edit_address">';
    if ($send_method == 'omnivalt_pt') {
      $all_terminals = omnivaltGetTerminalsList();
      $selected_terminal = get_post_meta($order->get_id(), '_omnivalt_terminal_id', true);
      echo '<p class="form-field-wide">';
      echo '<label for="omnivalt_terminal">' . __('Change parcel terminal', 'omnivalt') . '</label>';
      echo '<input type="hidden" id="omniva-order-country" value="' . $order->get_shipping_country() . '">';
      echo '<select id="omnivalt_terminal" class="select short" name="omnivalt_terminal_id">';
      echo '<option>-</option>';
      foreach ($all_terminals as $country => $country_terminals) {
        foreach ($country_terminals as $county => $terminals) {
          echo '<optgroup data-country="' . $country . '" label="' . $county . '">';
          foreach ($terminals as $terminal_id => $terminal_name) {
            $selected = ($terminal_id == $selected_terminal) ? 'selected' : '';
            echo '<option value="' . $terminal_id . '" ' . $selected . '>' . $terminal_name . '</option>';
          }
          echo '</optgroup>';
        }
      }
      echo '</select>';
      echo '</p>';
    }
    if ($send_method == 'omnivalt_c') {
      echo __('The delivery address for the courier is changed in the fields above', 'omnivalt');
    }

    foreach ($configs_services as $service_key => $service_values) {
      if ($service_values['add_always']) continue;
      echo '<p class="form-field-wide">';
      $field_id = 'omnivalt_' . $service_key;
      echo '<label for="' . $field_id . '">' . $service_values['title'] . '</label>';
      if ($service_values['in_order'] === 'checkbox') {
        echo '<select id="' . $field_id . '" class="select short" name="' . $field_id . '">';
        echo '<option value="no">' . __('No', 'omnivalt') . '</option>';
        $selected = (in_array($service_key, $services)) ? 'selected' : '';
        echo '<option value="yes" ' . $selected . '>' . __('Yes', 'omnivalt') . '</option>';
        echo '</select>';
      }
      echo '</p>';
    }

    echo '</div>';
    echo '<hr style="margin-top:20px;">';
  }

  add_action( 'save_post', 'omniva_terminal_field_save_admin_order_meta');
  function omniva_terminal_field_save_admin_order_meta($post_id) {
    global $post_type;
    if ( 'shop_order' != $post_type ) {
      return $post_id;
    }

    $configs_services = omnivalt_configs('additional_services');

    if (isset($_POST['omnivalt_terminal_id'])) {
      update_post_meta($post_id, '_omnivalt_terminal_id', wc_clean($_POST['omnivalt_terminal_id']));
    }

    foreach ($configs_services as $service_key => $service_values) {
      if (isset($_POST['omnivalt_' . $service_key])) {
        update_post_meta($post_id, '_omnivalt_' . $service_key, wc_clean($_POST['omnivalt_' . $service_key]));
      }
    }
  }

  add_action('woocommerce_checkout_process', 'omnivalt_terminal_validate');
  function omnivalt_terminal_validate()
  {
    if (isset($_POST['shipping_method']) && in_array('omnivalt_pt', $_POST['shipping_method'])) {
      if (empty($_POST['omnivalt_terminal']))
        wc_add_notice(__('Please select parcel terminal.', 'omnivalt'), 'error');
    }
  }

  /**
   * CUSTOM ACTIONS FOR MANIFEST PAGE
   */

  /**
 * Handle a custom query variable to get orders.
 * @param array $query - Args for WP_Query.
 * @param array $query_vars - Query vars from WC_Order_Query.
 * @return array modified $query
 */
  function handle_custom_omniva_query_var( $query, $query_vars ) {
    if ( ! empty( $query_vars['omnivalt_method'] ) ) {
      $query['meta_query'][] = array(
        'key' => '_omnivalt_method',
        'value' => $query_vars['omnivalt_method']//esc_attr( $query_vars['omnivalt_method'] ),
      );
    }

    if ( isset( $query_vars['omnivalt_barcode'] ) ) {
      $query['meta_query'][] = array(
          'key' => '_omnivalt_barcode',
          'value' => $query_vars['omnivalt_barcode'],
          'compare' => 'LIKE'
      );
    }

    if ( isset( $query_vars['omnivalt_customer'] ) ) {
      $query['meta_query'][] = array(
          'relation' => 'OR',
          array(
            'key' => '_billing_first_name',
            'value' => $query_vars['omnivalt_customer'],
            'compare' => 'LIKE'
          ),
          array(
            'key' => '_billing_last_name',
            'value' => $query_vars['omnivalt_customer'],
            'compare' => 'LIKE'
          )
      );
    }

    if ( isset( $query_vars['omnivalt_manifest'] ) ) {
      $query['meta_query'][] = array(
        'key' => '_manifest_generation_date',
        'compare' => ($query_vars['omnivalt_manifest'] ? 'EXISTS' : 'NOT EXISTS'),
      );
    }

    if ( isset( $query_vars['omnivalt_manifest_date'] ) ) {
      $filter_by_date = false;
      if ($query_vars['omnivalt_manifest_date'][0] && $query_vars['omnivalt_manifest_date'][1]) {
        $filter_by_date = array(
          'key' => '_manifest_generation_date',
          'value' => $query_vars['omnivalt_manifest_date'],
          'compare' => 'BETWEEN'
        );
      } elseif ($query_vars['omnivalt_manifest_date'][0] && !$query_vars['omnivalt_manifest_date'][1]) {
        $filter_by_date = array(
          'key' => '_manifest_generation_date',
          'value' => $query_vars['omnivalt_manifest_date'][0],
          'compare' => '>='
        );
      } elseif (!$query_vars['omnivalt_manifest_date'][0] && $query_vars['omnivalt_manifest_date'][1]) {
        $filter_by_date = array(
          'key' => '_manifest_generation_date',
          'value' => $query_vars['omnivalt_manifest_date'][1],
          'compare' => '<='
        );
      }

      if ($filter_by_date) {
        $query['meta_query'][] = $filter_by_date;
      }
    }

    return $query;
  }
  add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'handle_custom_omniva_query_var', 10, 2 );

  /**
   * END OF CUSTOM ACTIONS FOR MANIFEST PAGE
   */

  /**maps */
  function getTerminalForMap($selected = '', $country = "LT", $get_list = 'terminal')
  {
    $shipping_params = omnivalt_configs('shipping_params');
    $terminals_json_file_dir = dirname(__file__) . "/locations.json";
    $terminals_file = fopen($terminals_json_file_dir, "r");
    $terminals = fread($terminals_file, filesize($terminals_json_file_dir) + 10);
    fclose($terminals_file);
    $terminals = json_decode($terminals, true);
    $parcel_terminals = '';
    $terminalsList = array();
    $comment_lang = (!empty($shipping_params[strtoupper($country)]['comment_lang'])) ? $shipping_params[strtoupper($country)]['comment_lang'] : 'lit';
    if (is_array($terminals)) {
      foreach ($terminals as $terminal) {
        if ( $terminal['A0_NAME'] != $country && isset($shipping_params[$country])
          || ($get_list === 'terminal' && intval($terminal['TYPE']) === 1)
          || ($get_list === 'post' && intval($terminal['TYPE']) === 0)
        ) {
          continue;
        }

        if (!isset($grouped_options[$terminal['A1_NAME']]))
          $grouped_options[(string) $terminal['A1_NAME']] = array();
        $grouped_options[(string) $terminal['A1_NAME']][(string) $terminal['ZIP']] = $terminal['NAME'];

        $terminalsList[] = [$terminal['NAME'], $terminal['Y_COORDINATE'], $terminal['X_COORDINATE'], $terminal['ZIP'], $terminal['A1_NAME'], $terminal['A2_NAME'] !== 'NULL' ? $terminal['A2_NAME'] : '', str_ireplace('"', '\"', $terminal['comment_' . $comment_lang])];
      }
    }
    return $terminalsList;
  }

  function terminalsModal()
  {
    return '
    <div id="omnivaLtModal" class="modal">
        <div class="omniva-modal-content">
            <div class="omniva-modal-header">
            <span class="close" id="terminalsModal">&times;</span>
            <h5 id="omnivaLt_modal_title" style="display: inline">' . __('Omniva parcel terminals', 'omnivalt') . '</h5>
            </div>
            <div class="omniva-modal-body" style="/*overflow: hidden;*/">
                <div id = "omnivaMapContainer"></div>
                <div class="omniva-search-bar" >
                    <h4 id="omnivaLt_modal_search" style="margin-top: 0px;">' . __('Parcel terminals addresses', 'omnivalt') . '</h4>
                    <div id="omniva-search">
                    <form>
                    <input type = "text" placeholder = "' . __('Enter postcode', 'omnivalt') . '"/>
                    <button type = "submit" id="map-search-button"></button>
                    </form>                    
                    <div class="omniva-autocomplete scrollbar" style = "display:none;"><ul></ul></div>
                    </div>
                    <div class = "omniva-back-to-list" style = "display:none;">' . __('Back', 'omnivalt') . '</div>
                    <div class="found_terminals scrollbar" id="style-8">
                      <ul>
                      
                      </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>';
  }

  function generate_json_terminals($term = "", $country = "ALL")
  {
    $c_p = false;
    if (strlen($term) >= 4 && strlen($term)) {
      $c_p = search_postcode($term, $country);
    }
    $terminals_json_file_dir = dirname(__file__) . '/' . "locations.json";
    $terminals_file = fopen($terminals_json_file_dir, "r");
    $terminals = fread($terminals_file, filesize($terminals_json_file_dir) + 10);
    fclose($terminals_file);
    $terminals = json_decode($terminals, true);
    $parcel_terminals = array();
    if (is_array($terminals)) {
      $grouped_options = array();
      foreach ($terminals as $terminal) {
        if (intval($terminal['TYPE']) == 1) {
          continue;
        }
        if ($terminal['A0_NAME'] != $country && $country != "ALL") continue;
        if (!isset($grouped_options[$terminal['A1_NAME']])) $grouped_options[(string) $terminal['A1_NAME']] = array();
        $grouped_options[(string) $terminal['A1_NAME']][(string) $terminal['ZIP']] = $terminal;
      }
      $counter = 0;
      foreach ($grouped_options as $city => $locs) {
        $group = array("text" =>  $city, "distance" => 0, "children" => array());
        $group_distance = false;
        foreach ($locs as $key => $loc) {
          if ($term != "" && $c_p == false && stripos($loc['NAME'], $term) !== false) {
            $group['children'][] = array("id" => $key, "text" => $loc['NAME'], "distance" => 0);
          } elseif (is_array($c_p)) {
            $distance = calc_distance($c_p[0], $c_p[1], $loc['Y_COORDINATE'], $loc['X_COORDINATE']);
            $group['children'][] = array("id" => $key, "text" => $loc['NAME'], "distance" => $distance);
            if ($group_distance == false || $group_distance > $distance) {
              $group_distance = $distance;
            }
          } elseif ($term == "") {
            $group['children'][] = array("id" => $key, "text" => $loc['NAME'], "distance" => 0);
          }
        }
        $group['distance'] = $group_distance;
        if (count($group['children']) && $c_p == false) {
          $parcel_terminals[] = $group;
        } elseif (count($group['children'])) {
          $parcel_terminals = array_merge($parcel_terminals, $group['children']);
        }
        $counter++;
      }
    }
    if ($c_p != false) {
      usort($parcel_terminals, function ($a, $b) {
        return $b['distance'] > $a['distance'] ? -1 : 1;
      });
      return array_slice($parcel_terminals, 0, 8);
    }
    return $parcel_terminals;
  }

  function calc_distance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
  {
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
      cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return round($angle * $earthRadius / 1000, 2);
  }

  function search_postcode($postcode, $country)
  {
    if ($postcode == "") return false;
    $postcode = urlencode($postcode);
    $data = file_get_contents("http://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?singleLine=" . $postcode . "," . $country . "&category=&outFields=Postal&maxLocations=1&forStorage=false&f=pjson");
    if ($data) {
      $data = json_decode($data);
    } else {
      return false;
    }
    if (isset($data->candidates) && count($data->candidates)) {
      if ($data->candidates[0]->score > 90) {
        return array($data->candidates[0]->location->y, $data->candidates[0]->location->x);
      }
    }
    return false;
  }

  add_action("wp_ajax_omniva_terminals_json", "omniva_terminals_json");
  add_action("wp_ajax_nopriv_omniva_terminals_json", "omniva_terminals_json");

  function omniva_terminals_json()
  {
    if (!wp_verify_nonce($_REQUEST['nonce'], "omniva_terminals_json_nonce")) {
      exit("Not allowed");
    }
    $json_terminals = generate_json_terminals($_REQUEST['q'], $_REQUEST['country']);
    echo json_encode($json_terminals);
    die();
  }

} 
