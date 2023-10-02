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
        OmnivaLt_Omniva_Order::set_error($order->id, '');
        $status = $this->omnivalt_api->get_tracking_number($order->id);

        if ( ! empty($status['debug']) ) {
          OmnivaLt_Helper::add_msg('<b>OMNIVA RESPONSE DEBUG:</b><br/><pre style="white-space:pre-wrap;">' . htmlspecialchars($status['debug']) . '</pre>', 'notice');
        }

        if ( isset($status['status']) && $status['status'] === true ) {
          $barcodes = $status['barcodes'];
          OmnivaLt_Omniva_Order::set_barcodes($order->id, $barcodes);
          OmnivaLt_Wc_Order::add_note($order->id, '<b>Omniva:</b> ' . __('Registered labels', 'omnivalt') . ":\n" . implode(', ', $barcodes));

          $send_email = (isset($this->omnivalt_settings['email_created_label'])) ? $this->omnivalt_settings['email_created_label'] : 'yes';
          if ($send_email === 'yes') {
            $email_subject = (isset($this->omnivalt_settings['email_created_label_subject'])) ? $this->omnivalt_settings['email_created_label_subject'] : '';
            $email_params = array(
              'tracking_code' => $barcodes[0],
              'tracking_link' => $this->get_tracking_link(OmnivaLt_Order::get_customer_shipping_country($order), $barcodes[0], true),
              'subject' => $email_subject
            );
            $this->omnivalt_emails->send_label($order, $order->billing->email, $email_params);
          }
        } else {
          OmnivaLt_Omniva_Order::set_error($order->id, $status['msg']);
          OmnivaLt_Helper::add_msg($order->number . ' - ' . $status['msg'], 'error');
          continue;
        }
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
    
    return "<a href=\"" . $omniva_tracking_url[$country_code] . $barcode . "\" target=\"_blank\">" . $barcode . "</a>\n";
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
      $text = __('You can track your parcel with this number', 'omnivalt');
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
      $html = '<p><strong>' . $text . ':</strong> <br/>' . $barcodes_html . '</p>';
    }
    if ( $print ) {
      echo $html;
    }
    return $html;
  }

  public static function post_call_courier_actions()
  {
    $omnivalt_api = new OmnivaLt_Api();
    $callCarrierReturn = $omnivalt_api->call_courier(intval($_GET['call_quantity']));

    if ($callCarrierReturn['status'] == true)
      OmnivaLt_Helper::add_msg(__("Omniva courier called", 'omnivalt'), 'omniva-notice');
    else
      OmnivaLt_Helper::add_msg(__("There was an error calling Omniva courier. Error: " . $callCarrierReturn['msg'], 'omnivalt'), 'error');
    wp_safe_redirect(wp_get_referer());
  }
}
