<?php
class OmnivaLt_Settings_Page
{
  const PAGE_SLUG = 'omnivalt-settings';

  public static function get_page_url( $args = array() )
  {
    return add_query_arg(
      array_merge(
        array(
          'page' => self::PAGE_SLUG,
        ),
        $args
      ),
      admin_url('admin.php')
    );
  }

  public static function remove_legacy_settings_section( $sections )
  {
    unset($sections['omnivalt']);

    return $sections;
  }

  public static function redirect_legacy_settings_page()
  {
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
    if ( 'GET' !== strtoupper($request_method) ) {
      return;
    }

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- These query parameters only identify a read-only admin screen.
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
    $section = isset($_GET['section']) ? sanitize_key(wp_unslash($_GET['section'])) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    if ( 'wc-settings' !== $page || 'shipping' !== $tab || 'omnivalt' !== $section ) {
      return;
    }

    wp_safe_redirect(self::get_page_url());
    exit;
  }

  public static function register_menu_page()
  {
    add_submenu_page(
      'woocommerce',
      __('Omniva settings', 'omnivalt'),
      __('Omniva settings', 'omnivalt'),
      'manage_woocommerce',
      self::PAGE_SLUG,
      array('OmnivaLt_Settings_Page', 'render_page'),
      11
    );
  }

  public static function save_settings()
  {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ( self::PAGE_SLUG !== $page || ! isset($_POST['save']) ) {
      return;
    }

    if ( ! current_user_can('manage_woocommerce') ) {
      wp_die(esc_html__('You do not have permission to manage Omniva settings.', 'omnivalt'));
    }

    check_admin_referer('woocommerce-settings');

    $shipping_method = self::get_shipping_method();
    $shipping_method->init_form_fields();
    $shipping_method->process_admin_options();
    WC_Cache_Helper::get_transient_version('shipping', true);

    WC_Admin_Settings::add_message(__('Your settings have been saved.', 'omnivalt'));
  }

  public static function render_page()
  {
    if ( ! current_user_can('manage_woocommerce') ) {
      return;
    }

    $shipping_method = self::get_shipping_method();
    $shipping_method->init_form_fields();
    $page_data = self::prepare_page_data($shipping_method);

    include OMNIVALT_DIR . 'templates/admin/settings-page.php';
  }

  public static function get_settings_layout()
  {
    return array(
      'tabs' => array(
        'general' => __('Setup', 'omnivalt'),
        'rules' => __('Delivery & checkout', 'omnivalt'),
        'workflow' => __('Order fulfilment', 'omnivalt'),
        'advanced' => __('Diagnostics', 'omnivalt'),
      ),
      'cards' => array(
        'general' => array(
          'type' => 'settings',
          'section' => 'general',
          'tab' => 'general',
        ),
        'api' => array(
          'type' => 'settings',
          'section' => 'api',
          'tab' => 'general',
        ),
        'shop' => array(
          'type' => 'settings',
          'section' => 'shop',
          'tab' => 'general',
          'fields' => array(
            'company',
            'shop_name',
            'shop_address',
            'shop_city',
            'shop_postcode',
            'shop_countrycode',
            'shop_phone',
            'shop_mobile',
            'shop_email',
            'bank_account',
            'pick_up_start',
            'pick_up_end',
            'send_off',
          ),
        ),
        'shipping_methods' => array(
          'type' => 'shipping_methods',
          'section' => 'methods',
          'prices_section' => 'prices',
          'tab' => 'rules',
        ),
        'settings' => array(
          'type' => 'settings',
          'section' => 'settings',
          'tab' => 'rules',
        ),
        'design' => array(
          'type' => 'settings',
          'section' => 'design',
          'tab' => 'advanced',
        ),
        'orders' => array(
          'type' => 'settings',
          'section' => 'orders',
          'tab' => 'workflow',
        ),
        'labels' => array(
          'type' => 'settings',
          'section' => 'labels',
          'tab' => 'workflow',
        ),
        'manifest' => array(
          'type' => 'settings',
          'section' => 'manifest',
          'tab' => 'workflow',
        ),
        'pickup' => array(
          'type' => 'settings',
          'section' => 'pickup',
          'tab' => 'workflow',
        ),
        'debug' => array(
          'type' => 'settings',
          'section' => 'debug',
          'tab' => 'advanced',
        ),
      ),
    );
  }

  public static function get_shipping_method_layout( $shipping_method )
  {
    $methods = array();

    foreach ( OmnivaLt_Method::get_all_shipping_methods() as $method ) {
      $field_key = 'method_' . $method['key'];
      if ( ! isset($shipping_method->form_fields[$field_key]) ) {
        continue;
      }

      $methods[] = array(
        'key' => $method['key'],
        'field_key' => $field_key,
        'title' => $method['title'],
        'description' => $method['description'],
      );
    }

    return $methods;
  }

