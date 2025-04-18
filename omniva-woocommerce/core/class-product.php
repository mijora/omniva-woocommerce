<?php
class OmnivaLt_Product
{
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
        woocommerce_wp_text_input(array(
          'id' => '_omnivalt_total_shipments',
          'label' => __('Number of parcels (MPS)', 'omnivalt'),
          'desc_tip' => 'true',
          'description' => __('Specify how many separate shipments will be required for 1 quantity of this product. If a value of 1 or more is specified, when generating labels for the Order, as many labels as specified here will be additionally generated. If specify a value of 0, no additional shipping labels will be generated.', 'omnivalt'),
          'type' => 'number',
          'custom_attributes' => array(
            'min' => '0',
            'max' => '',
            'step' => '1',
          ),
          'default' => '0'
        ));
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
    $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
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

      OmnivaLt_Wc_Product::update_meta($post_id, '_omnivalt_' . $service_key, $value);
    }

    if ( isset($_POST['_omnivalt_total_shipments']) ) {
      OmnivaLt_Wc_Product::update_meta($post_id, $meta_keys['total_shipments'], absint($_POST['_omnivalt_total_shipments']));
    }
  }

  /**
   * Get order items services
   *
   * @param object $order - WC order
   * @param boolean $merged - If true, then function will return merged all products services to one level array
   */
  public static function get_order_items_services($order_items, $merged = false)
  {
    $configs_services = OmnivaLt_Core::get_configs('additional_services');
    $services = array();
    
    foreach ($order_items as $item_id => $item) {
      foreach ($configs_services as $service_key => $service_values) {
        if (!$service_values['in_product']) continue;

        $meta_value = OmnivaLt_Helper::get_value_from_array($item['product_meta_data'], '_omnivalt_' . $service_key, '');
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
