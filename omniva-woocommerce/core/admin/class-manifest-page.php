<?php
class OmnivaLt_Manifest_Page
{
  private const BARCODE_LOOKUP_CHUNK_SIZE = 100;

  private static $page_rendered = false;

  public static function load_admin_scripts()
  {
    $folder_css = '/assets/css/';
    $folder_js = '/assets/js/';

    wp_enqueue_style('omnivalt_admin_manifest', plugins_url($folder_css . 'omniva_admin_manifest.css', OmnivaLt_Core::$main_file_path), array(), OMNIVALT_VERSION);
    wp_enqueue_style('bootstrap-datetimepicker', plugins_url($folder_js . 'datetimepicker/bootstrap-datetimepicker.min.css', OmnivaLt_Core::$main_file_path));

    wp_enqueue_script('moment', plugins_url($folder_js . 'moment.min.js', OmnivaLt_Core::$main_file_path), array(), null, true);
    wp_enqueue_script('bootstrap-datetimepicker', plugins_url($folder_js . 'datetimepicker/bootstrap-datetimepicker.min.js', OmnivaLt_Core::$main_file_path), array('jquery', 'moment'), null, true);
    wp_enqueue_script('omniva_helper', plugins_url($folder_js . 'omniva_helper.js', OmnivaLt_Core::$main_file_path), array(), null, true);
    wp_enqueue_script('omniva_manifest', plugins_url($folder_js . 'omniva_manifest.js', OmnivaLt_Core::$main_file_path), array('jquery'), null, true);

    OmnivaLt_Admin_Page_Assets::add_readiness_fallback(
      'omniva_manifest',
      'omnivalt-manifest-root',
      'is-ready',
      'add'
    );

    wp_localize_script('omniva_manifest', 'omnivaglobals', array(
      'cookie_checked_list' => 'omniva_checked',
    ));

    wp_localize_script('omniva_manifest', 'omnivatext', array(
      'alert_select_orders' => __('Please select orders', 'omnivalt'),
    ));
  }

  public static function render_page()
  {
    if ( ! current_user_can('manage_woocommerce') || self::$page_rendered ) {
      return;
    }

    // Match the old include_once behaviour, including re-entrant page callbacks.
    self::$page_rendered = true;

    $page_data = self::prepare_page_data();

    // Keep the existing extension point and its execution order before the page markup.
    do_action('omniva_admin_manifest_head');

    include_once OMNIVALT_DIR . 'templates/admin/manifest-page.php';
  }

  private static function prepare_page_data()
  {
    $shipping_settings = OmnivaLt_Core::get_settings();
    $configs = OmnivaLt_Core::get_configs();
    $page_params = OmnivaLt_Manifest::page_params();
    $orders_data = OmnivaLt_Manifest::page_get_orders();
    $selected_orders = self::get_selected_orders();
    $sender_info_complete = OmnivaLt_Helper::has_required_sender_information($shipping_settings);
    $selected_order_has_barcodes = $sender_info_complete ? array() : self::get_selected_order_barcode_state($selected_orders);
    $manifest_enabled = (!isset($shipping_settings['manifest_enable']) || $shipping_settings['manifest_enable'] === 'yes') ? true : false;
    $active_omx = ($configs['api']['type'] === 'omx');
    $current_courier_calls = OmnivaLt_Helper::get_courier_calls();

    $active_filter_count = 0;
    $active_filters = (isset($orders_data['filters']) && is_array($orders_data['filters'])) ? $orders_data['filters'] : array();
    foreach ( array('id', 'customer', 'barcode', 'status') as $filter_key ) {
      $filter_value = isset($active_filters[$filter_key]) ? $active_filters[$filter_key] : false;
      if (false !== $filter_value && null !== $filter_value && '' !== trim((string) $filter_value) && ('status' !== $filter_key || '-1' !== (string) $filter_value)) {
        $active_filter_count++;
      }
    }

    foreach ( array('start_date', 'end_date') as $date_filter_key ) {
      if (isset($active_filters[$date_filter_key]) && '' !== trim((string) $active_filters[$date_filter_key])) {
        $active_filter_count++;
        break;
      }
    }

    $is_wrong_timezone = (OmnivaLt_Helper::get_timezone_offset(OmnivaLt_Helper::get_local_timezone_string()) !== OmnivaLt_Helper::get_timezone_offset('Europe/Tallinn'));
    $timezone_alert = __('The offset of the timezone of your website is different from the offset of the timezone of the Omniva server, so the courier call time is displayed differently than specified in the settings', 'omnivalt');

    return array(
      'shipping_settings' => $shipping_settings,
      'configs' => $configs,
      'page_params' => $page_params,
      'orders_data' => $orders_data,
      'selected_orders' => $selected_orders,
      'selected_order_has_barcodes' => $selected_order_has_barcodes,
      'sender_info_complete' => $sender_info_complete,
      'manifest_enabled' => $manifest_enabled,
      'active_omx' => $active_omx,
      'current_courier_calls' => $current_courier_calls,
      'active_filter_count' => $active_filter_count,
      'is_wrong_timezone' => $is_wrong_timezone,
      'timezone_alert' => $timezone_alert,
    );
  }

  private static function get_selected_order_barcode_state( $selected_orders )
  {
    $barcode_state = array();
    if ( ! is_array($selected_orders) ) {
      return $barcode_state;
    }

    $selected_orders = array_values(array_unique($selected_orders));

    if ( empty($selected_orders) ) {
      return $barcode_state;
    }

    $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
    if ( ! is_array($meta_keys) || empty($meta_keys['barcodes']) || ! is_string($meta_keys['barcodes']) ) {
      return $barcode_state;
    }

    $selected_order_count = count($selected_orders);
    $orders_with_barcodes_count = 0;

    foreach ( $selected_orders as $order_id ) {
      $barcode_state[(string) $order_id] = false;
    }

    foreach ( array_chunk($selected_orders, self::BARCODE_LOOKUP_CHUNK_SIZE) as $order_chunk ) {
      $orders_with_barcodes = wc_get_orders(array(
        'include' => $order_chunk,
        'limit' => count($order_chunk),
        'paginate' => false,
        'return' => 'ids',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The include list is limited to 100 selected order IDs per query.
        'meta_query' => array(
          array(
            'key' => $meta_keys['barcodes'],
            'value' => '',
            'compare' => '!=',
          ),
        ),
      ));

      if ( ! is_array($orders_with_barcodes) ) {
        continue;
      }

      foreach ( $orders_with_barcodes as $order_id ) {
        $order_key = (string) $order_id;
        if ( isset($barcode_state[$order_key]) && ! $barcode_state[$order_key] ) {
          $barcode_state[$order_key] = true;
          $orders_with_barcodes_count++;
        }
      }

      if ( $orders_with_barcodes_count === $selected_order_count ) {
        break;
      }
    }

    return $barcode_state;
  }

  private static function get_selected_orders()
  {
    if ( ! isset($_COOKIE['omniva_checked']) ) {
      return array();
    }

    if ( ! is_string($_COOKIE['omniva_checked']) ) {
      return array();
    }

    $cookie_value = json_decode(sanitize_text_field(wp_unslash($_COOKIE['omniva_checked'])), true);
    if ( ! is_array($cookie_value) ) {
      return array();
    }

    $selected_orders = array();
    foreach ( $cookie_value as $order_id ) {
      if ( ! is_string($order_id) && ! is_int($order_id) ) {
        continue;
      }

      $order_id = absint($order_id);
      if ( $order_id > 0 ) {
        $selected_orders[] = $order_id;
      }
    }

    return array_values(array_unique($selected_orders));
  }
}
