<?php
/**
 * Plugin Name: Omniva shipping
 * Description: Omniva shipping plugin for WooCommerce
 * Author: Omniva
 * Version: 1.4.13
 * Domain Path: /languages
 * Text Domain: omnivalt
 * WC requires at least: 3.0.0
 * WC tested up to: 3.7.0
 */

if (!defined('WPINC')) {
  die;
}

add_action( 'init', 'omnivalt_load_textdomain' );

function omnivalt_load_textdomain() {
  load_plugin_textdomain( 'omnivalt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
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

      wp_enqueue_script('omniva', plugins_url('/js/omniva.js?20190625', __FILE__), array(
        'jquery'
      ));

      wp_enqueue_style('omniva', plugins_url('/css/omniva.css?20190530', __FILE__));
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

  add_action('admin_head', 'omnivalt_admin_scripts');
  function omnivalt_admin_scripts()
  {
    wp_enqueue_style('omnivalt_admin', plugins_url('/css/admin_omnivalt.css', __FILE__));
  }

  add_action('wp_ajax_nopriv_add_terminal_to_session', 'add_terminal_to_session');
  add_action('wp_ajax_add_terminal_to_session', 'add_terminal_to_session');
  function add_terminal_to_session()
  {
    if (isset($_POST['terminal_id']) && is_numeric($_POST['terminal_id'])) WC()->session->set('omnivalt_terminal_id', $_POST['terminal_id']);
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
         * Define settings field for this shipping
         * @return void
         */
        function init_form_fields()
        {
          $this->form_fields = array(
            'enabled' => array(
              'title' => __('Enable', 'omnivalt'),
              'type' => 'checkbox',
              'description' => __('Enable this shipping.', 'omnivalt'),
              'default' => 'yes'
            ),
            'api_url' => array(
              'title' => __('Api URL', 'omnivalt'),
              'type' => 'text',
              'default' => 'https://edixml.post.ee'

            ),
            'api_user' => array(
              'title' => __('Api user', 'omnivalt'),
              'type' => 'text',
            ),
            'api_pass' => array(
              'title' => __('Api user password', 'omnivalt'),
              'type' => 'password',
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
            ),
            'shop_countrycode' => array(
              'title' => __('Shop country code', 'omnivalt'),
              'type' => 'text',
            ),
            'shop_phone' => array(
              'title' => __('Shop phone number', 'omnivalt'),
              'type' => 'text',
            ),
            'pick_up_start' => array(
              'title' => __('Pick up time start', 'omnivalt'),
              'type' => 'text',
            ),
            'pick_up_end' => array(
              'title' => __('Pick up time end', 'omnivalt'),
              'type' => 'text',
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
            'method_pt' => array(
              'title' => __('Parcel terminal', 'omnivalt'),
              'type' => 'checkbox',
              'description' => __('Show parcel terminal method in checkout.', 'omnivalt')
            ),
            'method_c' => array(
              'title' => __('Courrier', 'omnivalt'),
              'type' => 'checkbox',
              'description' => __('Show courrier method in checkout.', 'omnivalt')
            ),
            'c_price' => array(
              'title' => 'LT ' . __('Courrier price', 'omnivalt'),
              'type' => 'number',
              'default' => 2,
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
            ),
            'pt_price' => array(
              'title' => 'LT ' . __('Parcel terminal price', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'default' => 2,
            ),
            'pt_priceFREE' => array(
              'title' => 'LT ' . __('Free shipping then price is higher (Terminals)', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'default' => 100
            ),
            'pt_price_C_FREE' => array(
              'title' => 'LT ' . __('Free shipping then price is higher (Courier)', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'default' => 100
            ),
            'c_priceLV' => array(
              'title' => 'LV ' . __('Courrier price', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'default' => 2
            ),
            'pt_priceLV' => array(
              'title' => 'LV ' . __('Parcel terminal price', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'default' => 2
            ),
            'pt_priceLV_FREE' => array(
              'title' => 'LV ' . __('Free shipping then price is higher (Terminals)', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'default' => 100
            ),
            'pt_price_C_LV_FREE' => array(
              'title' => 'LV ' . __('Free shipping then price is higher (Courier)', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'default' => 100
            ),
            'c_priceEE' => array(
              'title' => 'EE ' . __('Courrier price', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'default' => 2
            ),
            'pt_priceEE' => array(
              'title' => 'EE ' . __('Parcel terminal price', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'default' => 2
            ),
            'pt_priceEE_FREE' => array(
              'title' => 'EE ' . __('Free shipping then price is higher (Terminals)', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'default' => 100
            ),
            'pt_price_C_EE_FREE' => array(
              'title' => 'EE ' . __('Free shipping then price is higher (Courier)', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'default' => 100
            ),
            'weight' => array(
              'title' => __('Weight (kg)', 'omnivalt'),
              'type' => 'number',
              'custom_attributes' => array(
                'step'          => 0.01,
              ),
              'description' => __('Maximum allowed weight', 'omnivalt'),
              'default' => 100
            ),
            'show_map' => array(
              'title' => __('Map', 'omnivalt'),
              'type' => 'checkbox',
              'description' => __('Show map of terminals.', 'omnivalt'),
              'default' => 'yes'
            ),
          );
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

          foreach ($package['contents'] as $item_id => $values) {
            $_product = $values['data'];
            if ($_product->get_weight()) {
              $weight = $weight + $_product->get_weight() * $values['quantity'];
            }
          }

          $weight = wc_get_weight($weight, 'kg');

          if ($this->settings['method_pt'] == 'yes') {
            switch ($country) {
              case 'LV':
                $amount = $this->settings['pt_priceLV'];
                if ($cart_amount > floatval($this->settings['pt_priceLV_FREE']))
                  $amount = 0.0;
                break;
              case 'EE':
                $amount = $this->settings['pt_priceEE'];
                if ($cart_amount > floatval($this->settings['pt_priceEE_FREE']))
                  $amount = 0.0;
                break;
              default:
                $amount = $this->settings['pt_price'];
                if ($cart_amount > floatval($this->settings['pt_priceFREE']))
                  $amount = 0.0;
                break;
            }

            $rate = array(
              'id' => 'omnivalt_pt',
              'label' => __('Omniva parcel terminal', 'omnivalt'),
              'cost' => $amount
            );
            $this->add_rate($rate);
          }

          if ($this->settings['method_c'] == 'yes') {
            switch ($country) {
              case 'LV':
                $amountC = $this->settings['c_priceLV'];
                if ($cart_amount > floatval($this->settings['pt_price_C_LV_FREE']))
                  $amountC = 0.0;
                break;
              case 'EE':
                $amountC = $this->settings['c_priceEE'];
                if ($cart_amount > floatval($this->settings['pt_price_C_EE_FREE']))
                  $amountC = 0.0;
                break;
              default:
                $amountC = $this->settings['c_price'];
                if ($cart_amount > floatval($this->settings['pt_price_C_FREE']))
                  $amountC = 0.0;
                break;
            }
            $rate = array(
              'id' => 'omnivalt_c',
              'label' => __('Omniva courrier', 'omnivalt'),
              'cost' => $amountC
            );
            $this->add_rate($rate);
          }
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
          $is_cod = false;
          if (get_post_meta($id_order, '_payment_method', true) == "cod")
            $is_cod = true;
          $parcel_terminal = "";
          if ($send_method == "pt") $parcel_terminal = 'offloadPostcode="' . $terminal_id . '" ';
          $additionalService = '';
          if ($service == "PA" || $service == "PU") $additionalService .= '<option code="ST" />';
          if ($is_cod) $additionalService .= '<option code="BP" />';
          if ($additionalService) {
            $additionalService = '<add_service>' . $additionalService . '</add_service>';
          }
          /* LV/EE fixes
          if ($parcel_terminal)
            $client_address = '<address ' . $parcel_terminal . ' />';
          else
	*/
          $client_address = '<address postcode="' . get_post_meta($id_order, '_shipping_postcode', true) . '" ' . $parcel_terminal . ' deliverypoint="' . get_post_meta($id_order, '_shipping_city', true) . '" country="' . get_post_meta($id_order, '_shipping_country', true) . '" street="' . get_post_meta($id_order, '_shipping_address_1', true) . '" />';
          $phones = '';
          if ($mobile = get_post_meta($id_order, '_billing_phone', true)) $phones .= '<mobile>' . $mobile . '</mobile>';
          $pickStart = $this->settings['pick_up_start'] ? $this->settings['pick_up_start'] : '8:00';
          $pickFinish = $this->settings['pick_up_end'] ? $this->settings['pick_up_end'] : '17:00';
          $pickDay = date('Y-m-d');
          if (time() > strtotime($pickDay . ' ' . $pickFinish)) $pickDay = date('Y-m-d', strtotime($pickDay . "+1 days"));
          $shop_country_iso = $this->settings['shop_countrycode'];
          $xmlRequest = '
          <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://service.core.epmx.application.eestipost.ee/xsd">
             <soapenv:Header/>
             <soapenv:Body>
                <xsd:businessToClientMsgRequest>
                   <partner>' . $this->settings['api_user'] . '</partner>
                   <interchange msg_type="info11">
                      <header file_id="' . Date('YmdHms') . '" sender_cd="' . $this->settings['api_user'] . '" >                
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
                               <person_name>' . get_post_meta($id_order, '_shipping_first_name', true) . ' ' . get_post_meta($id_order, '_shipping_last_name', true) . '</person_name>
                              ' . $phones . '
                              ' . $client_address . '
                            </receiverAddressee>
                            <!--Optional:-->
                            <returnAddressee>
                              <person_name>' . $this->settings['shop_name'] . '</person_name>
                              <!--Optional:-->
                              <phone>' . $this->settings['shop_phone'] . '</phone>
                              <address postcode="' . $this->settings['shop_postcode'] . '" deliverypoint="' . $this->settings['shop_city'] . '" country="' . $shop_country_iso . '" street="' . $this->settings['shop_address'] . '" />
                            
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
          $pickStart = $this->settings['pick_up_start'] ? $this->settings['pick_up_start'] : '8:00';
          $pickFinish = $this->settings['pick_up_end'] ? $this->settings['pick_up_end'] : '17:00';
          $pickDay = date('Y-m-d');
          if (time() > strtotime($pickDay . ' ' . $pickFinish)) $pickDay = date('Y-m-d', strtotime($pickDay . "+1 days"));
          $shop_country_iso = $this->settings['shop_countrycode'];
          $xmlRequest = '
          <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://service.core.epmx.application.eestipost.ee/xsd">
             <soapenv:Header/>
             <soapenv:Body>
                <xsd:businessToClientMsgRequest>
                   <partner>' . $this->settings['api_user'] . '</partner>
                   <interchange msg_type="info11">
                      <header file_id="' . Date('YmdHms') . '" sender_cd="' . $this->settings['api_user'] . '" >                
                      </header>
                      <item_list>
                        ';
          // for ($i = 0; $i < $orderInfo['packs']; $i++):
          $xmlRequest .= '
                         <item service="' . $service . '" >
                            <measures weight="1" />
                            <receiverAddressee >
                               <person_name>' . $this->settings['shop_name'] . '</person_name>
                              <!--Optional:-->
                              <phone>' . $this->settings['shop_phone'] . '</phone>
                              <address postcode="' . $this->settings['shop_postcode'] . '" deliverypoint="' . $this->settings['shop_city'] . '" country="' . $shop_country_iso . '" street="' . $this->settings['shop_address'] . '" />
                            </receiverAddressee>
                            <!--Optional:-->
                            <returnAddressee>
                              <person_name>' . $this->settings['shop_name'] . '</person_name>
                              <!--Optional:-->
                              <phone>' . $this->settings['shop_phone'] . '</phone>
                              <address postcode="' . $this->settings['shop_postcode'] . '" deliverypoint="' . $this->settings['shop_city'] . '" country="' . $shop_country_iso . '" street="' . $this->settings['shop_address'] . '" />
                            </returnAddressee>
                            <onloadAddressee>
                              <person_name>' . $this->settings['shop_name'] . '</person_name>
                              <!--Optional:-->
                              <phone>' . $this->settings['shop_phone'] . '</phone>
                              <address postcode="' . $this->settings['shop_postcode'] . '" deliverypoint="' . $this->settings['shop_city'] . '" country="' . $shop_country_iso . '" street="' . $this->settings['shop_address'] . '" />
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

        private function api_request($request)
        {
          $barcodes = array();;
          $errors = array();
          $url = $this->settings['api_url'] . '/epmx/services/messagesService.wsdl';
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
          curl_setopt($ch, CURLOPT_USERPWD, $this->settings['api_user'] . ":" . $this->settings['api_pass']);
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
          curl_setopt($ch, CURLOPT_TIMEOUT, 30);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          $xmlResponse = curl_exec($ch);

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
              'msg' => implode('. ', $errors)
            );
          } else {
            if (!empty($barcodes)) return array(
              'status' => true,
              'barcodes' => $barcodes
            );
            $errors[] = __('No saved barcodes received', 'omnivalt');
            return array(
              'status' => false,
              'msg' => implode('. ', $errors)
            );
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
                 <partner>' . $this->settings['api_user'] . '</partner>
                 <sendAddressCardTo>response</sendAddressCardTo>
                 <barcodes>
                    ' . $barcodeXML . '
                 </barcodes>
              </xsd:addrcardMsgRequest>
           </soapenv:Body>
        </soapenv:Envelope>';

          // echo $xmlRequest;

          try {
            $url = $this->settings['api_url'] . '/epmx/services/messagesService.wsdl';
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
            curl_setopt($ch, CURLOPT_USERPWD, $this->settings['api_user'] . ":" . $this->settings['api_pass']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $xmlResponse = curl_exec($ch);
            $debugData['result'] = $xmlResponse;
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
          $count = 0;
          $label_count = 0;
          require_once(plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php');
          require_once(plugin_dir_path(__FILE__) . 'fpdi/fpdi.php');
          $pdf = new FPDI();
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
            $track_numer = get_post_meta($order->ID, '_omnivalt_barcode', true);
            if ($track_numer == '' || !$download || !file_exists(plugin_dir_path(__FILE__) . 'pdf/' . $order->ID . '.pdf')) {
              if (file_exists(plugin_dir_path(__FILE__) . 'pdf/' . $order->ID . '.pdf')) {
                unlink(plugin_dir_path(__FILE__) . 'pdf/' . $order->ID . '.pdf');
              }
              $status = $this->get_tracking_number($orderId);
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
              } else {
                update_post_meta($orderId, '_omnivalt_error', $status['msg']);
                $this->add_msg($orderId . ' - ' . $status['msg'], 'error');
                continue;
              }
            }
            $label_url = '';
            if (file_exists(plugin_dir_path(__FILE__) . 'pdf/' . $order->ID . '.pdf')) {
              $label_url = plugin_dir_path(__FILE__) . 'pdf/' . $order->ID . '.pdf';
            }
            if ($label_url == '') {
              continue;
            }
            update_post_meta($orderId, '_omnivalt_error', '');
            $pagecount = $pdf->setSourceFile($label_url);
            for ($i = 1; $i <= $pagecount; $i++) {
              $tplidx = $pdf->ImportPage($i);
              if ($label_count == 0 || $label_count == 4) {
                $pdf->AddPage('P');
                $label_count = 0;
                $pdf->useTemplate($tplidx, 5, 15, 94.5, 108, true);
              } else if ($label_count == 1) {
                $pdf->useTemplate($tplidx, 110, 15, 94.5, 108, true);
              } else if ($label_count == 2) {
                $pdf->useTemplate($tplidx, 5, 160, 94.5, 108, true);
              } else if ($label_count == 3) {
                $pdf->useTemplate($tplidx, 110, 160, 94.5, 108, true);
              }
              $label_count++;
              //$tplidx = $pdf->ImportPage($i);
              //$s = $pdf->getTemplatesize($tplidx);
              //$pdf->AddPage('P', array($s['w'], $s['h']));
              //$pdf->useTemplate($tplidx);
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
              $track_numer = get_post_meta($order->ID, '_omnivalt_barcode', true);
              if ($track_numer == '') {
                $status = $this->get_tracking_number($orderId);
                if ($status['status']) {
                  update_post_meta($order->ID, '_omnivalt_barcode', $status['barcodes'][0]);
                  $track_numer = $status['barcodes'][0];
                  if (file_exists(plugin_dir_path(__FILE__) . 'pdf/' . $order->ID . '.pdf')) {
                    unlink(plugin_dir_path(__FILE__) . 'pdf/' . $order->ID . '.pdf');
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
          $shop_addr = '<table cellspacing="0" cellpadding="1" border="0"><tr><td>' . date('Y-m-d H:i:s') . '</td><td>Siuntėjo adresas:<br/>' . $this->settings['shop_name'] . '<br/>' . $this->settings['shop_address'] . ', ' . $this->settings['shop_postcode'] . '<br/>' . $this->settings['shop_city'] . ', ' . $this->settings['shop_countrycode'] . '<br/></td></tr></table>';

          $pdf->writeHTML($shop_addr, true, false, false, false, '');
          $tbl = '
            <table cellspacing="0" cellpadding="4" border="1">
              <thead>
                <tr>
                  <th width = "40" align="right">Nr.</th>
                  <th>Siuntos numeris</th>
                  <th width = "60">Data</th>
                  <th width = "40" >Kiekis</th>
                  <th width = "60">Svoris (kg)</th>
                  <th width = "210">Gavėjo adresas</th>
                </tr>
              </thead>
              <tbody>
                ' . $order_table . '
              </tbody>
            </table><br/><br/>
            ';
          if ($count == 0) {
            $this->add_msg("No compatible orders for manifest", 'error');
            wp_safe_redirect(wp_get_referer());
            exit;
          } else {
            // $this->call_omniva();
          }
          $pdf->SetFont('freeserif', '', 9);
          $pdf->writeHTML($tbl, true, false, false, false, '');
          $pdf->SetFont('freeserif', '', 14);
          $sign = 'Kurjerio vardas, pavardė, parašas ________________________________________________<br/><br/>';
          $sign .= 'Siuntėjo vardas, pavardė, parašas ________________________________________________';
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
                return $terminal['NAME'] . ', ' . $terminal['A2_NAME'] . ', ' . $terminal['A0_NAME'];
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
    if (isset($_POST['shipping_method'][0]) && ($_POST['shipping_method'][0] == "omnivalt_pt" || $_POST['shipping_method'][0] == "omnivalt_c")) {
      update_post_meta($order_id, '_omnivalt_method', $_POST['shipping_method'][0]);
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
    $parcel_terminals = '<option value = "">' . __('Select parcel terminal', 'omnivalt') . '</option>' . $parcel_terminals;
    $script = "<script>var omniva_current_country = '" . $country . "';
      var omnivaTerminals = '" . json_encode(getTerminalForMap('', $country)) . "';
      jQuery('document').ready(function($){        
        
        $('.omnivalt_terminal').omniva();
      
              });</script>";
    $button = '';
    $omniva_settings = get_option('woocommerce_omnivalt_settings');
    if (!isset($omniva_settings['show_map']) || isset($omniva_settings['show_map']) && $omniva_settings['show_map'] == "yes") {
      $button = '<button type="button" id="show-omniva-map" class="btn btn-basic btn-sm omniva-btn" style = "display: none;">' . __('Show in map', 'omnivalt') . '<img src = "' . plugin_dir_url(__FILE__) . '/sasi.png" title = "' . __("Show parcel terminals map", "omnivalt") . '"/></button>';
    }
    return '<div class = "terminal-container"><select class = "omnivalt_terminal" name = "omnivalt_terminal">' . $parcel_terminals . '</select>
      ' . $button . ' </div>' . $script;
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
          return (string) $terminal['NAME'] . ', ' . $terminal['A2_NAME'];
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
  }

  // Add custom order meta data to make it accessible in Order preview template
  add_filter('woocommerce_admin_order_preview_get_order_details', 'admin_order_preview_add_custom_meta_data', 10, 2);
  function admin_order_preview_add_custom_meta_data($data, $order)
  {
    $send_method = getOmnivaMethod($order);
    if ($send_method == 'omnivalt_pt') {
      $data['shipping_via'] = __('Omniva parcel terminal', 'omnivalt') . ": " . getOmnivaTerminalAddress($order);
    }
    //$data['shipping_via'] .=  '<br>' . getOmnivaTerminalAddress($order);
    return $data;
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
    $send_method = get_post_meta($order->ID, '_shipping_method', true);
    if ((isset($send_method[0]) && ($send_method[0] == 'omnivalt_pt' || $send_method[0] == 'omnivalt_c' || $send_method[0] == 'omnivalt'))) {
      echo '<a class="button tips omnivalt_generate_label" href="' . wp_nonce_url(admin_url('admin-ajax.php?action=generate_omnivalt_label&order_id=' . $order->ID), 'woocommerce-mark-order-status') . '" data-tip="' . __('Generate Omniva label', 'omnivalt') . '"> </a>';
      if (file_exists(plugin_dir_path(__FILE__) . "pdf/" . $order->ID . '.pdf')) {
        echo '<a class="button tips omnivalt_view_label" href="' . plugins_url('pdf/' . $order->ID . '.pdf', __FILE__) . '" target = "_blank" data-tip="' . __('VIew Omniva label', 'omnivalt') . '"> </a>';
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
      'manage_options',
      'omniva-manifest',
      'manifest_page',
      plugins_url('omniva-woocommerce/images/icon.png'),
      1
    );
  }

  function manifest_page()
  {
    include_once("manifest_page.php");
  }

  // Custom action to be used in manifest.php
  add_action('get_omniva_info_for_courier', 'custom_function');
  function custom_function()
  {
    $wc_shipping = new WC_Shipping();
    $omnivalt = new Omnivalt_Shipping_Method();
    $sender = $omnivalt->settings['shop_name'];
    $phone = $omnivalt->settings['shop_phone'];
    $postcode = $omnivalt->settings['shop_postcode'];
    $address = $omnivalt->settings['shop_address'] . ', ' . $omnivalt->settings['shop_city'];
    echo "<div><span>" . __("Shop name", 'omnivalt') . ":</span> $sender</div>" .
      "<div><span>" . __("Shop phone number", 'omnivalt') . ":</span> $phone</div>" .
      "<div><span>" . __("Shop postcode", 'omnivalt') . ":</span> $postcode</div>" .
      "<div><span>" . __("Shop address", 'omnivalt') . ":</span> $address</div>";
  }

  add_filter('admin_post_omnivalt_call_courier', 'omnivalt_post_call_courier_actions');
  function omnivalt_post_call_courier_actions()
  {
    $wc_shipping = new WC_Shipping();
    $omnivalt = new Omnivalt_Shipping_Method();
    $callCarrierReturn = $omnivalt->call_omniva();
    if ($callCarrierReturn['status'] == true)
      $omnivalt->add_msg(__("Omniva courier called", 'omnivalt'), 'notice');
    else
      $omnivalt->add_msg(__("There was an error calling Omniva courier. Error: " . $callCarrierReturn['msg'], 'omnivalt'), 'error');
    wp_safe_redirect(wp_get_referer());
  }
   
  /**
   * Display field value on the order edit page
   */
  add_action('woocommerce_admin_order_data_after_shipping_address', 'omniva_terminal_field_display_admin_order_meta', 10, 1);
  function omniva_terminal_field_display_admin_order_meta($order)
  {
    $send_method = getOmnivaMethod($order);
    if ($send_method == 'omnivalt_pt') {
      echo '<p><strong>' . __('Omniva parcel terminal', 'omnivalt') . ':</strong> <br/>' . getOmnivaTerminalAddress($order) . '</p>';
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
    if (is_array($terminals)) {
      foreach ($terminals as $terminal) {
        if ($terminal['A0_NAME'] != $country && in_array($country, array("LT", "EE", "LV")) || intval($terminal['TYPE']) == 1)
          continue;

        if (!isset($grouped_options[$terminal['A1_NAME']]))
          $grouped_options[(string) $terminal['A1_NAME']] = array();
        $grouped_options[(string) $terminal['A1_NAME']][(string) $terminal['ZIP']] = $terminal['NAME'];

        $terminalsList[] = [$terminal['NAME'], $terminal['Y_COORDINATE'], $terminal['X_COORDINATE'], $terminal['ZIP'], $terminal['A1_NAME'], $terminal['A2_NAME'], str_ireplace('"', '\"', $terminal['comment_lit'])];
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
