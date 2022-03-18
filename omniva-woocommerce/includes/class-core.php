<?php
class OmnivaLt_Core
{
  public static $main_file_path = WP_PLUGIN_DIR . '/' . OMNIVALT_BASENAME;

  public static function init()
  {
    self::load_classes();
    self::load_init_hooks();
    OmnivaLt_Cronjob::init();

    if ( in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {
      self::load_launch_hooks();
    }
  }

  public static function get_configs($section_name = false)
  {
    $default_configs = self::default_configs();
    $configs = $default_configs;

    if ( function_exists('omnivalt_configs') ) {
      foreach ( omnivalt_configs() as $key => $values ) {
        if ( is_array($values) ) {
          if ( ! isset($configs[$key]) ) {
            $configs[$key] = array();
          }
          $configs[$key] = array_merge($configs[$key], $values);
        } else {
          $configs[$key] = $values;
        }
      }
    }

    if ( $section_name && isset($configs[$section_name]) ) {
      $configs = $configs[$section_name];
    }

    return $configs;
  }

  public static function get_settings()
  {
    return get_option('woocommerce_omnivalt_settings');
  }

  public static function get_shipping_method_info($args = '') //TODO: Not completed and still not using. Make a mapping (simple function to return required info)
  {
    $args['receiver_country'] = (isset($args['receiver_country'])) ? $args['receiver_country'] : '';
    $args['get_all'] = (isset($args['get_all'])) ? $args['get_all'] : false;

    $configs = self::get_configs();
    $settings = self::get_settings();
    if ( ! $settings ) {
      return false;
    }

    $api_country = $settings['api_country'];
    if ( ! isset($configs['shipping_params'][$api_country]) ) {
      return false;
    }

    $shipping_params = $configs['shipping_params'][$api_country];

    $output = array(
      'title' => $shipping_params['title'],
      'comment_lang' => $shipping_params['comment_lang'],
      'tracking_url' => $shipping_params['tracking_url'],
      'set_name' => false,
      'shipping_services' => array(),
    );

    if ( ! empty($args['receiver_country']) && isset($shipping_params['shipping_sets'][$args['receiver_country']]) ) {
      $output['set_name'] = $shipping_params['shipping_sets'][$args['receiver_country']];

      if ( isset($configs['shipping_sets'][$output['set_name']]) ) {
        $output['shipping_services'] = $configs['shipping_sets'][$output['set_name']];
      }

      /*$asociations = OmnivaLt_Helper::get_methods_asociations(); //TODO: Continue with this

      if ( ! $args['get_all'] ) {
        foreach ( $output['shipping_services'] as $serv_combination => $serv_code ) {
        }
        $allowed_methods = OmnivaLt_Helper::get_allowed_methods($output['set_name']);
      } */
    }
  }

  public static function textdomain() {
    load_plugin_textdomain('omnivalt', false, dirname(OMNIVALT_BASENAME) . '/languages' ); 
  }

  public static function add_required_directories()
  {
    $directories = array('logs', 'pdf', 'debug');

    foreach ( $directories as $dir ) {
      if ( ! file_exists(OMNIVALT_DIR . $dir) ) {
        mkdir(OMNIVALT_DIR . $dir, 0755, true);
      }
    }
  }

  public static function get_overrides_dir($get_url = false)
  {
    $directory = '/omniva/';
    if ( $get_url ) {
      return get_template_directory_uri() . $directory;
    }

    return get_template_directory() . $directory;
  }

  public static function check_update($current_version = '') {
    $update_params = self::get_configs('update');
    
    if (empty($update_params['check_url'])) {
      return false;
    }

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
        $plugin_data = get_file_data(self::$main_file_path, array('Version' => 'Version'), false);
        $current_version = $plugin_data['Version'];
      }
      return (version_compare($current_version, $update_info['version'], '<')) ? $update_info : false;
    }
  
