<?php
class OmnivaLt_Labels
{
  private $omnivalt_api;
  private $omnivalt_emails;
  private $omnivalt_settings;
  private $omnivalt_configs;
  
  private $send_methods = array();

  public function __construct()
  {
    $this->omnivalt_api = new OmnivaLt_Api();
    $this->omnivalt_emails = new OmnivaLt_Emails();
    $this->omnivalt_configs = OmnivaLt_Core::get_configs();
    $this->omnivalt_settings = OmnivaLt_Core::get_settings();
    
    foreach ( $this->omnivalt_configs['method_params'] as $method_name => $method_values ) {
      if ( ! $method_values['is_shipping_method'] ) continue;
      $this->send_methods[$method_values['key']] = 'omnivalt_' . $method_values['key'];
    }
  }

  public function print_labels( $orderIds = false, $download = true, $regenerate = false )
  {
    if (empty($orderIds) || !$orderIds) {
      return;
    }

    if ( ! is_array($orderIds) )
      $orderIds = array($orderIds);

    $all_barcodes = array();
    foreach ( array_unique($orderIds) as $orderId ) {
      $order = OmnivaLt_Wc_Order::get_data($orderId, array('shipment', 'shipping', 'billing'));
      if ( ! $order ) {
        continue;
      }
      
      $send_method = $order->shipment->method;
      if ( ! in_array($send_method, $this->send_methods) ) {
        OmnivaLt_Helper::add_msg($order->number . ' - ' . __('Shipping method is not Omniva', 'omnivalt'), 'error');
        continue;
      }
      
      if ( $regenerate ) {
        OmnivaLt_Omniva_Order::set_barcodes($order->id, '');
      }

      $barcodes = OmnivaLt_Omniva_Order::get_barcodes($order->id);

      if ( empty($barcodes) ) {
        $barcodes = $this->register_label($order);
      }

      if ( ! is_array($barcodes) ) {
        continue;
      }

      foreach ( $barcodes as $barcode ) {
        $all_barcodes[] = $barcode;
      }
    }

    $labels_status = $this->omnivalt_api->download_shipment_labels($all_barcodes);
    if ( ! $labels_status['status'] ) {
      OmnivaLt_Helper::add_msg($labels_status['msg'], 'error');
      wp_safe_redirect(wp_get_referer());
    }

    exit;
  }

  private function register_label( $order )
  {
    OmnivaLt_Omniva_Order::set_error($order->id, '');
    $status = $this->omnivalt_api->get_tracking_number($order->id);

    if ( ! empty($status['debug']) && isset($this->omnivalt_settings['debug_notice']) && $this->omnivalt_settings['debug_notice'] === 'yes' ) {
      OmnivaLt_Helper::add_msg('<b>OMNIVA RESPONSE DEBUG:</b><br/><pre style="white-space:pre-wrap;">' . htmlspecialchars($status['debug']) . '</pre>', 'notice');
    }

    if ( isset($status['status']) && $status['status'] === true ) {
      $barcodes = $status['barcodes'];
      OmnivaLt_Omniva_Order::set_barcodes($order->id, $barcodes);
      OmnivaLt_Wc_Order::add_note($order->id, '<b>Omniva:</b> ' . __('Registered labels', 'omnivalt') . ":\n" . implode(', ', $barcodes));

      $send_email = (isset($this->omnivalt_settings['email_created_label'])) ? $this->omnivalt_settings['email_created_label'] : 'yes';
      if ($send_email === 'yes') {
        $email_subject = (isset($this->omnivalt_settings['email_created_label_subject'])) ? $this->omnivalt_settings['email_created_label_subject'] : '';
        $customer_country = OmnivaLt_Order::get_customer_shipping_country($order);
        $tracking_codes = $this->build_tracking_links($customer_country, $barcodes, true);
        $email_params = array(
          'tracking_code' => $barcodes[0],
          'tracking_link' => $this->get_tracking_link($customer_country, $barcodes[0], true),
          'tracking_codes' => $tracking_codes,
          'subject' => $email_subject
        );
        $this->omnivalt_emails->send_label($order, $order->billing->email, $email_params);
      }

      do_action('omnivalt_label_register_successfully', $order->id);

      return $barcodes;
    }
    
    OmnivaLt_Omniva_Order::set_error($order->id, $status['msg']);
    OmnivaLt_Helper::add_msg($order->number . ' - ' . $status['msg'], 'error');

    do_action('omnivalt_label_register_failed', $order->id);

    return array();
  }

