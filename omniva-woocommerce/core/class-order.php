<?php
class OmnivaLt_Order
{
  public static function load_admin_scripts( $hook )
  {
    if ( self::is_admin_order_edit_page() ) {
      wp_enqueue_style('omnivalt_admin_order', plugins_url('/assets/css/omniva_admin_order.css', OmnivaLt_Core::$main_file_path, array(), OMNIVALT_VERSION));
      wp_enqueue_script('omnivalt_admin_order', plugins_url( '/assets/js/omniva_admin_order.js', OmnivaLt_Core::$main_file_path ), array('jquery'), OMNIVALT_VERSION );
    }
  }

  public static function after_rate_description( $method, $index )
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

  public static function after_rate_terminals( $method )
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

    $method_key = OmnivaLt_Omniva_Order::get_method_key_from_id($method->id);
    $terminals_type = OmnivaLt_Configs::get_method_terminals_type($method_key);
    if ( $terminals_type && in_array($method->id, $selected_shipping_method) ) {
      echo OmnivaLt_Terminals::get_terminals_options($termnal_id, $country, $terminals_type);
    }
  }

  /**
   * Restrict Omniva Shipping methods if cart products has restricted categories
   */
  public static function restrict_shipping_methods_by_cats($rates)
  {
    global $woocommerce;
    $configs = OmnivaLt_Core::get_configs();
    $settings = get_option($configs['plugin']['settings_key']);
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
    $settings = get_option($configs['plugin']['settings_key']);
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

  public static function add_terminal_id_to_order( $order_id )
  {
    if ( empty($order_id) ) {
      OmnivaLt_Debug::log_error('Received empty Order ID when adding Order data');
      return;
    }
    
    if ( isset($_POST['omnivalt_terminal']) ) {
      $terminal_id = wc_clean($_POST['omnivalt_terminal']);
      OmnivaLt_Omniva_Order::set_terminal_id($order_id, $terminal_id);
      OmnivaLt_Wc_Order::add_note($order_id, '<b>Omniva:</b> ' . __('Customer choose parcel terminal', 'omnivalt') . ' - ' . OmnivaLt_Terminals::get_terminal_address($terminal_id,true) . ' <i>(ID: ' . $terminal_id . ')</i>');
    }

    if ( isset($_POST['shipping_method']) ) {
      OmnivaLt_Omniva_Order::set_method($order_id, $_POST['shipping_method']);
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

  public static function check_terminal_id_in_order( $wc_order )
  {
    try {
      $check_terminal_id = OmnivaLt_Omniva_Order::get_terminal_id($wc_order->get_id());

      if ( ! empty($_POST['shipping_method']) ) {
        $success = OmnivaLt_Omniva_Order::set_method($wc_order->get_id(), $_POST['shipping_method']);
        if ( $success && ! OmnivaLt_Omniva_Order::get_method($wc_order->get_id()) ) {
          OmnivaLt_Debug::log_error('Failed to save Omniva shipping method. ' . print_r($_POST,true));
        }
      }

      if ( ! empty($_POST['omnivalt_terminal']) && empty($check_terminal_id) ) {
        $terminal_id = wc_clean($_POST['omnivalt_terminal']);
        OmnivaLt_Omniva_Order::set_terminal_id($wc_order->get_id(), $_POST['omnivalt_terminal']);
        OmnivaLt_Wc_Order::add_note($wc_order->get_id(), '<b>Omniva:</b> ' . __('Parcel terminal save repeated', 'omnivalt') . ' - ' . OmnivaLt_Terminals::get_terminal_address($terminal_id,true) . ' <i>(ID: ' . $terminal_id . ')</i>');
      }
    } catch(\Exception $e) {
      OmnivaLt_Debug::log_error('Got error when trying add Omniva data to the Order: ' . $e->getMessage());
    }
  }

  public static function show_selected_terminal( $wc_order )
  {
    $order = OmnivaLt_Wc_Order::get_data($wc_order->get_id(), array('omniva'));
    $method_key = $order->omniva->method;

    if ( OmnivaLt_Configs::get_method_terminals_type($method_key) ) {
      $method_name = 'Omniva ' . strtolower(OmnivaLt_Configs::get_method_title($method_key));
      $terminal_name = OmnivaLt_Terminals::get_terminal_address($order->omniva->terminal_id);

      echo apply_filters('omnivalt_order_show_selected_terminal',
        '<p><b>' . $method_name . ':</b> ' . $terminal_name . '</p>',
        $method_name, $terminal_name
      );
    }
  }

  public static function show_tracking_link( $wc_order )
  {
    $order = OmnivaLt_Wc_Order::get_data($wc_order->get_id(), array('omniva', 'shipping', 'billing'));

    if ( ! empty($order->omniva->method) ) {
      $omnivalt_labels = new OmnivaLt_Labels();
      $omnivalt_labels->print_tracking_link($order, false, true);
    }
  }

  /**
   * Add custom order meta data to make it accessible in Order preview template
   */
  public static function admin_order_add_custom_meta_data( $data, $wc_order )
  {
    $configs = OmnivaLt_Core::get_configs();
    $order = OmnivaLt_Wc_Order::get_data($wc_order->get_id(), array('omniva'));

    foreach ( $configs['method_params'] as $method_key => $method_values ) {
      if ( ! $method_values['is_shipping_method'] ) continue;
      if ( $order->omniva->method != $method_values['key'] ) continue;

      if ( $method_values['key'] == 'pt' || $method_values['key'] == 'ps' ) {
        $data['shipping_via'] = 'Omniva ' . strtolower($method_values['title']) . ": " . OmnivaLt_Terminals::get_terminal_address($order->omniva->terminal_id);
      }
    }

    if ($order->omniva->method) {
      $shipping_settings = OmnivaLt_Core::get_settings();
      $omnivalt_labels = new OmnivaLt_Labels();

      $barcode = $order->omniva->barcodes[0];
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

  public static function bulk_actions( $bulk_actions )
  {
    global $wp_version;

    $plugin_info = OmnivaLt_Core::get_configs('plugin');
    $grouped = (version_compare($wp_version, '5.6.0', '>=')) ? true : false; 
    $actions = array(
      'labels' => __('Print labels', 'omnivalt'),
      'manifest' => __('Print manifest', 'omnivalt'),
    );

    foreach ( $actions as $action_key => $action_title ) {
      if ( $grouped ) {
        $bulk_actions[$plugin_info['title']][$plugin_info['id'] . '_' . $action_key] = $action_title;
      } else {
        $bulk_actions[$plugin_info['id'] . '_' . $action_key] = $plugin_info['title'] . ': ' . $action_title;
      }
    }

    return $bulk_actions;
  }

  public static function handle_bulk_actions( $redirect_to, $action, $ids )
  {
    $plugin_info = OmnivaLt_Core::get_configs('plugin');

    if ( $action == $plugin_info['id'] . '_labels' ) {
      $omnivalt_labels = new OmnivaLt_Labels();
      $omnivalt_labels->print_labels($ids);
      die();
    }

    if ( $action == $plugin_info['id'] . '_manifest' ) {
      $omnivalt_labels = new OmnivaLt_Labels();
      $omnivalt_labels->print_manifest($ids);
      die();
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
    $omnivalt_labels = new OmnivaLt_Labels();
    $omnivalt_labels->print_manifest($_REQUEST['post']);
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
  public static function add_cart_weight( $wc_order_id )
  {
    global $woocommerce;
    $weight = $woocommerce->cart->cart_contents_weight;
    OmnivaLt_Wc_Order::update_meta($wc_order_id, '_cart_weight', $weight);
  }

  public static function print_tracking_url_action( $barcode, $country_code = 'LT' )
  {
    $omnivalt_labels = new OmnivaLt_Labels();
    echo $omnivalt_labels->get_tracking_link($country_code, $barcode);
  }

  public static function is_admin_order_edit_page()
  {
    if ( ! is_admin() ) {
      return false;
    }

    $available_screens = OmnivaLt_Wc::get_page_recognition_ids('admin_order_edit');
    $screen_id = OmnivaLt_Wc::get_current_screen_id();

    return (in_array($screen_id, $available_screens));
  }

  /**
   * Display and edit Omniva info on the order edit page
   */
  public static function admin_order_display( $wc_order_id, $print_barcode = true, $admin_panel = true )
  {
    if ( is_object($wc_order_id) ) {
      $wc_order_id = $wc_order_id->get_id();
    }
    $configs = OmnivaLt_Core::get_configs();
    $order = OmnivaLt_Wc_Order::get_data($wc_order_id);
    $send_method = $order->omniva->method;
    $omnivalt_labels = new OmnivaLt_Labels();

    $is_omniva = false;
    foreach ( $configs['method_params'] as $ship_method => $ship_values ) {
      if ( ! $ship_values['is_shipping_method'] ) continue;
      if ( $send_method == $ship_values['key'] ) {
        $is_omniva = true;
      }
    }
    
    if ( $send_method !== false && ! $is_omniva ) {
      self::add_Omniva_manually();
    }
    if ( ! $is_omniva ) return;

    if ( self::is_admin_order_edit_page($order->id) ) {
      echo '<br class="clear"/>';
      echo '<hr style="margin-top:20px;">';
      echo '<h4>' . __('Omniva shipping', 'omnivalt') . '</h4>';
    }
    
    echo '<div class="address">';
    foreach ( $configs['method_params'] as $ship_method => $ship_values ) {
      if ( ! $ship_values['is_shipping_method'] ) continue;
      if ( $send_method != $ship_values['key'] ) continue;

      $field_value = $order->shipment->formated_shipping_address;
      if ( $ship_values['key'] == 'pt' || $ship_values['key'] == 'ps' ) {
        $field_value = OmnivaLt_Terminals::get_terminal_address($order->omniva->terminal_id);
      }
      
      echo '<p><strong class="title">' . $ship_values['title'] . ':</strong> ' . $field_value . '</p>';
    }

    if ( self::is_admin_order_edit_page($order->id) ) {
      echo self::build_shipment_size_text($order->shipment->size);
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
    
    if ( ! self::is_admin_order_edit_page($order->id) ) {
      return;
    }

    echo '<div class="edit_address">';
    if ( $send_method == 'pt' || $send_method == 'ps' ) {
      $values = array(
        'terminal_key' => OmnivaLt_Configs::get_method_terminals_type($send_method),
        'change_title' => sprintf(__('Change %s', 'omnivalt'), strtolower(OmnivaLt_Configs::get_method_title($send_method))),
      );

      $all_terminals = OmnivaLt_Terminals::get_terminals_list('ALL', $values['terminal_key']);
      $selected_terminal = $order->omniva->terminal_id;
     
      echo '<p class="form-field-wide">';
      echo '<label for="omnivalt_terminal">' . $values['change_title'] . '</label>';
      echo '<input type="hidden" id="omniva-order-country" value="' . $order->shipping->country . '">';
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

    echo self::build_shipment_size_fields($order->shipment->size);
    
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

  public static function get_dimmension_text( $current_values )
  {
    $output = '';

    $dimm_values = array(
      'length' => $current_values['length'] ?? 0,
      'width' => $current_values['width'] ?? 0,
      'height' => $current_values['height'] ?? 0,
    );
    $dimm_unit = get_option('woocommerce_dimension_unit');

    $first = true;
    foreach ( $dimm_values as $value ) {
      if ( ! $first ) {
        $output .= ' × ';
      }
      $output .= $value;
      $first = false;
    }
    $output .= ' ' . $dimm_unit;

    return $output;
  }

  public static function get_weight_text( $current_values )
  {
    $weight_value = $current_values['weight'] ?? 0;
    $weight_unit = get_option('woocommerce_weight_unit');

    return ($weight_value > 0) ? wc_format_weight($weight_value) : '0 ' . $weight_unit;
  }

  public static function get_price_text( $value )
  {
    return wc_price($value);
  }

  private static function build_shipment_size_text( $current_values )
  {
    $output = '<p>';
    
    $output .= '<strong class="title">' . __('Size', 'omnivalt') . ':</strong>';
    $output .= self::get_dimmension_text($current_values);
    $output .= ' ';
    $output .= self::get_weight_text($current_values);

    $output .= '</p>';

    return $output;
  }

  private static function build_shipment_size_fields( $current_values )
  {
    $output = '';

    $fields = array(
      'length' => __('Length', 'omnivalt'),
      'width' => __('Width', 'omnivalt'),
      'height' => __('Height', 'omnivalt'),
    );
    $field_id_prefix = 'omnivalt_dimmensions';
    $dimm_unit = get_option('woocommerce_dimension_unit');

    $output .= '<p class="form-field-wide omnivalt-dimmension">';
    $output .= '<label>' . __('Shipment size', 'omnivalt') . '</label>';
    
    $first = true;
    foreach ( $fields as $field_key => $title ) {
      if ( ! $first ) {
        $output .= ' × ';
      }
      $field_id = $field_id_prefix . '_' . $field_key;
      $field_name = $field_id_prefix . '[' . $field_key . ']';
      $field_value = $current_values[$field_key] ?? '';
      $output .= '<input type="number" class="short inline-number" name="' . $field_name . '" id="' . $field_id . '" value="' . $field_value . '" min="0" step="0.001" placeholder="' . $title . '">';
      $first = false;
    }
    $output .= ' ' . $dimm_unit;
    
    $output .= '</p>';

    return $output;
  }

  private static function add_Omniva_manually()
  {
    $configs = OmnivaLt_Core::get_configs();

    echo '<div class="edit_address">';
    $field_id = 'omnivalt_add_manual';
    echo '<p class="form-field-wide">';
    echo '<label for="' . $field_id . '">' . __('Omniva shipping method', 'omnivalt') . ':</label>';
    echo '<select id="' . $field_id . '" class="select short" name="' . $field_id . '">';
    echo '<option>' . __('Not Omniva', 'omnivalt') . '</option>';
    foreach ( $configs['method_params'] as $method_key => $method_values ) {
      if ( ! $method_values['is_shipping_method'] ) continue;
      echo '<option value="' . $method_values['key'] . '">' . $method_values['title'] . '</option>';
    }
    echo '</select>';
    echo '</p>';
    echo '</div>';
  }

  public static function admin_order_save( $post_id ) //Save for Woocommerce 7 or older
  {
    if ( ! self::is_admin_order_edit_page($post_id) ) {
      return $post_id;
    }

    $configs = OmnivaLt_Core::get_configs();

    if ( isset($_POST['omnivalt_terminal_id']) ) {
      $terminal_id = wc_clean($_POST['omnivalt_terminal_id']);
      OmnivaLt_Omniva_Order::set_terminal_id($post_id, $terminal_id);
      OmnivaLt_Wc_Order::add_note($post_id, '<b>Omniva:</b> ' . __('Admin changed parcel terminal', 'omnivalt') . ' - ' . OmnivaLt_Terminals::get_terminal_address($terminal_id,true) . ' <i>(ID: ' . $terminal_id . ')</i>');
    }

    if ( isset($_POST['omnivalt_dimmensions']) ) {
      OmnivaLt_Omniva_Order::set_dimmensions($post_id, wc_clean(json_encode($_POST['omnivalt_dimmensions'])));
    }

    foreach ( $configs['additional_services'] as $service_key => $service_values ) {
      if ( isset($_POST['omnivalt_' . $service_key]) ) {
        OmnivaLt_Wc_Order::update_meta($post_id, '_omnivalt_' . $service_key, wc_clean($_POST['omnivalt_' . $service_key]));
      }
    }

    if ( isset($_POST['omnivalt_add_manual']) ) {
      $method = array('omnivalt_' . $_POST['omnivalt_add_manual']);
      OmnivaLt_Omniva_Order::set_method($post_id, $method);
    }

    return $post_id;
  }

  public static function admin_order_save_hpos( $post_id ) //Save for Woocommerce 8 or newer
  {
    if ( ! self::is_admin_order_edit_page($post_id) ) {
      return $post_id;
    }

    remove_action('woocommerce_update_order', 'OmnivaLt_Order::admin_order_save_hpos'); //Temporary fix to avoid infinity loop

    $configs = OmnivaLt_Core::get_configs();

    if ( isset($_POST['omnivalt_terminal_id']) ) {
      $terminal_id = wc_clean($_POST['omnivalt_terminal_id']);
      $old_terminal_id = OmnivaLt_Omniva_Order::get_terminal_id($post_id);
      if ( $terminal_id != $old_terminal_id ) {
        OmnivaLt_Omniva_Order::set_terminal_id($post_id, $terminal_id);
        OmnivaLt_Wc_Order::add_note($post_id, '<b>Omniva:</b> ' . __('Admin changed parcel terminal', 'omnivalt') . ' - ' . OmnivaLt_Terminals::get_terminal_address($terminal_id,true) . ' <i>(ID: ' . $terminal_id . ')</i>');
      }
    }

    if ( isset($_POST['omnivalt_dimmensions']) ) {
      OmnivaLt_Omniva_Order::set_dimmensions($post_id, wc_clean(json_encode($_POST['omnivalt_dimmensions'])));
    }

    foreach ( $configs['additional_services'] as $service_key => $service_values ) {
      if ( isset($_POST['omnivalt_' . $service_key]) ) {
        OmnivaLt_Wc_Order::update_meta($post_id, '_omnivalt_' . $service_key, wc_clean($_POST['omnivalt_' . $service_key]));
      }
    }

    if ( isset($_POST['omnivalt_add_manual']) ) {
      $method = array('omnivalt_' . $_POST['omnivalt_add_manual']);
      OmnivaLt_Omniva_Order::set_method($post_id, $method);
    }

    add_action('woocommerce_update_order', 'OmnivaLt_Order::admin_order_save_hpos'); //Restore hook

    return $post_id;
  }

  public static function checkout_validate_terminal()
  {
    $messages = array(
      'pt' => __('Please select parcel terminal.', 'omnivalt'),
      'ps' => __('Please select post office.', 'omnivalt'),
    );

    foreach ( $messages as $key => $message ) {
      if ( isset($_POST['shipping_method']) && in_array('omnivalt_' . $key, $_POST['shipping_method']) ) {
        if ( empty($_POST['omnivalt_terminal']) ) {
          wc_add_notice($message, 'error');
        }
      }
    }
  }

  public static function get_customer_shipping_country( $order )
  {
    $country = $order->shipping->country;
    if ( empty($country) ) {
      $country = $order->billing->country;
    }

    return (!empty($country)) ? $country : 'LT';
  }

  public static function get_customer_name( $order )
  {
    $name = $order->shipping->name;
    if ( empty($name) ) {
      $name = $order->billing->name;
    }

    return $name;
  }

  public static function get_customer_fullname( $order )
  {
    $name = $order->shipping->name;
    $surname = $order->shipping->surname;
    if ( empty($name) && empty($surname) ) {
      $name = $order->billing->name;
      $surname = $order->billing->surname;
    }

    if ( ! empty($name) || ! empty($surname) ) {
      return trim($name . ' ' . $surname);
    }
    return '';
  }

  public static function get_customer_company( $order )
  {
    $company = $order->shipping->company;
    if ( empty($company) ) {
      $company = $order->billing->company;
    }

    return (!empty($company)) ? $company : '';
  }

  public static function get_customer_fullname_or_company( $order )
  {
    $full_name = self::get_customer_fullname($order);

    return (!empty($full_name)) ? $full_name : self::get_customer_company($order);
  }

  public static function get_customer_full_address( $order )
  {
    $street = $order->shipping->address_1;
    $city = $order->shipping->city;
    $postcode = $order->shipping->postcode;
    $country = $order->shipping->country;

    if ( empty($street) && empty($city) ) {
      $street = $order->billing->address_1;
      $city = $order->billing->city;
      $postcode = $order->billing->postcode;
      $country = $order->billing->country;
    }

    if ( empty($country) ) {
      $shipping_settings = OmnivaLt_Core::get_settings();
      $country = $shipping_settings['shop_countrycode'];
    }

    $output = (!empty($street)) ? $street : '—';
    $output .= ', ';
    $output .= (!empty($city)) ? $city : '—';
    $output .= (!empty($postcode)) ? ' ' . $postcode : '';
    $output .= (!empty($country)) ? ', ' . strtoupper($country) : '';

    return $output;
  }

  public static function spread_items( $items_data )
  {
    $spreaded_items = array();

    foreach ( $items_data as $item_data ) {
      for( $i = 0; $i < $item_data['quantity']; $i++ ) {
        $item = array();
        foreach ( $item_data as $key => $value ) {
          if ( $key == 'quantity' ) {
            continue;
          }
          $item[$key] = $value;
        }
        $spreaded_items[] = $item;
      }
    }

    return $spreaded_items;
  }

  public static function count_order_weight( $items_data )
  {
    $order_weight = 0;

    foreach ( $items_data as $item ) {
        $order_weight += $item['quantity'] * $item['weight'];
    }

    return $order_weight;
  }

  public static function count_order_dimmension( $items_data ) //TODO: Maybe this not need when OmnivaLt_Calc_Size will completed
  {
    $order_dimmension = array(
      'length' => 0,
      'width' => 0,
      'height' => 0,
    );

    $predicted_size = OmnivaLt_Helper::predict_order_size(self::spread_items($items_data), array(
      'length' => 1000,
      'width' => 1000,
      'height' => 1000
    ));
    foreach ( $order_dimmension as $dim_key => $dim_value ) {
      $order_dimmension[$dim_key] = $predicted_size[$dim_key] ?? 0;
    }

    return $order_dimmension;
  }

  public static function get_order_items_size( $items_data, $saved_order_size = false )
  {
    $order_size = array(
        'length' => 0,
        'width' => 0,
        'height' => 0,
      );

    $get_from_saved = false;
    if ( ! empty($saved_order_size) ) {
      foreach ( $order_size as $size_key => $size_value ) {
        if ( isset($saved_order_size[$size_key]) && $saved_order_size[$size_key] !== '' ) {
          $get_from_saved = true;
          break;
        }
      }
    }

    if ( $get_from_saved ) {
      foreach ( $order_size as $size_key => $size_value ) {
        if ( isset($saved_order_size[$size_key]) && $saved_order_size[$size_key] !== '' ) {
          $order_size[$size_key] = $saved_order_size[$size_key];
        }
      }
    } else {
      $order_size = self::count_order_dimmension($items_data);
    }

    return $order_size;
  }
}