  private static function prepare_page_data( $shipping_method )
  {
    $settings_layout = self::get_settings_layout();
    $settings_sections = self::get_settings_sections($shipping_method->form_fields);
    $shipping_method_layout = self::get_shipping_method_layout($shipping_method);
    $section_icons = self::get_section_icons();
    $rendered_sections = array();
    $rendered_fields = array();
    $cards = array();

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The query parameter only selects a read-only admin tab.
    $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'general';
    if ( ! isset($settings_layout['tabs'][$active_tab]) ) {
      $active_tab = 'general';
    }

    foreach ( $settings_layout['cards'] as $card_key => $card ) {
      $section_key = $card['section'];
      $has_field_list = isset($card['fields']) && is_array($card['fields']) && ! empty($card['fields']);
      if ( isset($rendered_sections[$section_key]) && ! $has_field_list ) {
        continue;
      }

      $section = self::resolve_card_section($card, $section_key, $settings_sections);
      foreach ( $section['_sources'] as $source ) {
        $rendered_fields[$source['section']][$source['key']] = true;
      }
      unset($section['_sources']);

      if ( ! $has_field_list ) {
        $rendered_sections[$section_key] = true;
      }

      if ( 'shipping_methods' === $card['type'] ) {
        $prices_section_key = $card['prices_section'];
        $prices_section = isset($settings_sections[$prices_section_key]) ? $settings_sections[$prices_section_key] : array();
        $rendered_sections[$prices_section_key] = true;
        $cards[] = self::prepare_shipping_methods_card(
          $shipping_method,
          $shipping_method_layout,
          $card_key,
          $card,
          $section_key,
          $section,
          $prices_section,
          $section_icons
        );
        continue;
      }

      $settings_card = self::prepare_settings_card($shipping_method, $card_key, $card['tab'], $section_key, $section, $section_icons);
      if ( $settings_card ) {
        $cards[] = $settings_card;
      }
    }

    foreach ( $settings_sections as $section_key => $section ) {
      if ( isset($rendered_sections[$section_key]) ) {
        continue;
      }

      $remaining_fields = array();
      foreach ( $section['fields'] as $field_key => $field ) {
        if ( empty($rendered_fields[$section_key][$field_key]) ) {
          $remaining_fields[$field_key] = $field;
        }
      }
      if ( empty($remaining_fields) ) {
        continue;
      }

      $section['fields'] = $remaining_fields;
      $settings_card = self::prepare_settings_card($shipping_method, $section_key, '', $section_key, $section, $section_icons);
      if ( $settings_card ) {
        $cards[] = $settings_card;
      }
    }

    return array(
      'tabs' => $settings_layout['tabs'],
      'active_tab' => $active_tab,
      'form_action' => self::get_page_url(
        array(
          'tab' => $active_tab,
        )
      ),
      'cards' => $cards,
    );
  }

  private static function get_settings_sections( $form_fields )
  {
    $settings_sections = array(
      'general' => array(
        'title' => __('General', 'omnivalt'),
        'description' => __('Core Omniva shipping configuration.', 'omnivalt'),
        'fields' => array(),
      ),
    );
    $current_section = 'general';

    foreach ( $form_fields as $field_key => $field ) {
      if ( isset($field['type']) && 'hr' === $field['type'] ) {
        if ( empty($field['title']) ) {
          continue;
        }

        $current_section = str_replace('hr_', '', $field_key);
        $settings_sections[$current_section] = array(
          'title' => $field['title'],
          'description' => '',
          'fields' => array(),
        );
        continue;
      }

      $settings_sections[$current_section]['fields'][$field_key] = $field;
    }

    return $settings_sections;
  }

  private static function resolve_card_section( $card, $section_key, $settings_sections )
  {
    $section = isset($settings_sections[$section_key]) ? $settings_sections[$section_key] : array(
      'title' => '',
      'description' => '',
      'fields' => array(),
    );
    $section['_sources'] = array();

    if ( empty($card['fields']) ) {
      foreach ( $section['fields'] as $field_key => $field ) {
        $section['_sources'][] = array(
          'section' => $section_key,
          'key' => $field_key,
        );
      }

      return $section;
    }

    $section['fields'] = array();
    foreach ( $card['fields'] as $field_reference ) {
      $source_section_key = $section_key;
      $field_key = $field_reference;

      if ( is_array($field_reference) ) {
        $source_section_key = isset($field_reference['section']) ? $field_reference['section'] : $section_key;
        $field_key = isset($field_reference['key']) ? $field_reference['key'] : '';
      }

      if ( ! $field_key ) {
        continue;
      }

      if ( isset($settings_sections[$source_section_key]['fields'][$field_key]) ) {
        $section['fields'][$field_key] = $settings_sections[$source_section_key]['fields'][$field_key];
        $section['_sources'][] = array(
          'section' => $source_section_key,
          'key' => $field_key,
        );
        continue;
      }

      foreach ( $settings_sections as $candidate_section_key => $candidate_section ) {
        if ( ! isset($candidate_section['fields'][$field_key]) ) {
          continue;
        }

        $section['fields'][$field_key] = $candidate_section['fields'][$field_key];
        $section['_sources'][] = array(
          'section' => $candidate_section_key,
          'key' => $field_key,
        );
        break;
      }
    }

    return $section;
  }

