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

      $label_file_path = OMNIVALT_DIR . 'var/pdf/' . OmnivaLt_Helper::clear_file_name($order->number) . '.pdf';
      $label_file_content = false;
      
      if ( empty($barcodes) || ! $download ) {
        if ( file_exists($label_file_path) ) {
          unlink($label_file_path);
        }
        
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

      $label_status = $this->omnivalt_api->get_shipment_labels($barcodes);
      if ( ! $label_status['status'] ) {
        OmnivaLt_Omniva_Order::set_error($order->id, $label_status['msg']);
        OmnivaLt_Helper::add_msg($order->number . ' - ' . $label_status['msg'], 'error');
        continue;
      }

      if ( ! empty($label_status['file']) ) {
        $label_file_content = $label_status['file'];
      }

      if ( ! $download ) {
        OmnivaLt_Helper::add_msg($order->number . ' - ' . __('Omniva label generated', 'omnivalt'), 'updated');
      }

      if ( ! $label_file_content ) {
        continue;
      }

      file_put_contents($label_file_path, base64_decode($label_file_content));

      if ( ! file_exists($label_file_path) ) {
        continue;
      }
  
      OmnivaLt_Omniva_Order::set_error($order->id, '');
      
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

    if ( $download ) {
      $pdf->Output('Omnivalt_labels.pdf', 'D');
    }
  }

  public function print_manifest( $orders_ids )
  {
    OmnivaLt_Core::load_vendors(array('tcpdf'));

    if ( ! is_array($orders_ids) ) {
      $orderIds = array($orders_ids);
    }

    $object = '';
    $configs = OmnivaLt_Core::get_configs();

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();

    $order_table = '';
    $count = 0;
    if ( is_array($orders_ids) ) {
      foreach ( $orders_ids as $order_id ) {
        $order = OmnivaLt_Wc_Order::get_data($order_id);

        if ( ! OmnivaLt_Helper::is_omniva_method($order->shipment->method) ) {
          OmnivaLt_Helper::add_msg($order->id . ' - ' . __('Shipping method is not Omniva', 'omnivalt'), 'error');
          continue;
        }

        $tracking_numbers = $order->omniva->barcodes;
        if ( empty($tracking_numbers) ) {
          $status = $this->omnivalt_api->get_tracking_number($order->id);
          if ( ! empty($status['debug']) ) {
            OmnivaLt_Helper::add_msg('<b>OMNIVA RESPONSE DEBUG:</b><br/><pre style="white-space:pre-wrap;">' . htmlspecialchars($status['debug']) . '</pre>', 'notice');
          }

          if ( isset($status['status']) && $status['status'] === true && ! empty($status['barcodes']) ) {
            $tracking_numbers = $status['barcodes'];
            OmnivaLt_Omniva_Order::set_barcodes($order->id, $tracking_numbers);
            OmnivaLt_Wc_Order::add_note($order->id, '<b>Omniva:</b> ' .__('Registered labels', 'omnivalt') . ":\n" . implode(', ', $tracking_numbers));

            $label_status = $this->omnivalt_api->get_shipment_labels($status['barcodes']);
              
            if ( ! $label_status['status'] ) {
              OmnivaLt_Omniva_Order::set_error($order->id, $label_status['msg']);
              OmnivaLt_Helper::add_msg($order->number . ' - ' . $label_status['msg'], 'error');
              continue;
            }
          } else {
            OmnivaLt_Omniva_Order::set_error($order->id, $status['msg']);
            OmnivaLt_Helper::add_msg($order->number . ' - ' . $status['msg'], 'error');
            continue;
          }

          $order = OmnivaLt_Wc_Order::get_data($order->id);
        }
          
        $client_name = OmnivaLt_Order::get_customer_fullname_or_company($order);

        $pt_address = '';
        if ( $order->omniva->method == 'pt' || $order->omniva->method == 'po' ) {
          $pt_address = OmnivaLt_Terminals::get_terminal_address($order->omniva->terminal_id, true);
        }

        $client_address = OmnivaLt_Order::get_customer_full_address($order);
        if ( $pt_address != '' ) {
          $client_address = '';
        }

        $count++;
        $cart_weight = $order->shipment->size['weight'];
        
        $cell_shipment_number = '<td width="110">' . $tracking_numbers[0] . '</td>';
        if ( $this->omnivalt_settings['manifest_show_barcode'] === 'yes' ) {
          $barcode_params = $pdf->serializeTCPDFtagParameters(array($tracking_numbers[0], 'C128', '', '', 25, 6, 0.4, array('position'=>'C', 'border'=>false, 'padding'=>0, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N'));
          $cell_shipment_number = '<td width="110" style="line-height: 50%;"><tcpdf method="write1DBarcode" params="' . $barcode_params . '" /></td>';
        }
        $order_table .= '<tr><td width = "30" align="right">' . $count . '.</td>' . $cell_shipment_number . '<td width = "60">' . current_time('Y-m-d') . '</td><td width = "40">1</td><td width = "60">' . $cart_weight . '</td><td width = "">' . $client_name . ', ' . $client_address . $pt_address . '</td></tr>';
      }
    }

    $pdf->SetFont('freeserif', '', 14);
    $shop_addr = '<table cellspacing="0" cellpadding="1" border="0"><tr><td>' . current_time('Y-m-d H:i:s') . '</td><td>' . _x('Sender address', 'Manifest', 'omnivalt') . ':<br/>' . $this->omnivalt_settings['shop_name'] . '<br/>' . $this->omnivalt_settings['shop_address'] . ', ' . $this->omnivalt_settings['shop_postcode'] . '<br/>' . $this->omnivalt_settings['shop_city'] . ', ' . $this->omnivalt_settings['shop_countrycode'] . '<br/></td></tr></table>';

    $pdf->writeHTML($shop_addr, true, false, false, false, '');
    $tbl = '
      <table cellspacing="0" cellpadding="4" border="1" width="100%">
        <thead>
          <tr>
            <th width="30" align="right">' . _x('No.', 'Manifest', 'omnivalt') . '</th>
            <th width="110">' . _x('Shipment number', 'Manifest', 'omnivalt') . '</th>
            <th width="60">' . _x('Date', 'Manifest', 'omnivalt') . '</th>
            <th width="40">' . _x('Quantity', 'Manifest', 'omnivalt') . '</th>
            <th width="60">' . _x('Weight', 'Manifest', 'omnivalt') . ' (' . $order->units->weight . ')</th>
            <th width="">' . _x("Recipient's name and address", 'Manifest', 'omnivalt') . '</th>
          </tr>
        </thead>
        <tbody>
        ' . $order_table . '
        </tbody>
      </table><br/><br/>
    ';
    
    if ($count == 0) {
      OmnivaLt_Helper::add_msg(__('No compatible orders for manifest', 'omnivalt'), 'error');
      wp_safe_redirect(wp_get_referer());
      exit;
    }

    OmnivaLt_Omniva_Order::set_manifest_date($order->id, current_time('Y-m-d H:i:s'));

    $pdf->SetFont('freeserif', '', 9);
    $pdf->writeHTML($tbl, true, false, false, false, '');
    $pdf->SetFont('freeserif', '', 14);
    $sign = _x("Courier name, surname, signature", 'Manifest', 'omnivalt') . ' ________________________________________________<br/><br/>';
    $sign .= _x("Sender name, surname, signature", 'Manifest', 'omnivalt') . ' ________________________________________________';
    $pdf->writeHTML($sign, true, false, false, false, '');
    $pdf->Output('Omnivalt_manifest.pdf', 'D');
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