    return false;
  }

  public static function update_message($file, $plugin) {
    $check_update = self::check_update($plugin['Version']);
    $update_params = self::get_configs('update');

    if ($check_update) {
      echo '<tr class="plugin-update-tr installer-plugin-update-tr js-otgs-plugin-tr active">';
      echo '<td class="plugin-update" colspan="100%">';
      echo '<div class="update-message notice inline notice-warning notice-alt">';
      echo '<p>' . sprintf(__('A newer version of the plugin (%s) has been released.', 'omnivalt'), '<a href="' . $check_update['url'] . '" target="_blank">v' . $check_update['version'] . '</a>');
      if (!empty($update_params['download_url'])) {
        echo ' ' . sprintf(__('You can download it by pressing %s.', 'omnivalt'), '<a href="' . $update_params['download_url'] . '">' . __('here', 'omnivalt') . '</a>');
      }
      if (defined('OMNIVALT_CUSTOM_VERSION') && OMNIVALT_CUSTOM_VERSION === true) {
        echo '<br/><strong style="color:red;">' . __('We do not recommend update the plugin, because your plugin have  changes that is not included in the update.', 'omnivalt') . '</strong>';
      }
      echo '</p>';
      echo '</div>';
      echo '</td></tr>';
    }
  }

  public static function load_front_scripts()
  {
    if (is_cart() || is_checkout()) {
      wp_enqueue_script('omniva-helper', plugins_url('/js/omniva_helper.js', self::$main_file_path), array('jquery'), OMNIVALT_VERSION);
      wp_enqueue_script('omniva', plugins_url('/js/omniva.js', self::$main_file_path), array('jquery'), OMNIVALT_VERSION);
      wp_enqueue_style('omniva', plugins_url('/css/omniva.css', self::$main_file_path), array(), OMNIVALT_VERSION);
      
      if ( file_exists(OMNIVALT_DIR . '/css/custom.css') ) { //Allow custom CSS file which not include in plugin by default
        wp_enqueue_style('omniva-custom', plugins_url('/css/custom.css', self::$main_file_path), array(), OMNIVALT_VERSION);
      }
      if ( file_exists(self::get_overrides_dir() . 'css/front.css') ) { //Allow custom CSS file from theme 
        wp_enqueue_style('omniva-theme-front', self::get_overrides_dir(true) . 'css/front.css', array(), OMNIVALT_VERSION);
      }

      wp_enqueue_script('leaflet', plugins_url('/js/leaflet.js', self::$main_file_path), array('jquery'), null, true);
      wp_enqueue_style('leaflet', plugins_url('/css/leaflet.css', self::$main_file_path));    

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

  public static function load_admin_settings_scripts($hook)
  {
    if ($hook == 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] == 'omnivalt') {
      wp_enqueue_style('omnivalt_admin_settings', plugins_url('/css/omniva_admin_settings.css', self::$main_file_path), array(), OMNIVALT_VERSION);
      wp_enqueue_script('omniva_admin_settings', plugins_url( '/js/omniva_admin_settings.js', self::$main_file_path), array('jquery'), OMNIVALT_VERSION );
    }
  }

  public static function add_asyncdefer_by_handle($tag, $handle)
  {
    if (strpos($handle, 'async') !== false) {
      $tag = str_replace('<script ', '<script async ', $tag);
    }
    if (strpos($handle, 'defer') !== false) {
      $tag =  str_replace('<script ', '<script defer ', $tag);
    }

    return $tag;
  }

  public static function init_shipping_method()
  {
    require_once OMNIVALT_DIR . 'includes/class-shipping-method-helper.php';
    include OMNIVALT_DIR . 'includes/class-shipping-method.php';
    self::load_conditional_hooks();
    OmnivaLt_Terminals::check_terminals_json_file();
  }

  public static function add_shipping_method($methods)
  {
    $methods['omnivalt'] = 'Omnivalt_Shipping_Method';
    return $methods;
  }

  public static function settings_link($links) {
    array_unshift($links, '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=omnivalt' ) . '">' . __('Settings', 'omnivalt') . '</a>');
    return $links;
  }

  public static function admin_notices(){
    if ( ! session_id() ) {
      session_start();
    }
    if ( array_key_exists( 'omnivalt_notices', $_SESSION ) ) {
      foreach ($_SESSION['omnivalt_notices'] as $notice) {
        echo '<div class="' . $notice['type'] . '"><p>' . $notice['msg'] . '</p></div>';
      }
      unset( $_SESSION['omnivalt_notices'] );
    }
  }

  public static function add_to_footer()
  {
    if (is_cart() || is_checkout()) {
      echo OmnivaLt_Terminals::terminals_modal();
    }
  }

  public static function load_vendors($vendors = array())
  {
    if (empty($vendors) || in_array('tcpdf', $vendors)) {
      require_once(OMNIVALT_DIR . 'vendor/tcpdf/tcpdf.php');
    }
    if (empty($vendors) || in_array('fpdi', $vendors)) {
      require_once(OMNIVALT_DIR . 'vendor/fpdi/src/autoload.php');
    }
  }

  public static function get_error_text($error_code)
  {
    $errors = array(
      '001' => _x('The service is not available to the sender country', 'error', 'omnivalt'),
      '002' => _x('The service is not available to the receiver country', 'error', 'omnivalt'),
      '003' => _x('This shipping set does not exist', 'error', 'omnivalt'),
      '004' => _x('The service does not exist for the specified method', 'error', 'omnivalt'),
    );

    if ( ! isset($errors[$error_code]) ) {
      return _x('Unknown error', 'error', 'omnivalt');
    }

    return $errors[$error_code];
  }

  private static function load_classes()
  {
    require_once OMNIVALT_DIR . 'includes/class-debug.php';
    require_once OMNIVALT_DIR . 'includes/class-helper.php';
    require_once OMNIVALT_DIR . 'includes/class-emails.php';
    require_once OMNIVALT_DIR . 'includes/class-labels.php';
    require_once OMNIVALT_DIR . 'includes/class-api.php';
    require_once OMNIVALT_DIR . 'includes/class-admin-html.php';
    require_once OMNIVALT_DIR . 'includes/class-packer.php';
    require_once OMNIVALT_DIR . 'includes/class-product.php';
    require_once OMNIVALT_DIR . 'includes/class-cronjob.php';
    require_once OMNIVALT_DIR . 'includes/class-terminals.php';
    require_once OMNIVALT_DIR . 'includes/class-manifest.php';
    require_once OMNIVALT_DIR . 'includes/class-order.php';
    require_once OMNIVALT_DIR . 'includes/class-frontend.php';
  }

  private static function default_configs()
  {
    return array(
      'settings_key' => 'woocommerce_omnivalt_settings',
      'shipping_sets' => array(),
      'shipping_params' => array(),
      'method_params' => array(),
      'additional_services' => array(),
      'locations' => array(),
      'update' => array(),
      'text_variables' => array(),
      'debug' => array(
        'delete_after' => 30,
      ),
      'meta_keys' => array(
        'method' => '_omnivalt_method',
        'barcode' => '_omnivalt_barcode',
        'error' => '_omnivalt_error',
        'manifest_date' => '_omnivalt_manifest_date',
        'terminal_id' => '_omnivalt_terminal_id',
      ),
    );
  }

  private static function load_init_hooks()
  {
    add_action('woocommerce_shipping_init', 'OmnivaLt_Core::init_shipping_method');
    add_action('init', 'OmnivaLt_Core::textdomain');
    add_action('admin_notices', 'OmnivaLt_Core::admin_notices');
    add_action('after_plugin_row_' . OMNIVALT_BASENAME, 'OmnivaLt_Core::update_message', 10, 3);

    add_filter('plugin_action_links_' . OMNIVALT_BASENAME, 'OmnivaLt_Core::settings_link');
  }

  private static function load_launch_hooks()
  {
    add_action('init', 'OmnivaLt_Product::init');
    add_action('wp_enqueue_scripts', 'OmnivaLt_Core::load_front_scripts', 99);
    add_action('omniva_admin_manifest_head', 'OmnivaLt_Manifest::load_admin_scripts');
    add_action('admin_enqueue_scripts', 'OmnivaLt_Core::load_admin_settings_scripts');
    add_action('admin_enqueue_scripts', 'OmnivaLt_Order::load_admin_scripts');
    add_action('wp_footer', 'OmnivaLt_Core::add_to_footer');
    add_action('wp_ajax_nopriv_add_terminal_to_session', 'OmnivaLt_Terminals::add_terminal_to_session');
    add_action('wp_ajax_add_terminal_to_session', 'OmnivaLt_Terminals::add_terminal_to_session');
    add_action('wp_ajax_omniva_terminals_json', 'OmnivaLt_Terminals::get_terminals_json');
    add_action('wp_ajax_nopriv_omniva_terminals_json', 'OmnivaLt_Terminals::get_terminals_json');
    add_action('admin_menu', 'OmnivaLt_Manifest::register_menu_pages');
    add_action('woocommerce_after_shipping_rate', 'OmnivaLt_Order::after_rate_description', 20, 2);
    add_action('woocommerce_after_shipping_rate', 'OmnivaLt_Order::after_rate_terminals');
    add_action('woocommerce_checkout_update_order_meta', 'OmnivaLt_Order::add_terminal_id_to_order');
    add_action('woocommerce_review_order_before_cart_contents', 'OmnivaLt_Order::validate_order', 10);
    add_action('woocommerce_after_checkout_validation', 'OmnivaLt_Order::validate_order', 10);
    add_action('woocommerce_order_details_after_order_table', 'OmnivaLt_Order::show_selected_terminal', 10, 1);
    add_action('woocommerce_order_details_after_order_table', 'OmnivaLt_Order::show_tracking_link', 10, 1);
    add_action('woocommerce_email_after_order_table', 'OmnivaLt_Order::show_selected_terminal', 10, 1);
    add_action('woocommerce_admin_order_preview_end', 'OmnivaLt_Order::display_order_data_in_admin');
    add_action('wp_ajax_generate_omnivalt_label', 'OmnivaLt_Order::generate_label');
    add_action('woocommerce_checkout_update_order_meta', 'OmnivaLt_Order::add_cart_weight');
    add_action('print_omniva_tracking_url', 'OmnivaLt_Order::print_tracking_url_action', 10, 2);
    add_action('woocommerce_admin_order_data_after_shipping_address', 'OmnivaLt_Order::admin_order_display', 10, 2);
    add_action('save_post', 'OmnivaLt_Order::admin_order_save');
    add_action('woocommerce_checkout_process', 'OmnivaLt_Order::checkout_validate_terminal');

    add_filter('script_loader_tag', 'OmnivaLt_Core::add_asyncdefer_by_handle', 10, 2);
    add_filter('woocommerce_shipping_methods', 'OmnivaLt_Core::add_shipping_method');
    add_filter('admin_post_omnivalt_call_courier', 'OmnivaLt_Labels::post_call_courier_actions');
    add_filter('woocommerce_order_data_store_cpt_get_orders_query', 'OmnivaLt_Manifest::handle_custom_query_var', 10, 2);
    add_filter('woocommerce_package_rates', 'OmnivaLt_Order::restrict_shipping_methods_by_cats', 10, 1);
    add_filter('woocommerce_admin_order_preview_get_order_details', 'OmnivaLt_Order::admin_order_add_custom_meta_data', 10, 2);
    add_filter('bulk_actions-edit-shop_order', 'OmnivaLt_Order::bulk_actions', 20);
    add_filter('handle_bulk_actions-edit-shop_order', 'OmnivaLt_Order::handle_bulk_actions', 20, 3);
    add_filter('admin_post_omnivalt_labels', 'OmnivaLt_Order::post_label_actions', 20, 3);
    add_filter('admin_post_omnivalt_manifest', 'OmnivaLt_Order::post_manifest_actions', 20, 3);
    //add_filter('woocommerce_admin_order_actions_end', 'OmnivaLt_Order::order_actions', 10, 1);
    add_filter('woocommerce_cart_shipping_method_full_label', 'OmnivaLt_Frontend::add_logo_to_method', 10, 2);
  }

  private static function load_conditional_hooks()
  {
    $settings = self::get_settings();

    $track_info_in_emails = (isset($settings['track_info_in_email'])) ? $settings['track_info_in_email'] : 'yes';
    
    if ( $track_info_in_emails === 'yes' ) {
      add_action('woocommerce_email_after_order_table', 'OmnivaLt_Order::show_tracking_link', 10, 1);
    }
  }
}
