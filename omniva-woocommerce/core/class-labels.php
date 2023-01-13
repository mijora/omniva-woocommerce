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

  public function print_labels($orderIds = false, $download = true, $regenerate = false)
  {
    if (empty($orderIds) || !$orderIds) {
      return;
    }

    OmnivaLt_Core::load_vendors(array('tcpdf', 'fpdi'));

    $print_type = (isset($this->omnivalt_settings['print_type'])) ? $this->omnivalt_settings['print_type'] : '4';
    $count = 0;
    $label_count = 0;

    $pdf = new \setasign\Fpdi\Tcpdf\Fpdi('P');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    if ( ! is_array($orderIds) )
      $orderIds = array($orderIds);

    foreach ( array_unique($orderIds) as $orderId ) {
      $order = get_post((int) $orderId);
      $wc_order = wc_get_order((int) $orderId);
      
      $send_method = "";
      foreach ( $wc_order->get_items('shipping') as $item_id => $shipping_item_obj ) {
        $send_method = $shipping_item_obj->get_method_id();
      }
      
      if ( $send_method == 'omnivalt' ) {
        $send_method = get_post_meta($orderId, $this->omnivalt_configs['meta_keys']['method'], true);
      }
      if ( ! in_array($send_method, $this->send_methods) ) {
        OmnivaLt_Helper::add_msg($orderId . ' - ' . __('Shipping method is not Omniva', 'omnivalt'), 'error');
        continue;
      }
      
      if ( $regenerate ) {
        update_post_meta($orderId, $this->omnivalt_configs['meta_keys']['barcode'], '');
      }

      $track_number = get_post_meta($orderId, $this->omnivalt_configs['meta_keys']['barcode'], true);
      $barcodes = array($track_number);

      $label_file_name = (!empty($track_number)) ? $track_number : rand(1, 1000);
      $label_file_path = OMNIVALT_DIR . 'var/pdf/' . $label_file_name . '.pdf';
      $label_file_content = false;
      
      if ( empty($track_number) || ! $download ) {
        if ( file_exists($label_file_path) ) {
          unlink($label_file_path);
        }
        
        $status = $this->omnivalt_api->get_tracking_number($orderId);

        if ( ! empty($status['debug']) ) {
          OmnivaLt_Helper::add_msg('<b>OMNIVA RESPONSE DEBUG:</b><br/><pre style="white-space:pre-wrap;">' . htmlspecialchars($status['debug']) . '</pre>', 'notice');
        }

        if ( isset($status['status']) && $status['status'] === true ) {
          $barcodes = $status['barcodes'];
          update_post_meta($orderId, $this->omnivalt_configs['meta_keys']['barcode'], $barcodes[0]);

          $send_email = (isset($this->omnivalt_settings['email_created_label'])) ? $this->omnivalt_settings['email_created_label'] : 'yes';
          if ($send_email === 'yes') {
            $email_subject = (isset($this->omnivalt_settings['email_created_label_subject'])) ? $this->omnivalt_settings['email_created_label_subject'] : '';
            $email_params = array(
              'tracking_code' => $barcodes[0],
              'tracking_link' => $this->get_tracking_link(OmnivaLt_Order::get_customer_shipping_country($wc_order), $barcodes[0], true),
              'subject' => $email_subject
            );
            $this->omnivalt_emails->send_label($wc_order, $wc_order->get_billing_email(), $email_params);
          }
        } else {
          update_post_meta($orderId, $this->omnivalt_configs['meta_keys']['error'], $status['msg']);
          OmnivaLt_Helper::add_msg($orderId . ' - ' . $status['msg'], 'error');
          continue;
        }
      }

      $label_status = $this->omnivalt_api->get_shipment_labels($barcodes, $orderId);
      if ( ! $label_status['status'] ) {
        update_post_meta($orderId, $this->omnivalt_configs['meta_keys']['error'], $label_status['msg']);
        OmnivaLt_Helper::add_msg($orderId . ' - ' . $label_status['msg'], 'error');
        continue;
      }

      if ( ! empty($label_status['file']) ) {
        $label_file_content = $label_status['file'];
      }

      if ( ! $download ) {
        OmnivaLt_Helper::add_msg($orderId . ' - ' . __('Omniva label generated', 'omnivalt'), 'updated');
      }

      if ( ! $label_file_content ) {
        continue;
      }

      file_put_contents($label_file_path, base64_decode($label_file_content));

      if ( ! file_exists($label_file_path) ) {
        continue;
      }
  
      update_post_meta($orderId, $this->omnivalt_configs['meta_keys']['error'], '');
      
      $pagecount = $pdf->setSourceFile($label_file_path);
      for ( $i = 1; $i <= $pagecount; $i++ ) {
        $tplidx = $pdf->ImportPage($i);
        if ( $print_type == '1' ) {
          $s = $pdf->getTemplatesize($tplidx);
          $pdf->AddPage('P', array($s['width'], $s['height']));
          $pdf->useTemplate($tplidx);
        } else if ( $print_type == '4' ) {
          if ( $label_count == 0 || $label_count == 4 ) {
            $pdf->AddPage('P');
            $label_count = 0;
            $pdf->useTemplate($tplidx, 5, 15, 94.5, 108, false);
          } else if ( $label_count == 1 ) {
            $pdf->useTemplate($tplidx, 110, 15, 94.5, 108, false);
          } else if ( $label_count == 2 ) {
            $pdf->useTemplate($tplidx, 5, 160, 94.5, 108, false);
          } else if ( $label_count == 3 ) {
            $pdf->useTemplate($tplidx, 110, 160, 94.5, 108, false);
          }
          $label_count++;
        }
      }

      unlink($label_file_path);
      $count++;
    }
    
    if ( $count == 0 ) {
      wp_safe_redirect(wp_get_referer());
      exit;
    }
    
    if ( $download )
      $pdf->Output('Omnivalt_labels.pdf', 'D');
  }

  public function get_tracking_link($country_code, $barcode, $link_only = false)
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

  public function print_tracking_link($order, $admin_panel = true, $print = true)
  {
    $shipping_settings = OmnivaLt_Core::get_settings();
    
    $barcode = $order->get_meta($this->omnivalt_configs['meta_keys']['barcode']);
    if ( $admin_panel ) {
      $country_code = $shipping_settings['shop_countrycode'];
      $text = __('Omniva tracking number', 'omnivalt');
    } else {
      $country_code = OmnivaLt_Order::get_customer_shipping_country($order);
      $text = __('You can track your parcel with this number', 'omnivalt');
    }

    $html = '';
    if ( ! empty($barcode) ) {
      $html = '<p><strong>' . $text . ':</strong> <br/>' . $this->get_tracking_link($country_code, $barcode) . '</p>';
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
