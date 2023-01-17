<?php
class OmnivaLt_Order
{
  public static function load_admin_scripts($hook)
  {
    global $post;

    if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
      if ( $post->post_type === 'shop_order' ) {
        wp_enqueue_script('omniva_admin_order', plugins_url( '/assets/js/omniva_admin_order.js', OmnivaLt_Core::$main_file_path ), array('jquery'), OMNIVALT_VERSION );
      }
    }
  }

  public static function after_rate_description($method, $index)
  {
    if ( is_cart() ) return; // Exit on cart page

    $customer = WC()->session->get('customer');
    if ( ! isset($customer['country']) ) {
      return;
    }

    $shipping_settings = OmnivaLt_Core::get_settings();
    if ( ! isset($shipping_settings['prices_' . $customer['country']]) ) {
      return;
    }

    $rate_settings = json_decode($shipping_settings['prices_' . $customer['country']]);
    $shipping_methods = OmnivaLt_Core::get_configs('method_params');

    foreach ( $shipping_methods as $ship_method => $ship_method_values ) {
      if ( ! $ship_method_values['is_shipping_method'] ) continue;

      if ( ! empty($rate_settings->{$ship_method_values['key'] . "_description"}) && $method->id === 'omnivalt_' . $ship_method_values['key'] ) {
        echo '<span class="omnivalt-shipping-description">' . $rate_settings->{$ship_method_values['key'] . "_description"} . '</span>';
      }
    }
  }

  public static function after_rate_terminals($method)
  {
    $customer = WC()->session->get('customer');
    $country = "ALL";
    if ( isset($customer['shipping_country']) ) {
      $country = $customer['shipping_country'];
    } elseif ( isset($customer['country']) ) {
      $country = $customer['country'];
    }
    
    $termnal_id = WC()->session->get('omnivalt_terminal_id');
    
    $selected_shipping_method = WC()->session->get('chosen_shipping_methods');
    if ( empty($selected_shipping_method) ) {
      $selected_shipping_method = array();
    }
    if ( ! is_array($selected_shipping_method) ) {
      $selected_shipping_method = array($selected_shipping_method);
    }

    if ( $method->id == "omnivalt_pt" && in_array("omnivalt_pt", $selected_shipping_method) ) {
      echo OmnivaLt_Terminals::get_terminals_options($termnal_id, $country);
    }
    if ( $method->id == "omnivalt_po" && in_array("omnivalt_po", $selected_shipping_method) ) {
      echo OmnivaLt_Terminals::get_terminals_options($termnal_id, $country, 'post');
    }
  }

  /**
   * Restrict Omniva Shipping methods if cart products has restricted categories
   */
  public static function restrict_shipping_methods_by_cats($rates)
  {
    global $woocommerce;
    $configs = OmnivaLt_Core::get_configs();
    $settings = get_option($configs['settings_key']);
    $cart_categories_ids = array();

    foreach( $woocommerce->cart->get_cart() as $cart_item ) {
      $cats = get_the_terms($cart_item['product_id'], 'product_cat');
      if ( empty($cats) ) {
        continue;
      }
      foreach ( $cats as $cat ) {
        $cart_categories_ids[] = $cat->term_id;
        if ( $cat->parent != 0 ) {
          $cart_categories_ids[] = $cat->parent;
        }
      }
    }
    $cart_categories_ids = array_unique($cart_categories_ids);

    $restricted_categories = $settings['restricted_categories'];
    if ( ! is_array($restricted_categories) ) {
      $restricted_categories = array($restricted_categories);
    }

    foreach ( $cart_categories_ids as $cart_product_categories_id ) {
      if ( in_array($cart_product_categories_id, $restricted_categories) ) {
        foreach ( $configs['method_params'] as $ship_method => $ship_method_values ) {
          if ( ! $ship_method_values['is_shipping_method'] ) continue;
          unset($rates['omnivalt_' . $ship_method_values['key']]);
        }
        break;
      }
    }

    return $rates;
  }

  /**
   * Restrict Omniva Shipping methods if cart products has restricted shipping classes
   */
  public static function restrict_shipping_methods_by_shipclass($rates)
  {
    global $woocommerce;
    $configs = OmnivaLt_Core::get_configs();
    $settings = get_option($configs['settings_key']);
    $cart_classes_ids = array();

    foreach( $woocommerce->cart->get_cart() as $cart_item ) {
      $shipping_classes = get_the_terms($cart_item['product_id'], 'product_shipping_class');
      if ( empty($shipping_classes) ) {
        continue;
      }
      foreach ( $shipping_classes as $class ) {
        $cart_classes_ids[] = $class->term_id;
      }
    }
    $cart_classes_ids = array_unique($cart_classes_ids);

    $restricted_shipclass = $settings['restricted_shipclass'] ?? array();
    if ( ! is_array($restricted_shipclass) ) {
      $restricted_shipclass = array($restricted_shipclass);
    }

    foreach ( $cart_classes_ids as $cart_product_class_id ) {
      if ( in_array($cart_product_class_id, $restricted_shipclass) ) {
        foreach ( $configs['method_params'] as $ship_method => $ship_method_values ) {
          if ( ! $ship_method_values['is_shipping_method'] ) continue;
          unset($rates['omnivalt_' . $ship_method_values['key']]);
        }
        break;
      }
    }

    return $rates;
  }

  public static function add_terminal_id_to_order($order_id)
  {
    $configs = OmnivaLt_Core::get_configs();

    if ( isset($_POST['omnivalt_terminal']) && $order_id ) {
      self::set_omniva_terminal_id($order_id, $_POST['omnivalt_terminal']);
    }

    if ( isset($_POST['shipping_method']) && $order_id ) {
      self::set_omniva_method($order_id, $_POST['shipping_method']);
    }
  }

  public static function validate_order($posted)
  {
    $packages = WC()->shipping->get_packages();
    $chosen_methods = WC()->session->get('chosen_shipping_methods');
    if ( is_array($chosen_methods) && in_array('omnivalt', $chosen_methods) ) {
      foreach ( $packages as $i => $package ) {
        if ( $chosen_methods[$i] != 'omnivalt' ) {
          continue;
        }

        $shipping_settings = OmnivaLt_Core::get_settings();
        $weightLimit = (int) $shipping_settings['weight'];
        $weight = 0;
        foreach ( $package['contents'] as $item_id => $values ) {
          $_product = $values['data'];
          $weight = $weight + $_product->get_weight() * $values['quantity'];
        }

        $weight = wc_get_weight($weight, 'kg');
        if ( $weight > $weightLimit ) {
          $message = sprintf(__('Sorry, %d kg exceeds the maximum weight of %d kg for %s', 'omnivalt'), $weight, $weightLimit, __('Omniva shipping', 'omnivalt'));
          $messageType = "error";
          if ( ! wc_has_notice($message, $messageType) ) {
            wc_add_notice($message, $messageType);
          }
        }
      }
    }
  }

  public static function check_terminal_id_in_order($order)
  {
    $configs = OmnivaLt_Core::get_configs();

    $check_terminal_id = self::get_omniva_terminal_id($order);
    $check_method = self::get_omniva_method($order);

    if ( ! empty($_POST['omnivalt_terminal']) && empty($check_terminal_id) ) {
      self::set_omniva_terminal_id($order->get_id(), $_POST['omnivalt_terminal']);
    }

    if ( ! empty($_POST['shipping_method']) ) {
      self::set_omniva_method($order->get_id(), $_POST['shipping_method']);
    }
  }

  public static function show_selected_terminal($order)
  {
    $configs_methods = OmnivaLt_Core::get_configs('method_params');
    $send_method = self::get_omniva_method($order);

    foreach ( $configs_methods as $method_key => $method_values ) {
      if ( ! $method_values['is_shipping_method'] ) continue;
      if ( $send_method != 'omnivalt_' . $method_values['key'] ) continue;

      if ( $method_values['key'] == 'pt' || $method_values['key'] == 'po' ) {
        echo '<p>Omniva ' . strtolower($method_values['title']) . ": " . OmnivaLt_Terminals::get_terminal_address($order) . '</p>';
      }
    }
  }

  public static function show_tracking_link($order)
  {
    $send_method = self::get_omniva_method($order);

    if ( $send_method ) {
      $omnivalt_labels = new OmnivaLt_Labels();
      $omnivalt_labels->print_tracking_link($order, false, true);
    }
  }

  /**
   * Add custom order meta data to make it accessible in Order preview template
   */
  public static function admin_order_add_custom_meta_data($data, $order)
  {
    $configs = OmnivaLt_Core::get_configs();
    $send_method = self::get_omniva_method($order);

    foreach ( $configs['method_params'] as $method_key => $method_values ) {
      if ( ! $method_values['is_shipping_method'] ) continue;
      if ( $send_method != 'omnivalt_' . $method_values['key'] ) continue;

      if ( $method_values['key'] == 'pt' || $method_values['key'] == 'po' ) {
        $data['shipping_via'] = 'Omniva ' . strtolower($method_values['title']) . ": " . OmnivaLt_Terminals::get_terminal_address($order);
      }
    }

    if ($send_method) {
      $shipping_settings = OmnivaLt_Core::get_settings();
      $omnivalt_labels = new OmnivaLt_Labels();

      $barcode = $order->get_meta($configs['meta_keys']['barcode']);
      $country_code = $shipping_settings['shop_countrycode'];
      $data['omnivalt_tracking_link'] = $omnivalt_labels->get_tracking_link($country_code, $barcode, true);
      $data['omnivalt_barcode'] = $barcode;
    }

    return $data;
  }

  public static function display_order_data_in_admin()
  {  
    echo '<# if ( data.omnivalt_barcode ) { #>' .
      '<p><div class="wc-order-preview-addresses">' .
      '<div class="wc-order-preview-address">' .
      '<strong>' . __('Omniva tracking number', 'omnivalt') .':</strong><a href="{{data.omnivalt_tracking_link}}" target="_blank">{{data.omnivalt_barcode}}</a>' .
      '</div></div></p>' .
      '<# } #>';
  }

  public static function bulk_actions($actions)
  {
    $actions['omnivalt_labels'] = __('Print Omniva labels', 'omnivalt');
    $actions['omnivalt_manifest'] = __('Print Omniva manifest', 'omnivalt');
    return $actions;
  }

  public static function handle_bulk_actions($redirect_to, $action, $ids)
  {
    if ( $action == "omnivalt_labels" ) {
      $omnivalt_labels = new OmnivaLt_Labels();
      $omnivalt_labels->print_labels($ids);
      return 0;
    }

    if ( $action == "omnivalt_manifest" ) {
      $wc_shipping = new WC_Shipping();
      $omnivalt = new Omnivalt_Shipping_Method();
      $omnivalt->printBulkManifests($ids);
      return 0;
    }

    return $redirect_to;
  }

  public static function post_label_actions()
  {
    $regenerate = false;
    if ( isset($_REQUEST['process']) && $_REQUEST['process'] === 'regenerate' ) {
      $regenerate = true;
    }

    $omnivalt_labels = new OmnivaLt_Labels();
    $omnivalt_labels->print_labels($_REQUEST['post'], true, $regenerate);
  }

  public static function post_manifest_actions()
  {
    $wc_shipping = new WC_Shipping();
    $omnivalt = new Omnivalt_Shipping_Method();
    $omnivalt->printBulkManifests($_REQUEST['post']);
  }

  /*public static function order_actions($order) //Disabled because not found where to using (possible junk from old plugin versions)
  {
    $order_id = $order->get_id();
    $send_method = get_post_meta($order_id, '_shipping_method', true);
    $configs_methods = OmnivaLt_Core::get_configs('method_params');

    foreach ( $configs_methods as $method_name => $method_values ) {
      if ( ! $method_values['is_shipping_method'] ) continue;

      if ( isset($send_method[0]) && $send_method[0] == 'omnivalt_' . $method_values['key'] ) {
        echo '<a class="button tips omnivalt_generate_label" href="' . wp_nonce_url(admin_url('admin-ajax.php?action=generate_omnivalt_label&order_id=' . $order_id), 'woocommerce-mark-order-status') . '" data-tip="' . __('Generate Omniva label', 'omnivalt') . '"> </a>';
        if ( file_exists(OMNIVALT_DIR . "var/pdf/" . $order_id . '.pdf') ) {
          echo '<a class="button tips omnivalt_view_label" href="' . plugins_url('var/pdf/' . $order_id . '.pdf', OmnivaLt_Core::$main_file_path) . '" target = "_blank" data-tip="' . __('View Omniva label', 'omnivalt') . '"> </a>';
        }
      }
    }
  }*/

  public static function generate_label()
  {
    if ( current_user_can('edit_shop_orders') && check_admin_referer('woocommerce-mark-order-status') ) {
      $omnivalt_labels = new OmnivaLt_Labels();
      $omnivalt_labels->print_labels($_GET['order_id'], false);
    }
    wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url('edit.php?post_type=shop_order'));
    exit;
  }

  /**
   * Add weight to order
   */
  public static function add_cart_weight($order_id)
  {
    global $woocommerce;
    $weight = $woocommerce->cart->cart_contents_weight;
    update_post_meta($order_id, '_cart_weight', $weight);
  }

  public static function print_tracking_url_action($barcode, $country_code = 'LT')
  {
    $omnivalt_labels = new OmnivaLt_Labels();
    echo $omnivalt_labels->get_tracking_link($country_code, $barcode);
  }

  /**
   * Display and edit Omniva info on the order edit page
   */
  public static function admin_order_display($order, $print_barcode = true, $admin_panel = true)
  {

    $configs = OmnivaLt_Core::get_configs();
    $send_method = self::get_omniva_method($order);
    $omnivalt_labels = new OmnivaLt_Labels();
    
    $is_omniva = false;
    foreach ( $configs['method_params'] as $ship_method => $ship_values ) {
      if ( ! $ship_values['is_shipping_method'] ) continue;

      if ( $send_method == 'omnivalt_' . $ship_values['key'] ) {
        $is_omniva = true;
      }
    }
    
    if ( ! $is_omniva ) return;

    global $post_type;
    $only_in_order = true;
    if ( 'shop_order' != $post_type ) {
      $only_in_order = false;
    }

    if ( $only_in_order ) {
      echo '<br class="clear"/>';
      echo '<hr style="margin-top:20px;">';
      echo '<h4>' . __('Omniva shipping', 'omnivalt') . '</h4>';
    }
    
    echo '<div class="address">';
    foreach ( $configs['method_params'] as $ship_method => $ship_values ) {
      if ( ! $ship_values['is_shipping_method'] ) continue;
      if ( $send_method != 'omnivalt_' . $ship_values['key'] ) continue;

      $field_value = $order->get_formatted_shipping_address();
      if ( $ship_values['key'] == 'pt' || $ship_values['key'] == 'po' ) {
        $field_value = OmnivaLt_Terminals::get_terminal_address($order);
      }
      
      echo '<p><strong class="title">' . $ship_values['title'] . ':</strong> ' . $field_value . '</p>';
    }

    $services = OmnivaLt_Helper::get_order_services($order);

    if ( ! empty($services) ) {
      echo '<p><strong class="title">' . __('Services', 'omnivalt') . ':</strong> ';
      $output = '';
      foreach ( $services as $service ) {
        if ( ! empty($output) ) $output .= ', ';
        foreach ( $configs['additional_services'] as $service_key => $service_values ) {
          if ( $service === $service_key ) $output .= $service_values['title'];
        }
      }
      echo $output . '</p>';
    }

    if ( $print_barcode ) {
      echo str_replace('<br/>', '', $omnivalt_labels->print_tracking_link($order, $admin_panel, false));
    }
    echo '</div>';
    
    if ( ! $only_in_order ) {
      return;
    }

    echo '<div class="edit_address">';
    if ( $send_method == 'omnivalt_pt' || $send_method == 'omnivalt_po' ) {
      $values = array(
        'terminal_key' => 'terminal',
        'change_title' => __('Change parcel terminal', 'omnivalt'),
      );
      if ( $send_method == 'omnivalt_po' ) {
        $values['terminal_key'] = 'post';
        $values['change_title'] = __('Change post office', 'omnivalt');
      }

      $all_terminals = OmnivaLt_Terminals::get_terminals_list('ALL', $values['terminal_key']);
      $selected_terminal = get_post_meta($order->get_id(), $configs['meta_keys']['terminal_id'], true);
      
      echo '<p class="form-field-wide">';
      echo '<label for="omnivalt_terminal">' . $values['change_title'] . '</label>';
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
    } else {
      echo __('The delivery address is changed in the fields above', 'omnivalt');
    }
    
    foreach ( $configs['additional_services'] as $service_key => $service_values ) {
      if ( $service_values['add_always'] ) continue;
      if ( ! $service_values['in_order'] ) continue;
      
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

  public static function admin_order_save($post_id)
  {
    global $post_type;
    if ( 'shop_order' != $post_type ) {
      return $post_id;
    }

    $configs = OmnivaLt_Core::get_configs();

    if ( isset($_POST['omnivalt_terminal_id']) ) {
      update_post_meta($post_id, $configs['meta_keys']['terminal_id'], wc_clean($_POST['omnivalt_terminal_id']));
    }

    foreach ( $configs['additional_services'] as $service_key => $service_values ) {
      if ( isset($_POST['omnivalt_' . $service_key]) ) {
        update_post_meta($post_id, '_omnivalt_' . $service_key, wc_clean($_POST['omnivalt_' . $service_key]));
      }
    }

    return $post_id;
  }

  public static function checkout_validate_terminal()
  {
    $messages = array(
      'pt' => __('Please select parcel terminal.', 'omnivalt'),
      'po' => __('Please select post office.', 'omnivalt'),
    );

    foreach ( $messages as $key => $message ) {
      if ( isset($_POST['shipping_method']) && in_array('omnivalt_' . $key, $_POST['shipping_method']) ) {
        if ( empty($_POST['omnivalt_terminal']) ) {
          wc_add_notice($message, 'error');
        }
      }
    }
  }

  public static function set_omniva_method($order_id, $order_methods_list)
  {
    if ( empty($order_id) ) {
      return false;
    }

    if ( ! empty($order_methods_list) && is_array($order_methods_list) ) {
      $configs = OmnivaLt_Core::get_configs();

      foreach ( $order_methods_list as $ship_method ) {
        foreach ( $configs['method_params'] as $method_name => $method_values ) {
          if ( ! $method_values['is_shipping_method'] ) continue;
          if ( $ship_method == "omnivalt_" . $method_values['key'] ) {
            update_post_meta($order_id, $configs['meta_keys']['method'], $ship_method);
            return true;
          }
        }
      }
    }

    return false;
  }

  public static function get_omniva_method($order)
  {
    $configs_meta = OmnivaLt_Core::get_configs('meta_keys');
    $wc_order = wc_get_order((int) $order->get_id());
    $send_method = "";
    foreach ( $wc_order->get_items('shipping') as $item_id => $shipping_item_obj ) {
      $send_method = $shipping_item_obj->get_method_id();
    }
    if ( $send_method == 'omnivalt' ) {
      return get_post_meta($order->get_id(), $configs_meta['method'], true);
    }
    return false;
  }

  public static function set_omniva_terminal_id($order_id, $terminal_id)
  {
    if ( empty($order_id) || empty($terminal_id) ) {
      return false;
    }
    
    $configs_meta = OmnivaLt_Core::get_configs('meta_keys');

    update_post_meta($order_id, $configs_meta['terminal_id'], $terminal_id);

    return true;
  }

  public static function get_omniva_terminal_id($order)
  {
    $configs_meta = OmnivaLt_Core::get_configs('meta_keys');

    return get_post_meta($order->get_id(), $configs_meta['terminal_id'], true);
  }

  public static function get_customer_shipping_country($order)
  {
    $country = $order->get_shipping_country();
    if ( empty($country) ) {
      $country = $order->get_billing_country();
    }

    return (!empty($country)) ? $country : 'LT';
  }

  public static function get_customer_name($order)
  {
    $name = $order->get_shipping_first_name();
    if ( empty($name) ) {
      $name = $order->get_billing_first_name();
    }

    return $name;
  }

  public static function get_customer_fullname($order)
  {
    $name = $order->get_shipping_first_name();
    $surname = $order->get_shipping_last_name();
    if ( empty($name) && empty($surname) ) {
      $name = $order->get_billing_first_name();
      $surname = $order->get_billing_last_name();
    }

    if ( ! empty($name) || ! empty($surname) ) {
      return trim($name . ' ' . $surname);
    }
    return '';
  }

  public static function get_customer_company($order)
  {
    $company = $order->get_shipping_company();
    if ( empty($company) ) {
      $company = $order->get_billing_company();
    }

    return (!empty($company)) ? $company : '';
  }

  public static function get_customer_fullname_or_company($order)
  {
    $full_name = self::get_customer_fullname($order);

    return (!empty($full_name)) ? $full_name : self::get_customer_company($order);
  }
}
