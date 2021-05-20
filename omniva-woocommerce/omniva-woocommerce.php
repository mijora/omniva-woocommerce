<?php
/**
 * Plugin Name: Omniva shipping
 * Description: Official Omniva shipping plugin for WooCommerce
 * Author: Omniva
 * Author URI: https://www.omniva.lt/
 * Plugin URI: https://iskiepiai.omnivasiunta.lt/
 * Version: 1.8.0
 * Domain Path: /languages
 * Text Domain: omnivalt
 * Requires at least: 5.1
 * Tested up to: 5.7.2
 * WC requires at least: 3.0.0
 * WC tested up to: 5.3.0
 * Requires PHP: 7.2
 */

if (!defined('WPINC')) {
  die;
}

define('OMNIVA_VERSION', '1.8.0');
define('OMNIVA_DIR', plugin_dir_path(__FILE__));
define('OMNIVA_URL', plugin_dir_url(__FILE__));

add_action( 'init', 'omnivalt_load_textdomain' );

function omnivalt_load_textdomain() {
  load_plugin_textdomain( 'omnivalt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
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

// start cron job for loaction update

add_filter('cron_schedules', 'cron_add_weekly');

function cron_add_weekly($schedules)
{
  $schedules['daily'] = array(
    'interval' => 86400,
    'display' => __('Once daily')
  );
  return $schedules;
}

register_activation_hook(__FILE__, 'omnivalt_activation');

function omnivalt_activation()
{
  if (!wp_next_scheduled('omnivalt_location_update')) {
    wp_schedule_event(time(), 'daily', 'omnivalt_location_update');
  }
}

add_action('omnivalt_location_update', 'do_daily_update');

function do_daily_update()
{
  $url = 'https://www.omniva.ee/locations.json';
  $fp = fopen(dirname(__file__) . '/' . "locations_new.json", "w");
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_FILE, $fp);
  curl_setopt($curl, CURLOPT_TIMEOUT, 60);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  $data = curl_exec($curl);
  curl_close($curl);
  fclose($fp);

  $new_data = file_get_contents(dirname(__file__) . '/' . "locations_new.json");
  if (json_decode($new_data)) {
    rename(dirname(__file__) . '/' . "locations_new.json", dirname(__file__) . '/' . "locations.json");
  }
}

register_deactivation_hook(__FILE__, 'omnivalt_deactivation');

function omnivalt_deactivation()
{
  wp_clear_scheduled_hook('omnivalt_location_update');
}

// end cron job for loaction update

/*
* Check if WooCommerce is active
*/

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

  require_once plugin_dir_path(__FILE__) . 'includes/class-emails.php';
  // add select2 js script

  add_action('wp_enqueue_scripts', 'omnivalt_scripts', 99);
  function omnivalt_scripts()
  {
    if (is_cart() || is_checkout()) {

      //problem with map
      // wp_dequeue_script('wc-password-strength-meter');

      //wp_enqueue_style('esri-classes', 'https://js.arcgis.com/4.10/esri/css/main.css');


      /*
      wp_enqueue_script('omniva', plugins_url('/js/omnivalt.js', __FILE__) , array(
        'jquery'
      ));
      */

      wp_enqueue_script('omniva-helper', plugins_url('/js/omniva_helper.js', __FILE__), array('jquery'), OMNIVA_VERSION);
      wp_enqueue_script('omniva', plugins_url('/js/omniva.js', __FILE__), array('jquery'), OMNIVA_VERSION);

      wp_enqueue_style('omniva', plugins_url('/css/omniva.css', __FILE__), array(), OMNIVA_VERSION);
      /*
      wp_localize_script('omniva', 'omnivadata', array(
        'ajax_url' => admin_url('admin-ajax.php')
      ));
      */
      /*
      wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array(
        'jquery'
      ));
      wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css');
      */
      wp_enqueue_script('leaflet', plugins_url('/js/leaflet.js', __FILE__), array('jquery'), null, true);
      wp_enqueue_style('leaflet', plugins_url('/css/leaflet.css', __FILE__));

      //wp_enqueue_style('icons-classes', 'https://use.fontawesome.com/releases/v5.3.1/css/all.css');
      //wp_register_script('secondscript', 'https://js.arcgis.com/4.11/', array('jquery'), null, true);
      //wp_enqueue_script('secondscript');

      //wp_enqueue_script('omniva-map', plugins_url('/js/omnivaMap.js?20190530', __FILE__) , array('jquery'),null,true);      

      wp_localize_script('omniva', 'omnivadata', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'omniva_plugin_url' => plugin_dir_url(__FILE__),
        'text_select_terminal' => __('Select terminal', 'omnivalt'),
        'text_search_placeholder' => __('Enter postcode', 'omnivalt'),
        'not_found' => __('Place not found', 'omnivalt'),
        'text_enter_address' => __('Enter postcode/address', 'omnivalt'),
        'text_show_in_map' => __('Show in map', 'omnivalt'),
        'text_show_more' => __('Show more', 'omnivalt'),
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
  		wp_enqueue_style('omnivalt_admin_settings', plugins_url('/css/omniva_admin_settings.css', __FILE__), array(), OMNIVA_VERSION);
      wp_enqueue_script('omniva_admin_settings', plugins_url( '/js/omniva_admin_settings.js', __FILE__ ), array('jquery'), OMNIVA_VERSION );
  	}
  }

  add_action('admin_enqueue_scripts', 'omnivalt_admin_order_scripts');
  function omnivalt_admin_order_scripts($hook)
  {
    global $post;

    if ($hook == 'post-new.php' || $hook == 'post.php') {
      if ($post->post_type === 'shop_order') {
        wp_enqueue_script('omniva_admin_order', plugins_url( '/js/omniva_admin_order.js', __FILE__ ), array('jquery'), OMNIVA_VERSION );
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
    if (!class_exists('Omnivalt_Shipping_Method')) {
      class Omnivalt_Shipping_Method extends WC_Shipping_Method

      {
        /**
         * Constructor for your shipping class
         *
         * @access public
         * @return void
         */
        public $errors = array();

        public function __construct()
        {
          $this->id = 'omnivalt';
          $this->method_title = __('Omniva Shipping', 'omnivalt');
          $this->method_description = __('Shipping Method for Omniva', 'omnivalt');

          // Availability & Countries

          $this->availability = 'including';
          $this->countries = array(
            'LT',
            'LV',
            'EE'
          );

          $this->init();

          $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
          $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Omnivalt Shipping', 'omnivalt');
        }

        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        function init()
        {

          // Load the settings API

          $this->init_form_fields();
          $this->init_settings();

          $this->title      = $this->get_option('title');
          // Save settings in admin if you have any defined

          add_action('woocommerce_update_options_shipping_' . $this->id, array(
            $this,
            'process_admin_options'
          ));
          //$this->updateT();
        }

        public function updateT()
        { //die('functions works');
          $url = 'https://www.omniva.ee/locations.json';

          $fp = fopen(dirname(__file__) . '/' . "locations.json", "w");
          $curl = curl_init();
          curl_setopt($curl, CURLOPT_URL, $url);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl, CURLOPT_HEADER, false);
          curl_setopt($curl, CURLOPT_FILE, $fp);
          curl_setopt($curl, CURLOPT_TIMEOUT, 60);
          curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
          curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
          $data = curl_exec($curl);
          curl_close($curl);
          fclose($fp);
        }
        /**
         * Load settings form
         */
        function admin_options()
        {
          ?>
          <h2><?php echo $this->method_title; ?></h2>
          <p><?php echo $this->method_description; ?></p>
          <table class="form-table omniva-settings">
            <?php $this->generate_settings_html(); ?>
          </table>
          <?php
        }
        /**
         * Define settings field for this shipping
         * @return void
         */
        function init_form_fields()
        {
          $fields = array(
            'enabled' => array(
              'title' => __('Enable', 'omnivalt'),
              'type' => 'checkbox',
              'description' => __('Enable this shipping.', 'omnivalt'),
              'default' => 'yes'
            ),
            'hr_api' => array(
              'type' => 'hr'
            ),
            'api_url' => array(
              'title' => __('Api URL', 'omnivalt'),
              'type' => 'text',
              'default' => 'https://edixml.post.ee',
              'description' => __('Change only if want use custom Api URL.', 'omnivalt') . ' ' . sprintf(__('Default is %s', 'omnivalt'),'<code>https://edixml.post.ee</code>'),
            ),
            'api_user' => array(
              'title' => __('Api user', 'omnivalt'),
              'type' => 'text',
            ),
            'api_pass' => array(
              'title' => __('Api user password', 'omnivalt'),
              'type' => 'password',
            ),
            'hr_shop' => array(
              'type' => 'hr'
            ),
            'company' => array(
              'title' => __('Company name', 'omnivalt'),
              'type' => 'text',
            ),
            'bank_account' => array(
              'title' => __('Bank account', 'omnivalt'),
              'type' => 'text',
            ),
            'shop_name' => array(
              'title' => __('Shop name', 'omnivalt'),
              'type' => 'text',
            ),
            'shop_city' => array(
              'title' => __('Shop city', 'omnivalt'),
              'type' => 'text',
            ),
            'shop_address' => array(
              'title' => __('Shop address', 'omnivalt'),
              'type' => 'text',
            ),
            'shop_postcode' => array(
              'title' => __('Shop postcode', 'omnivalt'),
              'type' => 'text',
              'description' => sprintf(__('Example for Latvia: %1$s. Example for other countries: %2$s.', 'omnivalt'), '<code>LV-0123</code>', '<code>01234</code>'),
            ),
            'shop_countrycode' => array(
            	'title' => __('Shop country code', 'omnivalt'),
            	'type'    => 'select',
            	'class' => 'checkout-style pickup-point',
            	'options' => array(
                'EE'  => 'EE - ' . __('Estonia', 'omnivalt'),
                'LV' => 'LV - ' . __('Latvia', 'omnivalt'),
                'LT' => 'LT - ' . __('Lithuania', 'omnivalt'),
            	),
            	'default' => 'LT',
        		),
            'shop_phone' => array(
              'title' => __('Shop phone number', 'omnivalt'),
              'type' => 'text',
            ),
            'pick_up_start' => array(
              'title' => __('Pick up time start', 'omnivalt'),
              'type' => 'text',
              'placeholder' => '08:00',
              'description' => sprintf(__('Allowed formats: %1$s. Default time is %2$s, if incorrect value is entered or field is empty.', 'omnivalt'),'<i>07:00, 7:00, 7</i>', '08:00'),
            ),
            'pick_up_end' => array(
              'title' => __('Pick up time end', 'omnivalt'),
              'type' => 'text',
              'placeholder' => '17:00',
              'description' => sprintf(__('Allowed formats: %1$s. Default time is %2$s, if incorrect value is entered or field is empty.', 'omnivalt'),'<i>09:00, 9:00, 9</i>', '17:00'),
            ),
            'send_off' => array(
              'title' => __('Send off type', 'omnivalt'),
              'type' => 'select',
              'description' => __('Send from store type.', 'omnivalt'),
              'options' => array(
                'pt' => __('Parcel terminal', 'omnivalt'),
                'c' => __('Courrier', 'omnivalt')
              )
            ),
          );
          $fields['hr_methods'] = array(
            'type' => 'hr'
          );
          $fields['method_pt'] = array(
            'title' => __('Parcel terminal', 'omnivalt'),
            'type' => 'checkbox',
            'description' => __('Show parcel terminal method in checkout.', 'omnivalt')
          );
          $fields['method_c'] = array(
            'title' => __('Courrier', 'omnivalt'),
            'type' => 'checkbox',
            'description' => __('Show courrier method in checkout.', 'omnivalt')
          );
          foreach ($this->countries as $country) {
            $fields['prices_'.$country] = array(
              'type' => 'prices_box',
              'lang' => $country,
            );
          }
          $fields['hr_settings'] = array(
            'type' => 'hr'
          );
          $fields['weight'] = array(
            'title' => sprintf(__('Max cart weight (%s) for terminal', 'omnivalt'),'kg'),
            'type' => 'number',
            'custom_attributes' => array(
              'step' => 0.001,
              'min' => 0
            ),
            'description' => __('Maximum allowed all cart products weight for parcel terminals.', 'omnivalt'),
            'default' => 30,
            'class' => 'omniva_terminal'
          );
          $fields['weight_c'] = array(
            'title' => sprintf(__('Max cart weight (%s) for courier', 'omnivalt'),'kg'),
            'type' => 'number',
            'custom_attributes' => array(
              'step' => 0.001,
              'min' => 0
            ),
            'description' => __('Maximum allowed all cart products weight for courier.', 'omnivalt'),
            'default' => 100,
            'class' => 'omniva_courier'
          );
          $fields['size_pt'] = array(
            'title' => sprintf(__('Max cart size (%s) for terminal', 'omnivalt'),get_option('woocommerce_dimension_unit')),
            'type' => 'dimensions',
            'description' => __('Maximum cart size for parcel terminals. Leave all empty to disable.', 'omnivalt') . '<br/>' . __('Preliminary cart size is calculated by trying to fit all products by taking their dimensions (boxes) indicated in their settings.', 'omnivalt'),
            'class' => 'omniva_terminal'
          );
          /*$fields['size_c'] = array(
            'title' => sprintf(__('Max size (%s) for courier', 'omnivalt'),get_option('woocommerce_dimension_unit')),
            'type' => 'dimensions',
            'description' => __('Maximum product size for courier. Leave all empty to disable.', 'omnivalt') . '<br/>' . __('If the length, width or height of at least one product exceeds the specified values, then it will not be possible to select the courier delivery method for the whole cart.', 'omnivalt')
          );*/
          $fields['show_map'] = array(
            'title' => __('Map', 'omnivalt'),
            'type' => 'checkbox',
            'description' => __('Show map of terminals.', 'omnivalt'),
            'default' => 'yes',
            'class' => 'omniva_terminal'
          );
          $fields['auto_select'] = array(
            'title' => __('Automatic terminal selection', 'omnivalt'),
            'type' => 'checkbox',
            'description' => __('Automatically select terminal by postcode.', 'omnivalt'),
            'default' => 'yes',
            'class' => 'omniva_terminal'
          );
          $fields['print_type'] = array(
            'title' => __('Labels print type', 'omnivalt'),
            'type' => 'select',
            'options' => array(
              '1' => __('Original (single label)', 'omnivalt'),
              '4' => __('A4 (4 labels)', 'omnivalt')
            ),
            'default' => '4',
            'description' => __('How many labels to print per page.', 'omnivalt')
          );
          $fields['email_created_label'] = array(
            'title' => __('Send email when a label is created', 'omnivalt'),
            'type' => 'checkbox',
            'description' => __('Send an email to customer with tracking code, when the label is generated.', 'omnivalt') . '<br/>' . sprintf(__('To override email template, copy template file from %1$s to your theme %2$s directory.', 'omnivalt'), '<code>wp-content/plugins/omniva-woocommerce/templates/emails</code>', '<code>wp-content/themes/theme-name/omniva/emails</code>'),
            'default' => ''
          );
          $fields['email_created_label_subject'] = array(
            'title' => '',
            'type' => 'text',
            'description' => __('Custom email subject (this field value not translating into other languages).', 'omnivalt'),
            'placeholder' => __('Your order shipment has been registered', 'omnivalt')
          );
          $fields['send_email_on_arrive'] = array(
            'title' => __('Send email on shipment arrive', 'omnivalt'),
            'type' => 'checkbox',
            'description' => __('Send email to customer from Omniva, when the shipment arrives at the terminal.', 'omnivalt'),
            'default' => '',
            'class' => 'omniva_terminal'
          );
          $fields['hr_debug'] = array(
            'type' => 'hr'
          );
          $fields['debug_mode'] = array(
            'title' => __('Enable debug mode', 'omnivalt'),
            'type' => 'checkbox',
            'description' => __('Enable request and response logging.', 'omnivalt'),
            'default' => ''
          );
          $fields['debugview_request'] = array(
            'type' => 'debug_window',
            'file_path' => OMNIVA_DIR . 'debug/request.txt',
            'title' => __('Last logged request', 'omnivalt'),
            'class' => 'omniva_debug'
          );
          $fields['debugview_response'] = array(
            'type' => 'debug_window',
            'file_path' => OMNIVA_DIR . 'debug/response.txt',
            'title' => __('Last logged response', 'omnivalt'),
            'class' => 'omniva_debug'
          );
					$this->form_fields = $fields;
        }

        public function generate_hr_html( $key, $value ) {
          $class = (isset($value['class'])) ? $value['class'] : '';
        	$html = '<tr valign="top"><td colspan="2"><hr class="' . $class . '"></td></tr>';
    			return $html;
        }
        public function generate_empty_html( $key, $value ) {
          $class = (isset($value['class'])) ? $value['class'] : '';
        	$html = '<tr valign="top"><td colspan="2" class="' . $class . '"></td></tr>';
    			return $html;
        }

        public function generate_prices_box_html( $key, $value ) {
        	$box_key = $this->get_field_key($key);
          $html = '';
          if (isset($value['lang'])) {
            $flag_img_url = OMNIVA_URL . 'css/images/flags/' . strtolower($value['lang']) . '.png';
            $fields = array(
              'pt_enable' => 'pt_enable_' . $value['lang'],
              'pt_price_single' => 'pt_price_' . $value['lang'],
              'pt_enable_free_from' => 'pt_price_' . $value['lang'] . '_enFree',
              'pt_free_from' => 'pt_price_' . $value['lang'] . '_FREE',
              'pt_enable_coupon' => 'pt_price_' . $value['lang'] . '_enCoupon',
              'pt_coupon' => 'pt_price_' . $value['lang'] . '_coupon',
              'c_enable' => 'c_enable_' . $value['lang'],
              'c_price_single' => 'c_price_' . $value['lang'],
              'c_free_from' => 'c_price_' . $value['lang'] . '_FREE',
              'c_enable_free_from' => 'c_price_' . $value['lang'] . '_enFree',
              'c_enable_coupon' => 'c_price_' . $value['lang'] . '_enCoupon',
              'c_coupon' => 'c_price_' . $value['lang'] . '_coupon',
            );
            $saved_values = json_decode($this->get_option($key));
            $values = array();
            foreach ($fields as $id => $field) {
            	$cur_value = (isset($saved_values->{$id})) ? $saved_values->{$id} : '';
            	/* -Compatibility with old data- */
            	$old_value = $this->get_option($field);
            	$is_old = false;
            	if ($cur_value === '' && (!empty($old_value) || $old_value === 0 || $old_value === '0')) {
            		$cur_value = $old_value;
            		$is_old = true;
            	}
            	/* -End of Compatibility with old data- */
              $values[$id] = array(
                'id' => $field,
                'key' => $this->get_field_key($field),
                'value' => $cur_value,
                'is_old' => $is_old,
              );
            }

            $args = array(
              'posts_per_page'   => -1,
              'orderby'          => 'title',
              'order'            => 'asc',
              'post_type'        => 'shop_coupon',
              'post_status'      => 'publish',
            );  
            $coupons = get_posts($args);

            ob_start();
            ?>
            <tr class="row-prices" valign="top">
              <td colspan="2">
                <div class="prices_box">
                  <div class="pb-lang">
                    <img src="<?php echo $flag_img_url; ?>" alt="[<?php echo $value['lang']; ?>]">
                    <span><?php echo $value['lang'] . ' ' . __('prices','omnivalt'); ?></span>
                  </div>
                  <div class="pb-content">
                    <?php if (isset($values['pt_enable'])) : ?>
                      <div class="block-prices terminal">
                        <div class="sec-title">
                          <?php
                          $field_title = __('Enable parcel terminal','omnivalt');
                          $field_id = $values['pt_enable']['key'];
                          $field_name = $box_key . '[pt_enable]';
                          $field_checked = ($values['pt_enable']['value']) ? 'checked' : '';
                          /* -Compatibility with old data- */
                          if ($values['pt_price_single']['is_old'] && isset($values['pt_price_single']['value']) && $values['pt_price_single']['value'] !== '') {
                          	$field_checked = 'checked';
                          }
                          /* -End of Compatibility with old data- */
                          ?>
                          <label for="<?php echo $field_id; ?>"><?php echo __('Parcel terminal','omnivalt'); ?></label>
                          <div class="switcher" title="<?php echo $field_title; ?>">
                            <label class="switch">
                              <input type="checkbox" class="pt_enable" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" <?php echo $field_checked; ?> value="1">
                              <span class="slider round"></span>
                            </label>
                          </div>
                        </div>
                        <div class="sec-prices">
                          <?php if (isset($values['pt_price_single'])) : ?>
                            <div class="prices-single">
                              <?php
                              $field_id = $values['pt_price_single']['key'];
                              $field_name = $box_key . '[pt_price_single]';
                              $field_value = $values['pt_price_single']['value'];
                              if (empty($field_value) && $field_value != 0) {
                                $field_value = 2;
                              }
                              ?>
                              <label for="<?php echo $field_id; ?>"><?php echo __('Price','omnivalt'); ?>:</label>
                              <input class="input-text regular-input" type="number" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" value="<?php echo $field_value; ?>" step="0.01" min="0">
                            </div>
                          <?php endif; ?>
                          <?php if (isset($values['pt_free_from'])) : ?>
                            <div class="prices-free">
                              <?php
                              $field_id = $values['pt_enable_free_from']['key'];
                              $field_name = $box_key . '[pt_enable_free_from]';
                              $field_checked = ($values['pt_enable_free_from']['value']) ? 'checked' : '';
                              /* -Compatibility with old data- */
                              if ($values['pt_free_from']['is_old'] && isset($values['pt_free_from']['value']) && $values['pt_free_from']['value'] !== '') {
                          			$field_checked = 'checked';
                          		}
                          		/* -End of Compatibility with old data- */
                              ?>
                              <label>
                              	<input type="checkbox" class="pt_enable_free" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" <?php echo $field_checked; ?> value="1">
                              	<?php echo __('Free from','omnivalt'); ?>:
                              </label>
                              <?php
                              $field_id = $values['pt_free_from']['key'];
                              $field_name = $box_key . '[pt_free_from]';
                              $field_value = $values['pt_free_from']['value'];
                              if (empty($field_value) && $field_value != 0) {
                                $field_value = 100;
                              }
                              ?>
                              <input class="input-text regular-input price_free" type="number" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" value="<?php echo $field_value; ?>" step="0.01" min="0">
                            </div>
                          <?php endif; ?>
                          <?php if (isset($values['pt_coupon'])) : ?>
                            <div class="prices-coupon">
                              <?php
                              $field_id = $values['pt_enable_coupon']['key'];
                              $field_name = $box_key . '[pt_enable_coupon]';
                              $field_checked = ($values['pt_enable_coupon']['value']) ? 'checked' : '';
                              ?>
                              <label>
                                <input type="checkbox" class="pt_enable_coupon" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" <?php echo $field_checked; ?> value="1">
                                <?php echo __('Free with coupon','omnivalt'); ?>:
                              </label>
                              <?php
                              $field_id = $values['pt_coupon']['key'];
                              $field_name = $box_key . '[pt_coupon]';
                              $field_value = $values['pt_coupon']['value'];
                              ?>
                              <select id="<?php echo $field_id; ?>" class="select price_coupon" name="<?php echo $field_name; ?>">
                                <?php $selected = (empty($values['pt_coupon']['value'])) ? 'selected' : ''; ?>
                                <option <?php echo $selected; ?>>-</option>
                                <?php foreach($coupons as $coupon) : ?>
                                  <?php
                                  $coupon_value = strtolower($coupon->post_title);
                                  $coupon_title = $coupon->post_title;
                                  $selected = ($coupon_value == $values['pt_coupon']['value']) ? 'selected' : '';
                                  ?>
                                  <option value="<?php echo $coupon_value; ?>" <?php echo $selected; ?>><?php echo $coupon_title; ?></option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <?php if (isset($values['c_enable'])) : ?>
                      <div class="block-prices courier">
                        <div class="sec-title">
                          <?php
                          $field_title = __('Enable courier','omnivalt');
                          $field_id = $values['c_enable']['key'];
                          $field_name = $box_key . '[c_enable]';
                          $field_checked = ($values['c_enable']['value']) ? 'checked' : '';
                          /* -Compatibility with old data- */
                          if ($values['c_price_single']['is_old'] && isset($values['c_price_single']['value']) && $values['c_price_single']['value'] !== '') {
                          	$field_checked = 'checked';
                          }
                          /* -End of Compatibility with old data- */
                          ?>
                          <label for="<?php echo $field_id; ?>"><?php echo __('Courier','omnivalt'); ?></label>
                          <div class="switcher" title="<?php echo $field_title; ?>">
                            <label class="switch">
                              <input type="checkbox" class="c_enable" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" <?php echo $field_checked; ?> value="1">
                              <span class="slider round"></span>
                            </label>
                            </label>
                          </div>
                        </div>
                        <div class="sec-prices">
                          <?php if (isset($values['c_price_single'])) : ?>
                            <div class="prices-single">
                              <?php
                              $field_id = $values['c_price_single']['key'];
                              $field_name = $box_key . '[c_price_single]';
                              $field_value = $values['c_price_single']['value'];
                              if (empty($field_value) && $field_value != 0) {
                                $field_value = 3;
                              }
                              ?>
                              <label for="<?php echo $field_id; ?>"><?php echo __('Price','omnivalt'); ?>:</label>
                              <input class="input-text regular-input" type="number" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" value="<?php echo $field_value; ?>" step="0.01" min="0">
                            </div>
                          <?php endif; ?>
                          <?php if (isset($values['c_free_from'])) : ?>
                            <div class="prices-free">
                              <?php
                              $field_id = $values['c_enable_free_from']['key'];
                              $field_name = $box_key . '[c_enable_free_from]';
                              $field_checked = ($values['c_enable_free_from']['value']) ? 'checked' : '';
                              /* -Compatibility with old data- */
                              if ($values['c_free_from']['is_old'] && isset($values['c_free_from']['value']) && $values['c_free_from']['value'] !== '') {
                          			$field_checked = 'checked';
                          		}
                          		/* -End of Compatibility with old data- */
                              ?>
                              <label>
                              	<input type="checkbox" class="c_enable_free" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" <?php echo $field_checked; ?> value="1">
                              	<?php echo __('Free from','omnivalt'); ?>:
                              </label>
                              <?php
                              $field_id = $values['c_free_from']['key'];
                              $field_name = $box_key . '[c_free_from]';
                              $field_value = $values['c_free_from']['value'];
                              if (empty($field_value) && $field_value != 0) {
                                $field_value = 100;
                              }
                              ?>
                              <input class="input-text regular-input price_free" type="number" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" value="<?php echo $field_value; ?>" step="0.01" min="0">
                            </div>
                          <?php endif; ?>
                          <?php if (isset($values['c_coupon'])) : ?>
                            <div class="prices-coupon">
                              <?php
                              $field_id = $values['c_enable_coupon']['key'];
                              $field_name = $box_key . '[c_enable_coupon]';
                              $field_checked = ($values['c_enable_coupon']['value']) ? 'checked' : '';
                              ?>
                              <label>
                                <input type="checkbox" class="c_enable_coupon" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" <?php echo $field_checked; ?> value="1">
                                <?php echo __('Free with coupon','omnivalt'); ?>:
                              </label>
                              <?php
                              $field_id = $values['c_coupon']['key'];
                              $field_name = $box_key . '[c_coupon]';
                              $field_value = $values['c_coupon']['value'];
                              ?>
                              <select id="<?php echo $field_id; ?>" class="select price_coupon" name="<?php echo $field_name; ?>">
                                <?php $selected = (empty($values['c_coupon']['value'])) ? 'selected' : ''; ?>
                                <option <?php echo $selected; ?>>-</option>
                                <?php foreach($coupons as $coupon) : ?>
                                  <?php
                                  $coupon_value = strtolower($coupon->post_title);
                                  $coupon_title = $coupon->post_title;
                                  $selected = ($coupon_value == $values['c_coupon']['value']) ? 'selected' : '';
                                  ?>
                                  <option value="<?php echo $coupon_value; ?>" <?php echo $selected; ?>><?php echo $coupon_title; ?></option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
            </tr>
            <?php
            $html = ob_get_contents();
            ob_end_clean();
          }
          return $html;
        }
        public function validate_prices_box_field( $key, $value ) {
    			$values = wp_json_encode($value);
    			return $values;
 				}

        public function generate_dimensions_html( $key, $value ) {
        	$field_key = $this->get_field_key($key);
          $field_class = (isset($value['class'])) ? $value['class'] : '';

        	if ( $this->get_option($key) !== '' ) {
      			$dim_values = $this->get_option($key);
      			if ( is_string($dim_values) ) {
        			$dim_values = json_decode($this->get_option($key), true);
      			}
    			} else {
      			$dim_values = array();
    			}

        	ob_start();
        	?>
        	<tr valign="top">
        		<th scope="row" class="titledesc">
        			<label><?php echo esc_html($value['title']); ?></label>
        		</th>
        		<td class="forminp">
        			<fieldset class="field-dimensions <?php echo $field_class; ?>">
        				<input type="number" value="<?php echo $dim_values[0]; ?>"
                  id="<?php echo esc_html($field_key); ?>"
                  name="<?php echo esc_html($field_key); ?>[0]"
                  min="0.001" step="0.001" placeholder="<?php echo __('Length','omnivalt'); ?>">
                <span>x</span>
                <input type="number" value="<?php echo $dim_values[1]; ?>"
                  id="<?php echo esc_html($field_key); ?>"
                  name="<?php echo esc_html($field_key); ?>[1]"
                  min="0.001" step="0.001" placeholder="<?php echo __('Width','omnivalt'); ?>">
                <span>x</span>
                <input type="number" value="<?php echo $dim_values[2]; ?>"
                  id="<?php echo esc_html($field_key); ?>"
                  name="<?php echo esc_html($field_key); ?>[2]"
                  min="0.001" step="0.001" placeholder="<?php echo __('Height','omnivalt'); ?>">
                 <span><?php echo get_option('woocommerce_dimension_unit'); ?></span>
                <?php if (!empty($value['description'])) : ?>
            			<p class="description"><?php echo __($value['description']); ?></p>
          			<?php endif; ?>
        			</fieldset>
        		</td>
        	</tr>
        	<?php
        	$html = ob_get_contents();
    			ob_end_clean();
    			return $html;
        }
        public function validate_dimensions_field( $key, $value ) {
    			$values = wp_json_encode($value);
    			return $values;
 				}

        public function generate_debug_window_html( $key, $value ) {
          $field_class = (isset($value['class'])) ? $value['class'] : '';
          $file_path = (isset($value['file_path'])) ? $value['file_path'] : '';
          if (!empty($file_path) && file_exists($file_path)) {
            $file = fopen($file_path, 'r');
            $file_content = fread($file,filesize($file_path));
            fclose($file);
          } else {
            $file_content = '- ' . __('Debug file still not created','omnivalt') . ' -';
          }
          ob_start();
          ?>
          <tr class="omniva-debugview" valign="top">
            <th scope="row" class="titledesc"></th>
            <td class="forminp">
              <fieldset class="field-debug <?php echo $field_class; ?>">
                <span class="title"><?php echo esc_html($value['title']); ?></span>
                <textarea readonly rows="11" style="width:100%"><?php echo $file_content; ?></textarea>
              </fieldset>
            </td>
          </tr>
          <?php
          $html = ob_get_contents();
          ob_end_clean();
          return $html;
        }

        /**
         * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
         *
         * @access public
         * @param mixed $package
         * @return void
         */
        public function calculate_shipping($package = array())
        {
          $weight = 0;
          $cost = 0;
          $country = $package["destination"]["country"];

          global $woocommerce;
          $cart_amount = $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total;

          $dimension_pass_pt = true;
          $max_dimension_pt = (isset($this->settings['size_pt'])) ? json_decode($this->settings['size_pt']) : array(999999,999999,999999);
          $products_for_dim = array();
          foreach ($package['contents'] as $item_id => $values) {
            $_product = $values['data'];
            if ($_product->get_weight()) {
              $weight = $weight + $_product->get_weight() * $values['quantity'];
            }
            for ($i=0;$i<$values['quantity'];$i++) {
            	array_push($products_for_dim, $_product);
            }
          }
          if ( (isset($max_dimension_pt[0]) && !empty($max_dimension_pt[0]))
            || (isset($max_dimension_pt[1]) && !empty($max_dimension_pt[1]))
            || (isset($max_dimension_pt[2]) && !empty($max_dimension_pt[2])) )
          {
            $dimension_pass_pt = $this->cart_size_prediction($products_for_dim, $max_dimension_pt);
          }

          $weight = wc_get_weight($weight, 'kg');
          if (isset($this->settings['weight'])) {
            $weight_pass_pt = (floatval($this->settings['weight']) >= $weight || floatval($this->settings['weight']) == 0);
          } else {
            $weight_pass_pt = true;
          }
          if (isset($this->settings['weight_c'])) {
            $weight_pass_c = (floatval($this->settings['weight_c']) >= $weight || floatval($this->settings['weight_c']) == 0);
          } else {
            $weight_pass_c = true;
          }

          $prices_key = (in_array($country, $this->countries)) ? 'prices_' . $country : 'prices_LT';
          $prices = (isset($this->settings[$prices_key])) ? json_decode($this->settings[$prices_key]) : array();

          if ($this->settings['method_pt'] == 'yes' && $weight_pass_pt && $dimension_pass_pt) {
            $show = true;
            /* -For compatibility with old version settings- */
            if ( in_array($country, $this->countries) ) {
              $pt_price_name = (isset($this->settings['pt_price_' . $country])) ? 'pt_price_' . $country : 'pt_price' . $country;
              $pt_free_name = (isset($this->settings['pt_price_' . $country . '_FREE'])) ? 'pt_price_' . $country . '_FREE' : 'pt_price' . $country . '_FREE';
              if ($country == 'LT') {
                $pt_price_name = (isset($this->settings['pt_price_LT'])) ? 'pt_price_LT' : 'pt_price';
                $pt_free_name = (isset($this->settings['pt_price_LT_FREE'])) ? 'pt_price_LT_FREE' : 'pt_priceFREE';
              }
            } else {
              $pt_price_name = (isset($this->settings['pt_price_LT'])) ? 'pt_price_LT' : 'pt_price';
              $pt_free_name = (isset($this->settings['pt_price_LT_FREE'])) ? 'pt_price_LT_FREE' : 'pt_priceFREE';
            }
            $amount = isset($this->settings[$pt_price_name]) ? $this->settings[$pt_price_name] : '';
            $amount_free = isset($this->settings[$pt_free_name]) ? floatval($this->settings[$pt_free_name]) : 100;
            if (isset($this->settings[$pt_price_name]) && $amount === '') 
              $show = false;
            if ($cart_amount >= $amount_free && $amount_free > 0)
              $amount = 0.0;
            /* -End of compatibility- */
            $amount = (isset($prices->pt_price_single)) ? $prices->pt_price_single : $amount;
            $amount_free = (isset($prices->pt_free_from)) ? $prices->pt_free_from : $amount_free;
            if (!isset($prices->pt_enable)) {
            	$show = false;
            }
            if (isset($prices->pt_enable_free_from)) {
            	if ($cart_amount >= $amount_free) $amount = 0.0;
            }
            if (isset($prices->pt_enable_coupon)) {
              if (isset($prices->pt_coupon) && !empty($package["applied_coupons"])) {
                foreach ($package["applied_coupons"] as $coupon) {
                  if ($prices->pt_coupon == $coupon) $amount = 0.0;
                }
              }
            }

            $rate = array(
              'id' => 'omnivalt_pt',
              'label' => __('Omniva parcel terminal', 'omnivalt'),
              'cost' => $amount
            );
            if ($show) {
            	$this->add_rate($rate);
            }
          }

          if ($this->settings['method_c'] == 'yes' && $weight_pass_c /*&& $dimension_pass_c*/) {
            $show = true;
            /* -For compatibility with old version settings- */
            if ( in_array($country, $this->countries) ) {
              $c_price_name = (isset($this->settings['c_price_' . $country])) ? 'c_price_' . $country : 'c_price' . $country;
              $c_free_name = (isset($this->settings['c_price_' . $country . '_FREE'])) ? 'c_price_' . $country . '_FREE' : 'pt_price_C_' . $country . '_FREE';
              if ($country == 'LT') {
                $c_price_name = (isset($this->settings['c_price_LT'])) ? 'c_price_LT' : 'c_price';
                $c_free_name = (isset($this->settings['c_price_LT_FREE'])) ? 'c_price_LT_FREE' : 'pt_price_C_FREE';
              }
            } else {
              $c_price_name = (isset($this->settings['c_price_LT'])) ? 'c_price_LT' : 'c_price';
              $c_free_name = (isset($this->settings['c_price_LT_FREE'])) ? 'c_price_LT_FREE' : 'pt_price_C_FREE';
            }
            $amountC = isset($this->settings[$c_price_name]) ? $this->settings[$c_price_name] : '';
            $amountC_free = isset($this->settings[$c_free_name]) ? floatval($this->settings[$c_free_name]) : 100;
            if (isset($this->settings[$c_price_name]) && $amountC === '') 
              $show = false;
            if ($cart_amount >= $amountC_free && $amountC_free > 0)
              $amountC = 0.0;
            /* -End of compatibility- */
            $amountC = (isset($prices->c_price_single)) ? $prices->c_price_single : $amountC;
            $amountC_free = (isset($prices->c_free_from)) ? $prices->c_free_from : $amountC_free;
            if (!isset($prices->c_enable)) {
            	$show = false;
            }
            if (isset($prices->c_enable_free_from)) {
            	if ($cart_amount >= $amountC_free) $amountC = 0.0;
            }
            if (isset($prices->c_enable_coupon)) {
              if (isset($prices->c_coupon) && !empty($package["applied_coupons"])) {
                foreach ($package["applied_coupons"] as $coupon) {
                  if ($prices->c_coupon == $coupon) $amountC = 0.0;
                }
              }
            }

            $rate = array(
              'id' => 'omnivalt_c',
              'label' => __('Omniva courrier', 'omnivalt'),
              'cost' => $amountC
            );
            if ($show) {
            	$this->add_rate($rate);
            }
          }
        }

        private function cart_size_prediction($products, $max_dimension) {
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
          		&& $prod_dim_width <= $max_dim_width && $prod_dim_height <= $max_dim_height
          	) {
          		$all_cart_dim_length = $all_cart_dim_length + $prod_dim_length;
          		$all_cart_dim_width = ($prod_dim_width > $all_cart_dim_width) ? $prod_dim_width : $all_cart_dim_width;
          		$all_cart_dim_height = ($prod_dim_height > $all_cart_dim_height) ? $prod_dim_height : $all_cart_dim_height;
          	}
          	//Add to width
          	else if ( ($prod_dim_width + $all_cart_dim_width) <= $max_dim_width 
          		&& $prod_dim_length <= $max_dim_length && $prod_dim_height <= $max_dim_height
          	) {
          		$all_cart_dim_length = ($prod_dim_length > $all_cart_dim_length) ? $prod_dim_length : $all_cart_dim_length;
          		$all_cart_dim_width = $all_cart_dim_width + $prod_dim_width;
          		$all_cart_dim_height = ($prod_dim_height > $all_cart_dim_height) ? $prod_dim_height : $all_cart_dim_height;
          	}
          	//Add to height
          	else if ( ($prod_dim_height + $all_cart_dim_height) <= $max_dim_height 
          		&& $prod_dim_length <= $max_dim_length && $prod_dim_width <= $max_dim_width
          	) {
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

        private function cod($order, $cod = 0, $amount = 0)
        {
          $company = $this->settings['company'];
          $bank_account = $this->settings['bank_account'];
          if ($cod) {
            return '<monetary_values>
              <cod_receiver>' . $company . '</cod_receiver>
              <values code="item_value" amount="' . $amount . '"/>
            </monetary_values>
            <account>' . $bank_account . '</account>
            <reference_number>' . $this->getReferenceNumber($order->ID) . '</reference_number>';
          } else {
            return '';
          }
        }

        protected static function getReferenceNumber($order_number)
        {
          $order_number = (string) $order_number;
          $kaal = array(7, 3, 1);
          $sl = $st = strlen($order_number);
          $total = 0;
          while ($sl > 0 and substr($order_number, --$sl, 1) >= '0') {
            $total += substr($order_number, ($st - 1) - $sl, 1) * $kaal[($sl % 3)];
          }
          $kontrollnr = ((ceil(($total / 10)) * 10) - $total);
          return $order_number . $kontrollnr;
        }

        public function get_tracking_number($id_order)
        {
          $order = get_post($id_order);
          $terminal_id = get_post_meta($id_order, '_omnivalt_terminal_id', true);

          // return $sql;
          // return $params['cart']->id_address_delivery;
          $wc_order = wc_get_order((int) $id_order);
          $weight_unit = get_option('woocommerce_weight_unit');
          $weight = get_post_meta($id_order, '_cart_weight', true);
          if ($weight_unit != 'kg') {
            $weight = wc_get_weight($weight, 'kg', $weight_unit);
          }
          $send_method = "";
          foreach ($wc_order->get_items('shipping') as $item_id => $shipping_item_obj) {
            $send_method        = $shipping_item_obj->get_method_id();
          }
          if ($send_method == 'omnivalt') {
            $send_method = get_post_meta($id_order, '_omnivalt_method', true);
          }
          if ($send_method == 'omnivalt_pt') $send_method = 'pt';
          if ($send_method == 'omnivalt_c') $send_method = 'c';
          $pickup_method = $this->settings['send_off'];
          $service = "";
          switch ($pickup_method . ' ' . $send_method) {
            case 'c pt':
              $service = "PU";
              break;

            case 'c c':
              $service = "QH";
              break;

            case 'pt c':
              $service = "PK";
              break;

            case 'pt pt':
              $service = "PA";
              break;

            default:
              $service = "";
              break;
          }

          $additionalService = '';
          $is_cod = false;
          if (get_post_meta($id_order, '_payment_method', true) == "cod")
            $is_cod = true;
          $client_email = $this->clean($wc_order->get_billing_email());
          $send_email_on_arrive = false;
          if (isset($this->settings['send_email_on_arrive'])) {
          	$send_email_on_arrive = ($this->settings['send_email_on_arrive'] == 'yes') ? true : false;
          }
          $emails = '';
          if (!empty($client_email) && $send_email_on_arrive && ($service == "PA" || $service == "PU")) {
          	$emails = '<email>' . $client_email . '</email>';
          	$additionalService .= '<option code="SF" />';
          }
          if ($service == "PA" || $service == "PU") $additionalService .= '<option code="ST" />';
          if ($is_cod) $additionalService .= '<option code="BP" />';
          if ($additionalService) {
            $additionalService = '<add_service>' . $additionalService . '</add_service>';
          }

          $parcel_terminal = "";
          if ($send_method == "pt") $parcel_terminal = 'offloadPostcode="' . $terminal_id . '" ';
          /* LV/EE fixes
          if ($parcel_terminal)
            $client_address = '<address ' . $parcel_terminal . ' />';
          else
	*/
          $client_post = $this->clean($wc_order->get_shipping_postcode());
          $client_city = $this->clean($wc_order->get_shipping_city());
          $client_address_1 = $this->clean($wc_order->get_shipping_address_1());
          $client_country = $this->clean($wc_order->get_shipping_country());
          if (empty($client_post) && empty($client_city) && empty($client_address_1) && empty($client_country)) {
          	$client_post = $this->clean($wc_order->get_billing_postcode());
          	$client_city = $this->clean($wc_order->get_billing_city());
          	$client_address_1 = $this->clean($wc_order->get_billing_address_1());
          	$client_country = $this->clean($wc_order->get_billing_country());
          }
          if (empty($client_country)) $client_country = 'LT';
          $client_phone = get_post_meta($id_order, '_shipping_phone', true);
          if (empty($client_phone)) {
            $client_phone = $this->clean($wc_order->get_billing_phone());
          }
          $client_name = $this->clean($wc_order->get_shipping_first_name());
          if (empty($client_name)) $client_name = $this->clean($wc_order->get_billing_first_name());
          $client_surname = $this->clean($wc_order->get_shipping_last_name());
          if (empty($client_surname)) $client_surname = $this->clean($wc_order->get_billing_last_name());

          $client_address = '<address postcode="' . $client_post . '" ' . $parcel_terminal . ' deliverypoint="' . $client_city . '" country="' . $client_country . '" street="' . $client_address_1 . '" />';
          $phones = '';
          if (!empty($client_phone)) $phones .= '<mobile>' . $client_phone . '</mobile>';
          $pickStart = $this->settings['pick_up_start'] ? $this->clean($this->settings['pick_up_start']) : '8:00';
          $pickFinish = $this->settings['pick_up_end'] ? $this->clean($this->settings['pick_up_end']) : '17:00';
          $pickDay = date('Y-m-d');
          if (time() > strtotime($pickDay . ' ' . $pickFinish)) $pickDay = date('Y-m-d', strtotime($pickDay . "+1 days"));
          $shop_country_iso = $this->clean($this->settings['shop_countrycode']);

          $xmlRequest = '
          <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://service.core.epmx.application.eestipost.ee/xsd">
             <soapenv:Header/>
             <soapenv:Body>
                <xsd:businessToClientMsgRequest>
                   <partner>' . $this->clean($this->settings['api_user']) . '</partner>
                   <interchange msg_type="info11">
                      <header file_id="' . Date('YmdHms') . '" sender_cd="' . $this->clean($this->settings['api_user']) . '" >                
                      </header>
                      <item_list>
                        ';
          // for ($i = 0; $i < $orderInfo['packs']; $i++):
          $xmlRequest .= '
                         <item service="' . $service . '" >
                            ' . $additionalService . '
                            <measures weight="' . $weight . '" />
                            ' . self::cod($order, $is_cod, get_post_meta($id_order, '_order_total', true)) . '
                            <receiverAddressee >
                               <person_name>' . $client_name . ' ' . $client_surname . '</person_name>
                              ' . $phones . '
                              ' . $emails . '
                              ' . $client_address . '
                            </receiverAddressee>
                            <!--Optional:-->
                            <returnAddressee>
                              <person_name>' . $this->clean($this->settings['shop_name']) . '</person_name>
                              <!--Optional:-->
                              <phone>' . $this->clean($this->settings['shop_phone']) . '</phone>
                              <address postcode="' . $this->clean($this->settings['shop_postcode']) . '" deliverypoint="' . $this->clean($this->settings['shop_city']) . '" country="' . $shop_country_iso . '" street="' . $this->clean($this->settings['shop_address']) . '" />
                            
                            </returnAddressee>
                         </item>';
          //endfor;
          $xmlRequest .= '
                      </item_list>
                   </interchange>
                </xsd:businessToClientMsgRequest>
             </soapenv:Body>
          </soapenv:Envelope>';

          return self::api_request($xmlRequest);
        }

        public function call_omniva()
        {
          $service = "QH";
          $is_cod = false;
          $parcel_terminal = "";
          $pickStart = $this->settings['pick_up_start'] ? $this->clean($this->settings['pick_up_start']) : '8:00';
          $pickStart = $this->get_formated_time($pickStart, '8:00');
          $pickFinish = $this->settings['pick_up_end'] ? $this->clean($this->settings['pick_up_end']) : '17:00';
          $pickFinish = $this->get_formated_time($pickFinish, '17:00');
          $pickDay = date('Y-m-d');
          if (time() > strtotime($pickDay . ' ' . $pickFinish)) $pickDay = date('Y-m-d', strtotime($pickDay . "+1 days"));
          $shop_country_iso = $this->clean($this->settings['shop_countrycode']);
          $xmlRequest = '
          <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://service.core.epmx.application.eestipost.ee/xsd">
             <soapenv:Header/>
             <soapenv:Body>
                <xsd:businessToClientMsgRequest>
                   <partner>' . $this->clean($this->settings['api_user']) . '</partner>
                   <interchange msg_type="info11">
                      <header file_id="' . Date('YmdHms') . '" sender_cd="' . $this->clean($this->settings['api_user']) . '" >                
                      </header>
                      <item_list>
                        ';
          // for ($i = 0; $i < $orderInfo['packs']; $i++):
          $xmlRequest .= '
                         <item service="' . $service . '" >
                            <measures weight="1" />
                            <receiverAddressee >
                               <person_name>' . $this->clean($this->settings['shop_name']) . '</person_name>
                              <!--Optional:-->
                              <phone>' . $this->clean($this->settings['shop_phone']) . '</phone>
                              <address postcode="' . $this->clean($this->settings['shop_postcode']) . '" deliverypoint="' . $this->clean($this->settings['shop_city']) . '" country="' . $shop_country_iso . '" street="' . $this->clean($this->settings['shop_address']) . '" />
                            </receiverAddressee>
                            <!--Optional:-->
                            <returnAddressee>
                              <person_name>' . $this->clean($this->settings['shop_name']) . '</person_name>
                              <!--Optional:-->
                              <phone>' . $this->clean($this->settings['shop_phone']) . '</phone>
                              <address postcode="' . $this->clean($this->settings['shop_postcode']) . '" deliverypoint="' . $this->clean($this->settings['shop_city']) . '" country="' . $shop_country_iso . '" street="' . $this->clean($this->settings['shop_address']) . '" />
                            </returnAddressee>
                            <onloadAddressee>
                              <person_name>' . $this->clean($this->settings['shop_name']) . '</person_name>
                              <!--Optional:-->
                              <phone>' . $this->clean($this->settings['shop_phone']) . '</phone>
                              <address postcode="' . $this->clean($this->settings['shop_postcode']) . '" deliverypoint="' . $this->clean($this->settings['shop_city']) . '" country="' . $shop_country_iso . '" street="' . $this->clean($this->settings['shop_address']) . '" />
                              <pick_up_time start="' . date("c", strtotime($pickDay . ' ' . $pickStart)) . '" finish="' . date("c", strtotime($pickDay . ' ' . $pickFinish)) . '"/>
                            </onloadAddressee>
                         </item>';
          //endfor;
          $xmlRequest .= '
                      </item_list>
                   </interchange>
                </xsd:businessToClientMsgRequest>
             </soapenv:Body>
          </soapenv:Envelope>';
          return self::api_request($xmlRequest);
        }

        private function get_formated_time($value, $value_if_not) {
        	if (!preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $value)) {
          	if ((string)(int)$value === $value || is_int($value)) {
          		return $value . ':00';
          	} else {
          		return $value_if_not;
          	}
          } else {
          	return $value;
          }
        }

        private function api_request($request)
        {
          $this->debug_request($request);
          $barcodes = array();;
          $errors = array();
          $url = $this->clean(preg_replace('{/$}', '', $this->settings['api_url'])) . '/epmx/services/messagesService.wsdl';
          $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "Content-length: " . strlen($request),
          );
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_HEADER, 0);
          curl_setopt($ch, CURLOPT_USERPWD, $this->clean($this->settings['api_user']) . ":" . $this->clean($this->settings['api_pass']));
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
          curl_setopt($ch, CURLOPT_TIMEOUT, 30);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          $xmlResponse = curl_exec($ch);
          $debug_response = $this->debug_response($xmlResponse);

          if ($xmlResponse === false) {
            $errors[] = curl_error($ch);
          } else {
            $errorTitle = '';
            if (strlen(trim($xmlResponse)) > 0) {
              $xmlResponse = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $xmlResponse);
              $xml = simplexml_load_string($xmlResponse);
              if (!is_object($xml)) {
                $errors[] = __('Response is in the wrong format', 'omnivalt');
              }

              if (is_object($xml) && is_object($xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo)) {
                foreach ($xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo as $data) {
                  $errors[] = $data->clientItemId . ' - ' . $data->barcode . ' - ' . $data->message;
                }
              }

              if (empty($errors)) {
                if (is_object($xml) && is_object($xml->Body->businessToClientMsgResponse->savedPacketInfo->barcodeInfo)) {
                  foreach ($xml->Body->businessToClientMsgResponse->savedPacketInfo->barcodeInfo as $data) {
                    $barcodes[] = (string) $data->barcode;
                  }
                }
              }
            }
          }

          // }

          if (!empty($errors)) {
            return array(
              'status' => false,
              'msg' => implode('. ', $errors),
              'debug' => $debug_response
            );
          } else {
            if (!empty($barcodes)) return array(
              'status' => true,
              'barcodes' => $barcodes,
              'debug' => $debug_response
            );
            $errors[] = __('No saved barcodes received', 'omnivalt');
            return array(
              'status' => false,
              'msg' => implode('. ', $errors),
              'debug' => $debug_response
            );
          }
        }

        private function debug_request($request) {
          if (isset($this->settings['debug_mode']) && $this->settings['debug_mode'] === 'yes') {
            if (!file_exists(OMNIVA_DIR . 'debug')) {
              mkdir(OMNIVA_DIR . 'debug');
            }
            $file = fopen(OMNIVA_DIR . 'debug/request.txt', 'w');
            fwrite($file, print_r($request,true));
            fclose($file);
            return $request;
          } else {
            return '';
          }
        }
        private function debug_response($response) {
          if (isset($this->settings['debug_mode']) && $this->settings['debug_mode'] === 'yes') {
            if (!file_exists(OMNIVA_DIR . 'debug')) {
              mkdir(OMNIVA_DIR . 'debug');
            }
            $file = fopen(OMNIVA_DIR . 'debug/response.txt', 'w');
            fwrite($file, print_r($response,true));
            fclose($file);
            return $response;
          } else {
            return '';
          }
        }

        public function getShipmentLabels($barcodes, $order_id = 0)
        {
          $errors = array();
          $barcodeXML = '';
          foreach ($barcodes as $barcode) {
            $barcodeXML .= '<barcode>' . $barcode . '</barcode>';
          }

          $xmlRequest = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://service.core.epmx.application.eestipost.ee/xsd">
           <soapenv:Header/>
           <soapenv:Body>
              <xsd:addrcardMsgRequest>
                 <partner>' . $this->clean($this->settings['api_user']) . '</partner>
                 <sendAddressCardTo>response</sendAddressCardTo>
                 <barcodes>
                    ' . $barcodeXML . '
                 </barcodes>
              </xsd:addrcardMsgRequest>
           </soapenv:Body>
        </soapenv:Envelope>';

          // echo $xmlRequest;
          $this->debug_request($xmlRequest);
          try {
            $url = $this->clean(preg_replace('{/$}', '', $this->settings['api_url'])) . '/epmx/services/messagesService.wsdl';
            $headers = array(
              "Content-type: text/xml;charset=\"utf-8\"",
              "Accept: text/xml",
              "Cache-Control: no-cache",
              "Pragma: no-cache",
              "Content-length: " . strlen($xmlRequest),
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_USERPWD, $this->clean($this->settings['api_user']) . ":" . $this->clean($this->settings['api_pass']));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $xmlResponse = curl_exec($ch);
            $this->debug_response($xmlResponse);
          } catch (Exception $e) {
            $errors[] = $e->getMessage() . ' ' . $e->getCode();
            $xmlResponse = '';
          }

          $xmlResponse = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $xmlResponse);
          $xml = simplexml_load_string($xmlResponse);
          if (!is_object($xml)) {
            $errors[] = __('Response is in the wrong format', 'omnivalt');
          }

          if (is_object($xml) && is_object($xml->Body->addrcardMsgResponse->successAddressCards->addressCardData->barcode)) {
            $shippingLabelContent = (string) $xml->Body->addrcardMsgResponse->successAddressCards->addressCardData->fileData;
            file_put_contents(plugin_dir_path(__FILE__) . "pdf/" . $order_id . '.pdf', base64_decode($shippingLabelContent));
          } else {
            $errors[] = 'No label received from webservice';
          }

          if (!empty($errors)) {
            return array(
              'status' => false,
              'msg' => implode('. ', $errors)
            );
          } else {
            if (!empty($barcodes)) return array(
              'status' => true
            );
            $errors[] = __('No saved barcodes received', 'omnivalt');
            return array(
              'status' => false,
              'msg' => implode('. ', $errors)
            );
          }
        }
        public function printLabels($orderIds = false, $download = true)
        {
          if (empty($orderIds) || !$orderIds) {
            return;
          }
          $omniva_settings = get_option('woocommerce_omnivalt_settings');
          $print_type = (isset($omniva_settings['print_type'])) ? $omniva_settings['print_type'] : '4';
          $count = 0;
          $label_count = 0;
          require_once(plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php');
          require_once(plugin_dir_path(__FILE__) . 'fpdi/src/autoload.php');
          $pdf = new \setasign\Fpdi\Tcpdf\Fpdi('P');
          $pdf->setPrintHeader(false);
          $pdf->setPrintFooter(false);
          if (!is_array($orderIds))
            $orderIds = array($orderIds);
          foreach (array_unique($orderIds) as $orderId) {
            $order = get_post((int) $orderId);
            $wc_order = wc_get_order((int) $orderId);
            $send_method = "";
            foreach ($wc_order->get_items('shipping') as $item_id => $shipping_item_obj) {
              $send_method        = $shipping_item_obj->get_method_id();
            }
            if ($send_method == 'omnivalt') {
              $send_method = get_post_meta($orderId, '_omnivalt_method', true);
            }
            if (!($send_method == 'omnivalt_pt' || $send_method == 'omnivalt_c')) {
              $this->add_msg($orderId . ' - ' . __('Shipping method is not Omniva', 'omnivalt'), 'error');
              continue;
            }
            $track_numer = get_post_meta($orderId, '_omnivalt_barcode', true);
            if ($track_numer == '' || !$download || !file_exists(plugin_dir_path(__FILE__) . 'pdf/' . $orderId . '.pdf')) {
              if (file_exists(plugin_dir_path(__FILE__) . 'pdf/' . $orderId . '.pdf')) {
                unlink(plugin_dir_path(__FILE__) . 'pdf/' . $orderId . '.pdf');
              }
              $status = $this->get_tracking_number($orderId);
              if (!empty($status['debug'])) {
                $this->add_msg('<b>OMNIVA RESPONSE DEBUG:</b><br/><pre style="white-space:pre-wrap;">' . htmlspecialchars($status['debug']) . '</pre>', 'notice');
              }
              if ($status['status']) {
                update_post_meta($orderId, '_omnivalt_barcode', $status['barcodes'][0]);
                $label_status = $this->getShipmentLabels($status['barcodes'], $orderId);
                if (!$label_status['status']) {
                  update_post_meta($orderId, '_omnivalt_error', $label_status['msg']);
                  $this->add_msg($orderId . ' - ' . $label_status['msg'], 'error');
                  continue;
                }
                if (!$download)
                  $this->add_msg($orderId . ' - ' . __('Omniva label generated', 'omnivalt'), 'updated');
                $send_email = (isset($omniva_settings['email_created_label'])) ? $omniva_settings['email_created_label'] : 'yes';
                if ($send_email === 'yes') {
                  $emails = new Omniva_Emails( plugin_dir_path(__FILE__) . '/templates/');
                  $email_subject = (isset($omniva_settings['email_created_label_subject'])) ? $omniva_settings['email_created_label_subject'] : '';
                  $email_params = array(
                    'tracking_code' => $status['barcodes'][0],
                    'tracking_link' => getTrackingLink($wc_order->get_shipping_country(), $status['barcodes'][0], true),
                    'subject' => $email_subject
                  );
                  $emails->send_label($wc_order, $wc_order->get_billing_email(), $email_params);
                }
              } else {
                update_post_meta($orderId, '_omnivalt_error', $status['msg']);
                $this->add_msg($orderId . ' - ' . $status['msg'], 'error');
                continue;
              }
            }

            $label_url = '';
            if (file_exists(plugin_dir_path(__FILE__) . 'pdf/' . $orderId . '.pdf')) {
              $label_url = plugin_dir_path(__FILE__) . 'pdf/' . $orderId . '.pdf';
            }
            if ($label_url == '') {
              continue;
            }
            update_post_meta($orderId, '_omnivalt_error', '');
            $pagecount = $pdf->setSourceFile($label_url);
            for ($i = 1; $i <= $pagecount; $i++) {
              $tplidx = $pdf->ImportPage($i);
              if ($print_type == '1') {
                $s = $pdf->getTemplatesize($tplidx);
                $pdf->AddPage('P', array($s['width'], $s['height']));
                $pdf->useTemplate($tplidx);
              } else if ($print_type == '4') {
                if ($label_count == 0 || $label_count == 4) {
                  $pdf->AddPage('P');
                  $label_count = 0;
                  $pdf->useTemplate($tplidx, 5, 15, 94.5, 108, false);
                } else if ($label_count == 1) {
                  $pdf->useTemplate($tplidx, 110, 15, 94.5, 108, false);
                } else if ($label_count == 2) {
                  $pdf->useTemplate($tplidx, 5, 160, 94.5, 108, false);
                } else if ($label_count == 3) {
                  $pdf->useTemplate($tplidx, 110, 160, 94.5, 108, false);
                }
                $label_count++;
              }
            }
            $count++;
          }
          if ($count == 0) {
            wp_safe_redirect(wp_get_referer());
            exit;
          }
          if ($download)
            $pdf->Output('Omnivalt_labels.pdf', 'D');
        }

        function printBulkManifests($orderIds = false)
        {
          require_once(plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php');
          if (!is_array($orderIds))
            $orderIds = array($orderIds);
          $object = '';
          $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

          $pdf->setPrintHeader(false);
          $pdf->setPrintFooter(false);
          $pdf->AddPage();
          $order_table = '';
          $count = 0;
          if (is_array($orderIds))
            foreach ($orderIds as $orderId) {
              $order = get_post((int) $orderId);
              $wc_order = wc_get_order((int) $orderId);
              $send_method = "";
              foreach ($wc_order->get_items('shipping') as $item_id => $shipping_item_obj) {
                $send_method        = $shipping_item_obj->get_method_id();
              }
              if ($send_method == 'omnivalt') {
                $send_method = get_post_meta($orderId, '_omnivalt_method', true);
              }
              if (!($send_method == 'omnivalt_pt' || $send_method == 'omnivalt_c')) {
                $this->add_msg($orderId . ' - ' . __('Shipping method is not Omniva', 'omnivalt'), 'error');
                continue;
              }
              $track_numer = get_post_meta($orderId, '_omnivalt_barcode', true);
              if ($track_numer == '') {
                $status = $this->get_tracking_number($orderId);
                if (!empty($status['debug'])) {
                  $this->add_msg('<b>OMNIVA RESPONSE DEBUG:</b><br/><pre style="white-space:pre-wrap;">' . htmlspecialchars($status['debug']) . '</pre>', 'notice');
                }
                if ($status['status']) {
                  update_post_meta($orderId, '_omnivalt_barcode', $status['barcodes'][0]);
                  $track_numer = $status['barcodes'][0];
                  if (file_exists(plugin_dir_path(__FILE__) . 'pdf/' . $orderId . '.pdf')) {
                    unlink(plugin_dir_path(__FILE__) . 'pdf/' . $orderId . '.pdf');
                  }
                  $label_status = $this->getShipmentLabels($status['barcodes'], $orderId);
                  if (!$label_status['status']) {
                    update_post_meta($orderId, '_omnivalt_error', $label_status['msg']);
                    $this->add_msg($orderId . ' - ' . $label_status['msg'], 'error');
                    continue;
                  }
                } else {
                  $this->add_msg($orderId . ' - ' . $status['msg'], 'error');
                  continue;
                }
              }
              //if (get_post_meta($orderId,'_manifest_generation_date',true)){
              //  $this->add_msg($orderId.' - '.__('Manifest already generated','omnivalt'),'error');
              //  continue;
              //}
              update_post_meta($orderId, '_manifest_generation_date', date('Y-m-d H:i:s'));
              $pt_address = '';
              if ($send_method == 'omnivalt_pt') {
                $pt_address = $this->get_terminal_address($terminal_id = get_post_meta($orderId, '_omnivalt_terminal_id', true));
              }
              $client_address = get_post_meta($orderId, '_shipping_address_index', true);
              if ($pt_address != '')
                $client_address = '';
              $count++;
              $cart_weight = get_post_meta($orderId, '_cart_weight', true);
              $weight_unit = get_option('woocommerce_weight_unit');
              if ($weight_unit != 'kg') {
                $cart_weight = wc_get_weight($cart_weight, 'kg', $weight_unit);
              }
              $order_table .= '<tr><td width = "40" align="right">' . $count . '.</td><td>' . $track_numer . '</td><td width = "60">' . date('Y-m-d') . '</td><td width = "40">1</td><td width = "60">' . $cart_weight . '</td><td width = "210">' . $client_address . $pt_address . '</td></tr>';

              //make order shipped after creating manifest
              /*
                $history = new OrderHistory();
                $history->id_order = (int)$orderId;
                $history->id_employee = (int)$cookie->id_employee;
                $history->changeIdOrderState((int)Configuration::get('PS_OS_SHIPPING'), $order);
                $history->addWithEmail(true);*/
            }
          $pdf->SetFont('freeserif', '', 14);
          $shop_addr = '<table cellspacing="0" cellpadding="1" border="0"><tr><td>' . date('Y-m-d H:i:s') . '</td><td>' . _x('Sender address', 'Manifest', 'omnivalt') . ':<br/>' . $this->settings['shop_name'] . '<br/>' . $this->settings['shop_address'] . ', ' . $this->settings['shop_postcode'] . '<br/>' . $this->settings['shop_city'] . ', ' . $this->settings['shop_countrycode'] . '<br/></td></tr></table>';

          $pdf->writeHTML($shop_addr, true, false, false, false, '');
          $tbl = '
            <table cellspacing="0" cellpadding="4" border="1">
              <thead>
                <tr>
                  <th width = "40" align="right">' . _x('No.', 'Manifest', 'omnivalt') . '</th>
                  <th>' . _x('Shipment number', 'Manifest', 'omnivalt') . '</th>
                  <th width = "60">' . _x('Date', 'Manifest', 'omnivalt') . '</th>
                  <th width = "40">' . _x('Quantity', 'Manifest', 'omnivalt') . '</th>
                  <th width = "60">' . _x('Weight (kg)', 'Manifest', 'omnivalt') . '</th>
                  <th width = "210">' . _x("Recipient's address", 'Manifest', 'omnivalt') . '</th>
                </tr>
              </thead>
              <tbody>
                ' . $order_table . '
              </tbody>
            </table><br/><br/>
            ';
          if ($count == 0) {
            $this->add_msg(__('No compatible orders for manifest', 'omnivalt'), 'error');
            wp_safe_redirect(wp_get_referer());
            exit;
          } else {
            // $this->call_omniva();
          }
          $pdf->SetFont('freeserif', '', 9);
          $pdf->writeHTML($tbl, true, false, false, false, '');
          $pdf->SetFont('freeserif', '', 14);
          $sign = _x("Courier name, surname, signature", 'Manifest', 'omnivalt') . ' ________________________________________________<br/><br/>';
          $sign .= _x("Sender name, surname, signature", 'Manifest', 'omnivalt') . ' ________________________________________________';
          $pdf->writeHTML($sign, true, false, false, false, '');
          $pdf->Output('Omnivalt_manifest.pdf', 'D');
        }

        function get_terminal_address($terminal_id)
        {
          $terminals_json_file_dir = dirname(__file__) . '/' . "locations.json";
          $terminals_file = fopen($terminals_json_file_dir, "r");
          $terminals = fread($terminals_file, filesize($terminals_json_file_dir) + 10);
          fclose($terminals_file);
          $terminals = json_decode($terminals, true);
          $parcel_terminals = '';
          if (is_array($terminals) && $terminal_id) {
            foreach ($terminals as $terminal) {
              if ($terminal['ZIP'] == $terminal_id) {
                return $terminal['NAME'] . ', ' . $terminal['A1_NAME'] . ', ' . $terminal['A0_NAME'];
              }
            }
          }
          return '';
        }

        function add_msg($msg, $type)
        {
          if (!session_id()) {
            session_start();
          }
          if (!isset($_SESSION['omnivalt_notices']))
            $_SESSION['omnivalt_notices'] = array();
          $_SESSION['omnivalt_notices'][] = array('msg' => $msg, 'type' => $type);
        }

        private function clean($string) {
          return str_replace('"',"'",$string);
        }
      }
    }
  }

  add_action('woocommerce_shipping_init', 'omnivalt_shipping_method');

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
    if (($method->id == "omnivalt_pt" && !isset($_POST['shipping_method'][0])) || ($method->id == "omnivalt_pt" && isset($_POST['shipping_method'][0]) && $_POST['shipping_method'][0] == "omnivalt_pt")) {
      echo omnivaltGetTerminalsOptions($termnal_id, $country);
    }
  }

  add_action('woocommerce_checkout_update_order_meta', 'add_terminal_id_to_order');
  function add_terminal_id_to_order($order_id)
  {
    if (isset($_POST['omnivalt_terminal']) && $order_id) {
      update_post_meta($order_id, '_omnivalt_terminal_id', $_POST['omnivalt_terminal']);
    }
    if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method'])) {
      foreach ($_POST['shipping_method'] as $ship_method) {
        if ($ship_method == "omnivalt_pt" || $ship_method == "omnivalt_c") {
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

  function omnivaltGetTerminalsOptions($selected = '', $country = "ALL")
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
        if (intval($terminal['TYPE']) == 1) {
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
    $script = "<script style='display:none;'>var omniva_current_country = '" . $country . "';
      var omnivaSettings = {
        auto_select:'" . $set_autoselect . "'
      };
      var omniva_current_terminal = '" . $selected . "';
      var omnivaTerminals = JSON.stringify(" . json_encode(getTerminalForMap('', $country)) . ");
      jQuery('document').ready(function($){        
        
        $('.omnivalt_terminal').omniva();
        $(document).trigger('omnivalt.checkpostcode');
              });</script>";
    $button = '';
    if (!isset($omniva_settings['show_map']) || isset($omniva_settings['show_map']) && $omniva_settings['show_map'] == "yes") {
      $button = '<button type="button" id="show-omniva-map" class="btn btn-basic btn-sm omniva-btn" style = "display: none;">' . __('Show in map', 'omnivalt') . '<img src = "' . plugin_dir_url(__FILE__) . '/sasi.png" title = "' . __("Show parcel terminals map", "omnivalt") . '"/></button>';
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
      if (file_exists(plugin_dir_path(__FILE__) . "pdf/" . $order_id . '.pdf')) {
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
    $omnivalt = new Omnivalt_Shipping_Method();
    $callCarrierReturn = $omnivalt->call_omniva();
    if ($callCarrierReturn['status'] == true)
      $omnivalt->add_msg(__("Omniva courier called", 'omnivalt'), 'omniva-notice');
    else
      $omnivalt->add_msg(__("There was an error calling Omniva courier. Error: " . $callCarrierReturn['msg'], 'omnivalt'), 'error');
    wp_safe_redirect(wp_get_referer());
  }
   
  /**
   * Display field value on the order edit page
   */
  add_action('woocommerce_admin_order_data_after_shipping_address', 'omniva_terminal_field_display_admin_order_meta', 10, 2);
  function omniva_terminal_field_display_admin_order_meta($order, $print_barcode = true, $admin_panel = true)
  {
    $send_method = getOmnivaMethod($order);
    if ($send_method != 'omnivalt_pt' && $send_method != 'omnivalt_c') {
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
      echo '<p><strong class="title">' . __('Courrier', 'omnivalt') . ':</strong> ' . $order->get_formatted_shipping_address() . '</p>';
    }

    if ($print_barcode) {
      echo printTrackingLink($order, $admin_panel, true);
    }
    echo '</div>';

    if (!$only_in_order) {
      return;
    }
    echo '<div class="edit_address">';
    if ($send_method == 'omnivalt_pt') {
      $all_terminals = omnivaltGetTerminalsList();
      $selected_terminal = get_post_meta($order->get_id(), '_omnivalt_terminal_id', true);
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
    }
    if ($send_method == 'omnivalt_c') {
      echo __('The delivery address for the courier is changed in the fields above ', 'omnivalt');
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
    if (isset($_POST['omnivalt_terminal_id'])) {
      update_post_meta($post_id, '_omnivalt_terminal_id', wc_clean($_POST['omnivalt_terminal_id']));
    }
  }

  add_action('woocommerce_checkout_process', 'omnivalt_terminal_validate');
  function omnivalt_terminal_validate()
  {
    if (in_array('omnivalt_pt', $_POST['shipping_method'])) {
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
  function getTerminalForMap($selected = '', $country = "LT")
  {
    $terminals_json_file_dir = dirname(__file__) . "/locations.json";
    $terminals_file = fopen($terminals_json_file_dir, "r");
    $terminals = fread($terminals_file, filesize($terminals_json_file_dir) + 10);
    fclose($terminals_file);
    $terminals = json_decode($terminals, true);
    $parcel_terminals = '';
    $terminalsList = array();
    switch (strtoupper($country)) {
      case 'LV':
        $comment_lang = 'lav';
        break;
      case 'EE':
        $comment_lang = 'est';
        break;
      
      default:
        $comment_lang = 'lit';
        break;
    }
    if (is_array($terminals)) {
      foreach ($terminals as $terminal) {
        if ($terminal['A0_NAME'] != $country && in_array($country, array("LT", "EE", "LV")) || intval($terminal['TYPE']) == 1)
          continue;

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
            <h5 style="display: inline">' . __('Omniva parcel terminals', 'omnivalt') . '</h5>
            </div>
            <div class="omniva-modal-body" style="/*overflow: hidden;*/">
                <div id = "omnivaMapContainer"></div>
                <div class="omniva-search-bar" >
                    <h4 style="margin-top: 0px;">' . __('Parcel terminals addresses', 'omnivalt') . '</h4>
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