  private static function prepare_settings_card( $shipping_method, $card_key, $tab, $section_key, $section, $section_icons )
  {
    if ( empty($section['fields']) ) {
      return false;
    }

    $rows_html = self::prepare_settings_rows($shipping_method->generate_settings_html($section['fields'], false));
    $rows_html = str_replace('class="description"', 'class="description omniva-field__desc"', $rows_html);

    return array(
      'type' => 'settings',
      'key' => $card_key,
      'tab' => $tab,
      'section_key' => $section_key,
      'title' => $section['title'],
      'description' => $section['description'],
      'icon' => isset($section_icons[$section_key]) ? $section_icons[$section_key] : 'admin-generic',
      'rows_html' => $rows_html,
    );
  }

  private static function prepare_shipping_methods_card( $shipping_method, $shipping_method_layout, $card_key, $card, $section_key, $section, $prices_section, $section_icons )
  {
    $methods = array();
    foreach ( $shipping_method_layout as $method ) {
      if ( ! isset($section['fields'][$method['field_key']]) ) {
        continue;
      }

      $method_field = array(
        $method['field_key'] => $section['fields'][$method['field_key']],
      );
      $method['rows_html'] = self::prepare_settings_rows($shipping_method->generate_settings_html($method_field, false));
      $method['is_international'] = false;
      $method['destinations'] = array();
      $methods[$method['key']] = $method;
    }

    $methods['international'] = array(
      'key' => 'international',
      'title' => __('International services', 'omnivalt'),
      'description' => __('Configure international service package and region settings.', 'omnivalt'),
      'rows_html' => '',
      'is_international' => true,
      'destinations' => array(),
    );

    $methods = self::prepare_shipping_price_destinations($shipping_method, $prices_section, $methods);

    $returns_html = '';
    if ( isset($section['fields']['txt_returns']) ) {
      $returns_html = self::prepare_settings_rows(
        $shipping_method->generate_settings_html(
          array(
            'txt_returns' => $section['fields']['txt_returns'],
          ),
          false
        )
      );
    }

    return array(
      'type' => 'shipping_methods',
      'key' => $card_key,
      'tab' => $card['tab'],
      'section_key' => $section_key,
      'title' => $section['title'],
      'icon' => isset($section_icons[$section_key]) ? $section_icons[$section_key] : 'admin-generic',
      'methods' => array_values($methods),
      'returns_html' => $returns_html,
    );
  }

  private static function prepare_shipping_price_destinations( $shipping_method, $prices_section, $methods )
  {
    if ( empty($prices_section['fields']) ) {
      return $methods;
    }

    foreach ( $prices_section['fields'] as $field_key => $field ) {
      if ( empty($field['type']) || 'prices_box' !== $field['type'] ) {
        continue;
      }

      $destination = $shipping_method->get_prices_box_data($field_key, $field);
      if ( empty($destination['blocks']) ) {
        continue;
      }

      foreach ( $destination['blocks'] as $block ) {
        $group_key = $block['group_key'];
        if ( ! isset($methods[$group_key]) ) {
          continue;
        }

        $destination_key = $destination['type'] . '_' . $destination['key'];
        if ( ! isset($methods[$group_key]['destinations'][$destination_key]) ) {
          $destination_id = 'omniva-delivery-' . sanitize_html_class($group_key) . '-' . sanitize_html_class($destination_key);
          $methods[$group_key]['destinations'][$destination_key] = array(
            'type' => $destination['type'],
            'key' => $destination['key'],
            'title' => $destination['title'],
            'image_url' => $destination['image_url'],
            'id' => $destination_id,
            'tab_id' => $destination_id . '-tab',
            'blocks' => array(),
          );
        }

        unset($block['group_key']);
        $methods[$group_key]['destinations'][$destination_key]['blocks'][] = $block;
      }
    }

    foreach ( $methods as $method_key => $method ) {
      $methods[$method_key]['destinations'] = array_values($method['destinations']);
    }

    return $methods;
  }

  private static function prepare_settings_rows( $rows )
  {
    return preg_replace_callback(
      '/<tr(\s[^>]*)?>/',
      function( $matches ) {
        $attributes = isset($matches[1]) ? $matches[1] : '';

        if ( strpos($attributes, 'class=') !== false ) {
          $attributes = preg_replace('/class=(["\'])([^"\']*)\1/', 'class=$1$2 omniva-field$1', $attributes, 1);
        } else {
          $attributes .= ' class="omniva-field"';
        }

        return '<tr' . $attributes . '>';
      },
      $rows
    );
  }

  private static function get_section_icons()
  {
    return array(
      'general' => 'admin-generic',
      'api' => 'admin-links',
      'shop' => 'store',
      'methods' => 'admin-site-alt3',
      'prices' => 'money-alt',
      'settings' => 'admin-settings',
      'design' => 'art',
      'orders' => 'cart',
      'labels' => 'media-document',
      'manifest' => 'media-spreadsheet',
      'pickup' => 'location-alt',
      'debug' => 'admin-tools',
    );
  }

  private static function get_shipping_method()
  {
    if ( ! class_exists('Omnivalt_Shipping_Method') ) {
      OmnivaLt_Core::init_shipping_method();
    }

    return new Omnivalt_Shipping_Method();
  }
}