  public function print_manifest( $orders_ids )
  {
    $result = $this->omnivalt_api->get_manifest($orders_ids);

    if ( ! $result['status'] ) {
      OmnivaLt_Helper::add_msg(__('Failed to get manifest', 'omnivalt') . '. ' . __('Error', 'omnivalt') . ': ' . $result['msg'], 'error');
      wp_safe_redirect(wp_get_referer());
      exit;
    }
    if ( empty($result['success']) ) {
      OmnivaLt_Helper::add_msg(__('No compatible orders for manifest', 'omnivalt'), 'error');
      wp_safe_redirect(wp_get_referer());
      exit;
    }

    foreach ( $result['success'] as $order_id ) {
      OmnivaLt_Omniva_Order::set_manifest_date($order_id, current_time('Y-m-d H:i:s'));
    }

    exit;
  }

  public function get_tracking_link( $country_code, $barcode, $link_only = false )
  {
    $country_code = strtoupper($country_code);
    $omniva_tracking_url = array();
 
    foreach ( $this->omnivalt_configs['shipping_params'] as $ship_country => $ship_values ) {
      $omniva_tracking_url[$ship_country] = $ship_values['tracking_url'];
    }
    
    if ( ! isset($omniva_tracking_url[$country_code]) || empty($omniva_tracking_url[$country_code]) ) {
      return $barcode;
    }
    if ( $link_only ) {
      return $omniva_tracking_url[$country_code] . $barcode;
    }
    
    return "<a href=\"" . $omniva_tracking_url[$country_code] . $barcode . "\" target=\"_blank\">" . $barcode . "</a>";
  }

  public function print_tracking_link( $order, $admin_panel = true, $print = true )
  {
    $shipping_settings = OmnivaLt_Core::get_settings();
    $barcodes = $order->omniva->barcodes;
    
    if ( $admin_panel ) {
      $country_code = $shipping_settings['shop_countrycode'];
      $text = __('Omniva tracking number', 'omnivalt');
    } else {
      $country_code = OmnivaLt_Order::get_customer_shipping_country($order);
      $text = __('You can track your parcels with this numbers', 'omnivalt');
    }

    $html = '';
    if ( ! empty($barcodes) ) {
      $barcodes_html = '';
      foreach ( $barcodes as $barcode ) {
        if ( ! empty($barcodes_html) ) {
          $barcodes_html .= ', ';
        }
        $barcodes_html .= $this->get_tracking_link($country_code, $barcode);
      }
      $html = '<p><strong>' . $text . ':</strong> ' . $barcodes_html . '</p>';
    }
    if ( $print ) {
      echo $html;
    }
    return $html;
  }

  public function build_tracking_links( $country_code, $barcodes, $link_only = false )
  {
    if ( ! is_array($barcodes) ) {
      $barcodes = array($barcodes);
    }
    
    $links = array();
    
    foreach ( $barcodes as $barcode ) {
      $links[$barcode] = $this->get_tracking_link($country_code, $barcode, $link_only);
    }

    return $links;
  }

