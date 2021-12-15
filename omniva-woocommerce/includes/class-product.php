<?php
class OmnivaLt_Product
{
  public static function init()
  {
    add_filter('woocommerce_product_data_tabs', array(__CLASS__, 'add_product_tabs'));
    add_filter('woocommerce_product_data_panels', array(__CLASS__, 'options_content'));
    
    add_action('admin_head', array(__CLASS__, 'tabs_styles'));
    add_action('woocommerce_process_product_meta_simple', array(__CLASS__, 'save_options_fields'));
    add_action('woocommerce_process_product_meta_variable', array(__CLASS__, 'save_options_fields'));
  }

  /**
   * Add a custom product tab
   *
   * @param array $tabs - Product current tabs
   */
  public static function add_product_tabs($tabs)
  {
    $tabs['omnivalt'] = array(
      'label'   => __('Omniva options', 'omnivalt'),
      'target'  => 'omnivalt_options',
      'class'   => array('show_if_simple', 'show_if_variable'),
    );

    return $tabs;
  }

  /**
   * Styles
   */
  public static function tabs_styles()
  {
    ?>
    <style>
      #woocommerce-product-data ul.wc-tabs li.omnivalt_options a:before {
        font-family: WooCommerce;
        content: '\e006';
      }
    </style>
    <?php
  }

  /**
   * Content of options tab
   */
  public static function options_content()
  {
    global $post;
    $configs_services = OmnivaLt_Core::get_configs('additional_services');
    ?>
    <div id='omnivalt_options' class='panel woocommerce_options_panel'>
      <div class='options_group'>
        <?php
        foreach ($configs_services as $service_key => $service_values) {
          if (!$service_values['in_product']) continue;

          $field_params = array(
            'id' => '_omnivalt_' . $service_key,
            'label' => $service_values['title'],
          );
          if (!empty($service_values['desc_product'])) {
            $field_params['desc_tip'] = 'true';
            $field_params['description'] = $service_values['desc_product'];
          }

          if ($service_values['in_product'] === 'checkbox') {
            woocommerce_wp_checkbox($field_params);
          }
          if ($service_values['in_product'] === 'text') {
            $field_params['type'] = 'text';
            woocommerce_wp_text_input($field_params);
          }
          if ($service_values['in_product'] === 'number') {
            $field_params['type'] = 'number';
            $field_params['custom_attributes'] = array(
              'min' => '',
              'max' => '',
              'step'  => '1',
            );
            woocommerce_wp_text_input($field_params);
          }
        }
        ?>
      </div>
    </div>
    <?php
  }

  /**
   * Save the custom fields
   *
   * @param integer $post_id - Post ID
   */
  public static function save_options_fields($post_id)
  {
    $configs_services = OmnivaLt_Core::get_configs('additional_services');

    foreach ($configs_services as $service_key => $service_values) {
      if (!$service_values['in_product']) continue;
      
      $value = '';
      if ($service_values['in_product'] === 'checkbox') {
        $value = isset($_POST['_omnivalt_' . $service_key]) ? 'yes' : 'no';
      }
      if ($service_values['in_product'] === 'text') {
        $value = isset($_POST['_omnivalt_' . $service_key]) ? $_POST['_omnivalt_' . $service_key] : '';
      }
      if ($service_values['in_product'] === 'number') {
        $value = isset($_POST['_omnivalt_' . $service_key]) ? absint($_POST['_omnivalt_' . $service_key]) : '';
      }

      update_post_meta($post_id, '_omnivalt_' . $service_key, $value);
    }
  }

  /**
   * Get order items services
   *
   * @param object $order - WC order
   * @param boolean $merged - If true, then function will return merged all products services to one level array
   */
  public static function get_order_items_services($order, $merged = false)
  {
    $configs_services = OmnivaLt_Core::get_configs('additional_services');
    $services = array();
    
    foreach ($order->get_items() as $item_id => $item) {
      foreach ($configs_services as $service_key => $service_values) {
        if (!$service_values['in_product']) continue;

        $meta_value = get_post_meta($item['product_id'], '_omnivalt_' . $service_key, true);
        $add_service = false;

        if ($service_values['in_product'] === 'checkbox' && $meta_value === 'yes') {
          $add_service = $service_key;
        }

        if ($add_service) {
          if (!$merged) {
            $services[$item_id][] = $add_service;
          } elseif (!in_array($service_key, $services)) {
            $services[] = $add_service;
          }
        }
      }
    }

    return $services;
  }
}