  public static function post_call_courier_actions()
  {
    $shipping_settings = OmnivaLt_Core::get_settings();
    $omnivalt_api = new OmnivaLt_Api();

    $call_params = array();
    $call_params['quantity'] = intval($_GET['call_quantity']);
    $call_params['heavy'] = (isset($_GET['call_checkboxes']) && in_array('heavy', $_GET['call_checkboxes']));
    $call_params['twoman'] = (isset($_GET['call_checkboxes']) && in_array('twoman', $_GET['call_checkboxes']));

    $call_result = $omnivalt_api->call_courier($call_params);

    if ( ! empty($call_result['debug']) && isset($shipping_settings['debug_notice']) && $shipping_settings['debug_notice'] === 'yes' ) {
      OmnivaLt_Helper::add_msg('<b>OMNIVA RESPONSE DEBUG:</b><br/><pre style="white-space:pre-wrap;">' . htmlspecialchars(print_r($call_result['debug'], true)) . '</pre>', 'notice');
    }

    if ( $call_result['status'] == true ) {
      if ( isset($call_result['call_id']) ) {
        $result = OmnivaLt_Helper::update_courier_calls(array(
          'id' => esc_attr($call_result['call_id']),
          'start' => esc_attr($call_result['start_time']),
          'end' => esc_attr($call_result['end_time']),
        ));
        $arrival_start = date('Y-m-d H:i', strtotime($call_result['start_time']));
        $arrival_end = date('Y-m-d H:i', strtotime($call_result['end_time']));
      } else {
        $pick_day = date('Y-m-d');
        $pick_start = OmnivaLt_Helper::get_formated_time($shipping_settings['pick_up_start'], '08:00');
        $pick_end = OmnivaLt_Helper::get_formated_time($shipping_settings['pick_up_end'], '08:00');
        if ( time() > strtotime($pick_day . ' ' . $pick_start) ) {
          $pick_day = date('Y-m-d', strtotime($pick_day . "+1 days"));
        }
        $arrival_start = date('Y-m-d H:i', strtotime($pick_day . ' ' . $pick_start));
        $arrival_end = date('Y-m-d H:i', strtotime($pick_day . ' ' . $pick_end));
      }

      OmnivaLt_Helper::add_msg(sprintf(__('Omniva courier called. Arrival time between %s.', 'omnivalt'), $arrival_start . ' - ' . $arrival_end), 'success');
    } else {
      OmnivaLt_Helper::add_msg(__("There was an error calling Omniva courier. Error: " . $call_result['msg'], 'omnivalt'), 'error');
    }
    wp_safe_redirect(wp_get_referer());
  }

  public static function post_cancel_courier_actions()
  {
    if ( ! isset($_GET['omnivalt_cancel_courier_nonce']) || ! wp_verify_nonce($_GET['omnivalt_cancel_courier_nonce'], 'omnivalt_cancel_courier') ) {
      OmnivaLt_Helper::add_msg(__('Request security check failed', 'omnivalt'), 'error');
      wp_safe_redirect(wp_get_referer());
      return;
    }

    if ( empty(esc_attr($_GET['call_id'])) ) {
      OmnivaLt_Helper::add_msg(__('Failed to get courier invitation number', 'omnivalt'), 'error');
      wp_safe_redirect(wp_get_referer());
      return;
    }
    $call_id = esc_attr($_GET['call_id']);

    $omnivalt_api = new OmnivaLt_Api();
    $result = $omnivalt_api->cancel_courier_call($call_id);
    if ( ! $result['status'] ) {
      OmnivaLt_Helper::add_msg($result['msg'], 'error');
      wp_safe_redirect(wp_get_referer());
      return;
    }

    OmnivaLt_Helper::remove_courier_calls($call_id);

    OmnivaLt_Helper::add_msg('Courier call canceled successfully', 'success');
    wp_safe_redirect(wp_get_referer());
  }

  public static function ajax_remove_courier_call()
  {
    $result = array(
      'status' => 'error',
      'msg' => '',
    );
    
    if ( empty(esc_attr($_POST['call_id'])) ) {
      $result['msg'] = __('Failed to get courier invitation number', 'omnivalt');
      echo json_encode($result);
      wp_die();
    }

    $call_id = esc_attr($_POST['call_id']);
    OmnivaLt_Helper::remove_courier_calls($call_id);
    $result['status'] = 'OK';

    echo json_encode($result);
    wp_die();
  }
}
