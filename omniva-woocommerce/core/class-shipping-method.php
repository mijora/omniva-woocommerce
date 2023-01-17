<?php
if ( ! class_exists('Omnivalt_Shipping_Method') ) {
  class Omnivalt_Shipping_Method extends WC_Shipping_Method
  {
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public $errors = array();

    private $omnivalt_api;
    private $omnivalt_configs;
    private $shipping_sets;
    private $methods_asociations;
    private $destinations_countries = array();

    public function __construct()
    {
      $this->id = 'omnivalt';
      $this->method_title = __('Omniva shipping', 'omnivalt');
      $this->method_description = __('Shipping methods for Omniva', 'omnivalt');

      $this->omnivalt_api = new OmnivaLt_Api();
      $this->omnivalt_configs = OmnivaLt_Core::get_configs();
      $this->methods_asociations = OmnivaLt_Helper::get_methods_asociations();

      // Destination countries
      foreach ( $this->omnivalt_configs['shipping_params'] as $ship_params ) {
        foreach ( $ship_params['shipping_sets'] as $country => $set ) {
          if ( $country === 'call' ) continue;
          if ( ! isset($this->destinations_countries[$country]) ) {
            $country_name = $country;
            if ( isset($this->omnivalt_configs['shipping_params'][$country]['title']) ) {
              $country_name = $this->omnivalt_configs['shipping_params'][$country]['title'];
            }
            $this->destinations_countries[$country] = $country_name;
          }
        }
      }

      // Availability, Countries and other required Woocommerce functions
      $this->availability = 'including';
      $this->countries = array_keys($this->destinations_countries);

      $this->init();

      $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
      $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Omniva shipping', 'omnivalt');

      // Default values
      if ( empty($this->settings['api_country']) ) {
        $this->settings['api_country'] = 'LT';
      }
      foreach ( $this->methods_asociations as $key => $name ) {
        if ( empty($this->settings['method_' . $key]) ) {
          $this->settings['method_' . $key] = 'no';
        }
      }

      $this->shipping_sets = OmnivaLt_Helper::get_shipping_sets($this->settings['api_country']);
    }

    private function convert_method_name_to_short($method_name, $reverse = false)
    {
      foreach ( $this->methods_asociations as $key => $value ) {
        if ( ! $reverse ) {
          if ( $method_name === $value ) {
            return $key;
          }
        } else {
          if ( $method_name === $key ) {
            return $value;
          }
        }
      }

      return $method_name;
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init()
    {

      // Load the settings API

      $this->init_form_fields();
      $this->init_settings();

      //$this->title = $this->get_option('title');
      
      // Save settings in admin if you have any defined
      add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
      //$this->update_locations_file();
    }

    public function update_locations_file()
    {
      $locations = $this->omnivalt_configs['locations'];
      $fp = fopen(OMNIVALT_DIR . '/' . "locations.json", "w");
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $locations['source_url']);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_FILE, $fp);
      curl_setopt($curl, CURLOPT_TIMEOUT, 60);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      $data = curl_exec($curl);
      curl_close($curl);
      fclose($fp);
    }

    /**
     * Load settings form
     */
    function admin_options()
    {
      ?>
      <div class="omniva-title">
        <div class="title">
          <h2><?php echo $this->method_title; ?></h2>
          <p><?php echo $this->method_description; ?></p>
        </div>
        <div class="logo">
          <img src="<?php echo OMNIVALT_URL; ?>assets/img/logos/omniva_vertical_m.png" alt="Omniva logo" />
        </div>
      </div>
      <table class="form-table omniva-settings">
        <?php $this->generate_settings_html(); ?>
      </table>
      <?php
    }
    /**
     * Define settings field for this shipping
     * @return void
     */
    function init_form_fields()
    {
      $countries_options = array();
      foreach ($this->omnivalt_configs['shipping_params'] as $country_code => $ship_params) {
        $countries_options[$country_code] = $country_code . ' - ' . $ship_params['title'];
      }
      
      $fields = array(
        'enabled' => array(
          'title' => __('Enable', 'omnivalt'),
          'type' => 'checkbox',
          'description' => sprintf(__('Activate this plugin and allow to use %s methods', 'omnivalt'), $this->method_title),
          //'desc_tip' => true,
          'default' => 'yes',
        ),
        'hr_api' => array(
          'type' => 'hr',
          'title' => __('API', 'omnivalt'),
        ),
        'api_url' => array(
          'title' => __('API URL', 'omnivalt'),
          'type' => 'text',
          'default' => 'https://edixml.post.ee',
          'description' => __('Change only if want use custom API URL.', 'omnivalt') . ' ' . sprintf(__('Default Omniva API URL is %s', 'omnivalt'),'<code>https://edixml.post.ee</code>'),
        ),
        'api_user' => array(
          'title' => __('API user', 'omnivalt'),
          'type' => 'text',
          'description' => __('Please contact Omniva for API access codes.', 'omnivalt'),
        ),
        'api_pass' => array(
          'title' => __('API password', 'omnivalt'),
          'type' => 'password',
        ),
        'api_country' => array(
          'title' => __('API account country', 'omnivalt'),
          'type'    => 'select',
          'options' => array(
            'LT' => __('Lithuania', 'omnivalt') . ' / ' . __('Latvia', 'omnivalt'),
            'EE' => __('Estonia', 'omnivalt'),
          ),
          'default' => 'LT',
          'description' => __('Choose the country of Omniva support from which you received API logins.', 'omnivalt'),
        ),
        'hr_shop' => array(
          'type' => 'hr',
          'title' => __('Sender information', 'omnivalt'),
        ),
        'company' => array(
          'title' => __('Company name', 'omnivalt'),
          'type' => 'text',
        ),
        'bank_account' => array(
          'title' => __('Bank account', 'omnivalt'),
          'type' => 'text',
        ),
        'shop_name' => array(
          'title' => __('Shop name', 'omnivalt'),
          'type' => 'text',
        ),
        'shop_city' => array(
          'title' => __('Shop city', 'omnivalt'),
          'type' => 'text',
        ),
        'shop_address' => array(
          'title' => __('Shop address', 'omnivalt'),
          'type' => 'text',
        ),
        'shop_postcode' => array(
          'title' => __('Shop postcode', 'omnivalt'),
          'type' => 'text',
          'description' => sprintf(__('Example for Latvia: %1$s. Example for other countries: %2$s.', 'omnivalt'), '<code>LV-0123</code>', '<code>01234</code>'),
        ),
        'shop_countrycode' => array(
          'title' => __('Shop country code', 'omnivalt'),
          'type'    => 'select',
          'class' => 'checkout-style pickup-point',
          'options' => $countries_options,
          'default' => 'LT',
        ),
        'shop_phone' => array(
          'title' => __('Shop phone number', 'omnivalt'),
          'type' => 'text',
          'description' => sprintf(__('Required mobile phone number if want use service "%s".', 'omnivalt'), $this->omnivalt_configs['additional_services']['delivery_confirmation_sms']['title']),
        ),
        'shop_email' => array(
          'title' => __('Shop email', 'omnivalt'),
          'type' => 'text',
        ),
        'pick_up_start' => array(
          'title' => __('Pick up time start', 'omnivalt'),
          'type' => 'text',
          'placeholder' => '08:00',
          'description' => sprintf(__('Allowed formats: %1$s. Default time is %2$s, if incorrect value is entered or field is empty.', 'omnivalt'),'<i>07:00, 7:00, 7</i>', '08:00'),
        ),
        'pick_up_end' => array(
          'title' => __('Pick up time end', 'omnivalt'),
          'type' => 'text',
          'placeholder' => '17:00',
          'description' => sprintf(__('Allowed formats: %1$s. Default time is %2$s, if incorrect value is entered or field is empty.', 'omnivalt'),'<i>09:00, 9:00, 9</i>', '17:00'),
        ),
        'send_off' => array(
          'title' => __('Send off type', 'omnivalt'),
          'type' => 'select',
          'description' => __('Send from store type.', 'omnivalt'),
          'options' => array(
            'pt' => __('Parcel terminal', 'omnivalt'),
            'c' => __('Courier', 'omnivalt'),
            'po' => __('Post office', 'omnivalt'),
            'lc' => __('Logistics center', 'omnivalt'),
          )
        ),
      );
      $fields['hr_methods'] = array(
        'type' => 'hr',
        'title' => __('Shipping methods', 'omnivalt'),
      );
      foreach ($this->omnivalt_configs['method_params'] as $ship_method => $ship_method_values) {
        if ($ship_method_values['is_shipping_method'] === false) continue;

        $exists = false;
        foreach ( $this->omnivalt_configs['shipping_params'] as $ship_params ) {
          $method_key = ($ship_method === 'terminal') ? 'pickup' : $ship_method;
          if ( in_array($method_key, $ship_params['methods']) ) {
            $exists = true;
          }
        }
        if ( ! $exists ) continue;

        //$description = sprintf(__('Show %s method in checkout.', 'omnivalt'), strtolower($ship_method_values['title']));

        $fields['method_' . $ship_method_values['key']] = array(
          'title' => $ship_method_values['title'],
          'type' => 'checkbox',
          'description' => $ship_method_values['description'],
        );
      }
      $fields['txt_returns'] = array(
        'title' => __('Returns', 'omnivalt'),
        'type' => 'string',
        'text' => __('Please contact Omniva about parcels returns.', 'omnivalt'),
      );
      $fields['hr_prices'] = array(
        'type' => 'hr',
        'title' => __('Delivery countries and prices', 'omnivalt'),
      );
      foreach ( $this->destinations_countries as $country_code => $country_name ) {
        $fields['prices_'.$country_code] = array(
          'type' => 'prices_box',
          'lang' => $country_code,
        );
      }
      $fields['hr_settings'] = array(
        'type' => 'hr',
        'title' => __('Shipping methods settings', 'omnivalt'),
      );
      foreach ( $this->omnivalt_configs['method_params'] as $ship_method => $ship_method_values ) {
        if ($ship_method_values['is_shipping_method'] === false) continue;

        $field_key = 'weight_' . $ship_method_values['key'];
        if ( $ship_method_values['key'] === 'pt' ) {
          $field_key = 'weight';
        }

        $fields[$field_key] = array(
          'title' => sprintf(__('Max cart weight (%1$s) for %2$s', 'omnivalt'), 'kg', strtolower($ship_method_values['title'])),
          'type' => 'number',
          'custom_attributes' => array(
            'step' => 0.001,
            'min' => 0
          ),
          'description' => sprintf(__('Maximum allowed all cart products weight for %s.', 'omnivalt'), strtolower($ship_method_values['title'])),
          'default' => $ship_method_values['weight']['default'],
          'class' => 'omniva_' . $ship_method,
        );
      }
      $fields['size_pt'] = array(
        'title' => sprintf(__('Max cart size (%s) for terminal', 'omnivalt'), get_option('woocommerce_dimension_unit')),
        'type' => 'dimensions',
        'description' => __('Maximum cart size for parcel terminals. Leave all empty to disable.', 'omnivalt') . '<br/>' . __('Preliminary cart size is calculated by trying to fit all products by taking their dimensions (boxes) indicated in their settings.', 'omnivalt'),
        'class' => 'omniva_terminal'
      );
      /*$fields['size_c'] = array(
        'title' => sprintf(__('Max size (%s) for courier', 'omnivalt'),get_option('woocommerce_dimension_unit')),
        'type' => 'dimensions',
        'description' => __('Maximum product size for courier. Leave all empty to disable.', 'omnivalt') . '<br/>' . __('If the length, width or height of at least one product exceeds the specified values, then it will not be possible to select the courier delivery method for the whole cart.', 'omnivalt')
      );*/
      $fields['restricted_categories'] = array(
        'title' => __('Disable for specific categories', 'omnivalt'),
        'type' => 'multiselect',
        'class' => 'wc-enhanced-select',
        'description' => __('Select categories for which you want to disable the Omniva method', 'omnivalt'),
        'options' => $this->omnivalt_get_categories(),
        //'desc_tip' => true,
        'required' => false,
        'custom_attributes' => array(
          'data-placeholder' => __('Select Categories', 'omnivalt'),
          'data-name' => 'restricted_categories'
        ),
      );
      $fields['restricted_shipclass'] = array(
        'title' => __('Disable for specific shipping classes', 'omnivalt'),
        'type' => 'multiselect',
        'class' => 'wc-enhanced-select',
        'description' => __('Select shipping classes for which you want to disable the Omniva method', 'omnivalt'),
        'options' => $this->omnivalt_get_shipping_classes(),
        //'desc_tip' => true,
        'required' => false,
        'custom_attributes' => array(
          'data-placeholder' => __('Select Shipping classes', 'omnivalt'),
          'data-name' => 'restricted_shipclass'
        ),
      );
      $fields['auto_select'] = array(
        'title' => __('Automatic terminal selection', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Automatically select terminal by postcode.', 'omnivalt'),
        'default' => 'yes',
        'class' => 'omniva_terminal'
      );
      $fields['hr_design'] = array(
        'type' => 'hr',
        'title' => __('Design', 'omnivalt'),
      );
      $fields['show_map'] = array(
        'title' => __('Map', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Show map of terminals.', 'omnivalt'),
        'default' => 'yes',
        'class' => 'omniva_terminal'
      );
      $fields['label_design'] = array(
        'title' => __('Label design', 'omnivalt'),
        'type' => 'select',
        'description' => __('Choose what the shipping method label will be displayed on the Cart and Checkout pages.', 'omnivalt'),
        'options' => array(
          'classic' => 'Omniva ' . strtolower(__('Parcel terminal', 'omnivalt')),
          'full' => 'LOGO Omniva ' . strtolower(__('Parcel terminal', 'omnivalt')),
          'logo' => 'LOGO ' . __('Parcel terminal', 'omnivalt'),
          'short' => __('Parcel terminal', 'omnivalt'),
        )
      );
      $fields['custom_label'] = array(
        'title' => __('Custom label names', 'omnivalt'),
        'type' => 'label_name',
        'description' => __('Use custom shipping method name.', 'omnivalt') . ' ' . __('Values is not translatable.', 'omnivalt'),
      );
      $fields['position'] = array(
        'title' => __('Positions', 'omnivalt'),
        'type' => 'position',
        'description' => __('Position of each Omniva shipping method in shipping methods list on Checkout page.', 'omnivalt') . '<br/>' . __('Leave empty to not change position. A higher number means a lower position (1 - top of the list).', 'omnivalt') . '<br/>' . __('NOTE', 'omnivalt') . ': ' . __('Positioning may be affected by other plugins or functions used in the theme.', 'omnivalt'),
      );
      $fields['hr_orders'] = array(
        'type' => 'hr',
        'title' => __('Orders', 'omnivalt'),
      );
      $fields['track_info_in_email'] = array(
        'title' => __('Show tracking information in emails', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Show tracking information in WooCommerce Order emails.', 'omnivalt'),
        'default' => 'yes',
      );
      $fields['hr_labels'] = array(
        'type' => 'hr',
        'title' => __('Labels', 'omnivalt'),
      );
      $fields['print_type'] = array(
        'title' => __('Labels print type', 'omnivalt'),
        'type' => 'select',
        'options' => array(
          '1' => __('Original (single label)', 'omnivalt'),
          '4' => __('A4 (4 labels)', 'omnivalt')
        ),
        'default' => '4',
        'description' => __('How many labels to print per page.', 'omnivalt')
      );
      $inline_variables = '';
      foreach ( $this->omnivalt_configs['text_variables'] as $key => $title ) {
        $inline_variables .= '<br/><code>{' . $key . '}</code> - ' . $title;
      }
      $fields['label_note'] = array(
        'title' => __('Note on label', 'omnivalt'),
        'type' => 'text',
        'description' => sprintf(__('Show note or other comment on label. You can use this variables: %s', 'omnivalt'), $inline_variables),
        'custom_attributes' => array(
          'maxlength' => 128,
        ),
      );
      $fields['email_created_label'] = array(
        'title' => __('Send email when a label is created', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Send an email to customer with tracking code, when the label is generated.', 'omnivalt') . '<br/>' . sprintf(__('To override email template, copy template file from %1$s to your theme %2$s directory.', 'omnivalt'), '<code>wp-content/plugins/omniva-woocommerce/templates/emails</code>', '<code>wp-content/themes/theme-name/omniva/emails</code>'),
        'default' => ''
      );
      $fields['email_created_label_subject'] = array(
        'title' => '',
        'type' => 'text',
        'description' => __('Custom email subject (this field value not translating into other languages).', 'omnivalt'),
        'placeholder' => __('Your order shipment has been registered', 'omnivalt')
      );
      $fields['send_email_on_arrive'] = array(
        'title' => __('Send email on shipment arrive', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Send email to customer from Omniva, when the shipment arrives at the terminal.', 'omnivalt'),
        'default' => '',
        'class' => 'omniva_terminal'
      );
      $fields['send_return_code'] = array(
        'title' => __('Send return code', 'omnivalt'),
        'type' => 'select',
        'options' => array(
          'all' => __('Add to SMS and email', 'omnivalt'),
          'sms' => __('Add to SMS', 'omnivalt'),
          'email' => __('Add to email', 'omnivalt'),
          'dont' => __('Do not send', 'omnivalt'),
        ),
        'default' => 'all',
        'description' => __('Choose how to send the return code to the customer', 'omnivalt')
      );
      $fields['hr_manifest'] = array(
        'type' => 'hr',
        'title' => __('Manifest', 'omnivalt'),
      );
      $fields['manifest_enable'] = array(
        'title' => __('Enable manifest print', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Allow print manifest. Disable this option will hide table manifest column and manifest generation buttons.', 'omnivalt'),
        'default' => 'yes',
      );
      $fields['manifest_show_barcode'] = array(
        'title' => __('Show barcode in manifest', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Show barcode image in manifest.', 'omnivalt'),
        'default' => 'yes',
      );
      $fields['hr_debug'] = array(
        'type' => 'hr',
        'title' => __('Debug', 'omnivalt'),
      );
      $fields['debug_mode'] = array(
        'title' => __('Enable debug mode', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Enable request and response logging.', 'omnivalt') . ' ' . sprintf(__('Log files are stored for %d days.', 'omnivalt'), $this->omnivalt_configs['debug']['delete_after']),
        'default' => ''
      );
      $fields['debugview_request'] = array(
        'type' => 'debug_window',
        'files' => OmnivaLt_Debug::get_all_files('request'),
        'title' => __('Logged requests', 'omnivalt'),
        'subtitle' => __('Request', 'omnivalt'),
        'class' => 'omniva_debug'
      );
      $fields['debugview_response'] = array(
        'type' => 'debug_window',
        'files' => OmnivaLt_Debug::get_all_files('response'),
        'title' => __('Logged responses', 'omnivalt'),
        'subtitle' => __('Response', 'omnivalt'),
        'class' => 'omniva_debug'
      );
      $fields['hr_end'] = array(
        'type' => 'hr',
      );
      $this->form_fields = $fields;
    }

    public function generate_hr_html( $key, $value )
    {
      $class = (isset($value['class'])) ? $value['class'] : '';
      $title = '';
      if ( ! empty($value['title']) ) {
        if ( ! empty($class) ) {
          $class .= ' ';
        }
        $class .= 'have_title';
        $title = '<span>' . $value['title'] . '</span>';
      }
      
      $html = '<tr valign="top"><td colspan="2" class="section_title"><hr class="' . $class . '">' . $title . '</td></tr>';
      
      return $html;
    }
    
    public function generate_empty_html( $key, $value )
    {
      $class = (isset($value['class'])) ? $value['class'] : '';
      
      $html = '<tr valign="top"><td colspan="2" class="' . $class . '"></td></tr>';
      
      return $html;
    }

    public function generate_string_html( $key, $value )
    {
      $class = (isset($value['class'])) ? $value['class'] : '';
      
      $html = '<tr valign="top">';
      $html .= '<th scope="row" class="titledesc"><label>' . $value['title'] . '</label></th>';
      $html .= '<td class="forminp"><fieldset><p class="description">' . $value['text'] . '</p></fieldset></td>';
      $html .= '</tr>';

      return $html;
    }

    public function generate_prices_box_html( $key, $value )
    {
      $box_key = $this->get_field_key($key);
      $html = '';
      if ( isset($value['lang']) ) {
        $flag_img_url = OMNIVALT_URL . 'assets/img/flags/' . strtolower($value['lang']) . '.png';
        if ( isset($this->omnivalt_configs['shipping_params'][$value['lang']]) ) {
          $shipping_methods = $this->omnivalt_configs['shipping_params'][$value['lang']]['methods'];
          $shipping_keys = array();
          foreach ( $shipping_methods as $ship_method ) {
            $shipping_keys[] = $this->convert_method_name_to_short($ship_method);
          }
        } else {
          $shipping_keys = array_keys($this->methods_asociations);
        }
        $fields = array();
        foreach ( $shipping_keys as $ship_key ) {
          $fields[$ship_key . '_enable'] = $ship_key . '_enable_' . $value['lang'];
          $fields[$ship_key . '_price_type'] = $ship_key . '_price_type_' . $value['lang'];
          $fields[$ship_key . '_price_single'] = $ship_key . '_price_' . $value['lang'];
          $fields[$ship_key . '_price_by_weight'] = $ship_key . '_price_by_weight_' . $value['lang'];
          $fields[$ship_key . '_price_by_amount'] = $ship_key . '_price_by_amount_' . $value['lang'];
          $fields[$ship_key . '_enable_free_from'] = $ship_key . '_price_' . $value['lang'] . '_enFree';
          $fields[$ship_key . '_free_from'] = $ship_key . '_price_' . $value['lang'] . '_FREE';
          $fields[$ship_key . '_enable_coupon'] = $ship_key . '_price_' . $value['lang'] . '_enCoupon';
          $fields[$ship_key . '_coupon'] = $ship_key . '_price_' . $value['lang'] . '_coupon';
          $fields[$ship_key . '_description'] = $ship_key . '_description_' . $value['lang'];
        }
        /* START Fields only for parcel terminal */
        $fields['pt_price_by_boxsize'] = 'pt_price_by_box_' . $value['lang'];
        /* END Fields only for parcel terminal */
        $saved_values = json_decode($this->get_option($key));
        $values = array();
        foreach ( $fields as $id => $field ) {
          $cur_value = (isset($saved_values->{$id})) ? $saved_values->{$id} : '';
          /* -Compatibility with old data- */
          $old_value = $this->get_option($field);
          $is_old = false;
          if ($cur_value === '' && (!empty($old_value) || $old_value === 0 || $old_value === '0')) {
            $cur_value = $old_value;
            $is_old = true;
          }
          /* -End of Compatibility with old data- */
          $values[$id] = array(
            'id' => $field,
            'key' => $this->get_field_key($field),
            'value' => $cur_value,
            'is_old' => $is_old,
          );
        }

        $args = array(
          'posts_per_page'   => -1,
          'orderby'          => 'title',
          'order'            => 'asc',
          'post_type'        => 'shop_coupon',
          'post_status'      => 'publish',
        );  
        $coupons = get_posts($args);

        ob_start();
        ?>
        <tr class="row-prices" valign="top">
          <td colspan="2">
            <div class="prices_box">
              <div class="pb-lang">
                <img src="<?php echo $flag_img_url; ?>" alt="[<?php echo $value['lang']; ?>]">
                <span><?php echo $value['lang'] . ' ' . __('prices','omnivalt'); ?></span>
              </div>
              <div class="pb-content">
                <?php foreach ($shipping_keys as $ship_key) : ?>
                  <?php if (isset($values[$ship_key . '_enable'])) : ?>
                    <?php
                    $params = array(
                      'box_key' => $box_key,
                      'enable' => array(
                        'id' => $values[$ship_key . '_enable']['key'],
                        'name' => $ship_key . '_enable',
                        'checked' => ($values[$ship_key . '_enable']['value']) ? 'checked' : '',
                        'class' => $ship_key . '_enable',
                      ),
                      'prices' => array(
                        'type' => (isset($values[$ship_key . '_price_type'])) ? $values[$ship_key . '_price_type'] : false,
                        'type_name' => $ship_key . '_price_type',
                        'single' => (isset($values[$ship_key . '_price_single'])) ? $values[$ship_key . '_price_single'] : false,
                        'single_name' => $ship_key . '_price_single',
                        'weight' => (isset($values[$ship_key . '_price_by_weight'])) ? $values[$ship_key . '_price_by_weight'] : false,
                        'weight_name' => $ship_key . '_price_by_weight',
                        'amount' => (isset($values[$ship_key . '_price_by_amount'])) ? $values[$ship_key . '_price_by_amount'] : false,
                        'amount_name' => $ship_key . '_price_by_amount',
                        'free_enable' => (isset($values[$ship_key . '_enable_free_from'])) ? $values[$ship_key . '_enable_free_from'] : false,
                        'free_enable_name' => $ship_key . '_enable_free_from',
                        'free_enable_class' => $ship_key . '_enable_free',
                        'free' => (isset($values[$ship_key . '_free_from'])) ? $values[$ship_key . '_free_from'] : false,
                        'free_name' => $ship_key . '_free_from',
                        'coupon' => (isset($values[$ship_key . '_coupon'])) ? $values[$ship_key . '_coupon'] : false,
                        'coupon_name' => $ship_key . '_coupon',
                        'coupon_enable' => (isset($values[$ship_key . '_enable_coupon'])) ? $values[$ship_key . '_enable_coupon'] : false,
                        'coupon_enable_name' => $ship_key . '_enable_coupon',
                        'coupon_enable_class' => $ship_key . '_enable_coupon',
                      ),
                      'data' => array(
                        'coupons' => $coupons,
                      ),
                      'other' => array(
                        'desc' => (isset($values[$ship_key . '_description'])) ? $values[$ship_key . '_description'] : false,
                        'desc_name' => $ship_key . '_description',
                      ),
                    );
                    foreach ( $this->omnivalt_configs['method_params'] as $method_name => $method_values ) {
                      if ($ship_key === $method_values['key']) {
                        $params['type'] = $method_name;
                        $params['title'] = $method_values['title'];
                        $params['enable']['title'] = sprintf(__('Enable %s','omnivalt'), strtolower($method_values['title']));
                        break;
                      }
                    }
                    if ( $ship_key === 'pt' ) {
                      $params['prices']['boxsize'] = (isset($values[$ship_key . '_price_by_boxsize'])) ? $values[$ship_key . '_price_by_boxsize'] : false;
                      $params['prices']['boxsize_name'] = $ship_key . '_price_by_boxsize';
                    }
                    echo $this->omnivalt_build_prices_block($params);
                    ?>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
          </td>
        </tr>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
      }
      return $html;
    }

    private function omnivalt_build_prices_block( $params ) {
      $params = array(
        'type' => (isset($params['type'])) ? $params['type'] : '',
        'title' => (isset($params['title'])) ? $params['title'] : __('Shipping','omnivalt'),
        'box_key' => (isset($params['box_key'])) ? $params['box_key'] : '',
        'enable' => array(
          'title' => (isset($params['enable']['title'])) ? $params['enable']['title'] : __('Enable','omnivalt'),
          'id' => (isset($params['enable']['id'])) ? $params['enable']['id'] : '',
          'name' => (isset($params['enable']['name'])) ? $params['enable']['name'] : '',
          'checked' => (isset($params['enable']['checked'])) ? $params['enable']['checked'] : '',
          'class' => (isset($params['enable']['class'])) ? $params['enable']['class'] : '',
        ),
        'prices' => array(
          'type' => (isset($params['prices']['type'])) ? $params['prices']['type'] : false,
          'type_name' => (isset($params['prices']['type_name'])) ? $params['prices']['type_name'] : '',
          'single' => (isset($params['prices']['single'])) ? $params['prices']['single'] : false,
          'single_name' => (isset($params['prices']['single_name'])) ? $params['prices']['single_name'] : '',
          'single_title' => (isset($params['prices']['single_title'])) ? $params['prices']['single_title'] : __('Price','omnivalt'),
          'weight' => (isset($params['prices']['weight'])) ? $params['prices']['weight'] : false,
          'weight_name' => (isset($params['prices']['weight_name'])) ? $params['prices']['weight_name'] : '',
          'weight_title' => (isset($params['prices']['weight_title'])) ? $params['prices']['weight_title'] : __('Weight','omnivalt'),
          'amount' => (isset($params['prices']['amount'])) ? $params['prices']['amount'] : false,
          'amount_name' => (isset($params['prices']['amount_name'])) ? $params['prices']['amount_name'] : '',
          'amount_title' => (isset($params['prices']['amount_title'])) ? $params['prices']['amount_title'] : __('Cart amount','omnivalt'),
          'boxsize' => (isset($params['prices']['boxsize'])) ? $params['prices']['boxsize'] : false,
          'boxsize_name' => (isset($params['prices']['boxsize_name'])) ? $params['prices']['boxsize_name'] : '',
          'boxsize_title' => (isset($params['prices']['boxsize_title'])) ? $params['prices']['boxsize_title'] : __('Box size','omnivalt'),
          'free_enable' => (isset($params['prices']['free_enable'])) ? $params['prices']['free_enable'] : false,
          'free_enable_name' => (isset($params['prices']['free_enable_name'])) ? $params['prices']['free_enable_name'] : '',
          'free_enable_class' => (isset($params['prices']['free_enable_class'])) ? $params['prices']['free_enable_class'] : '',
          'free' => (isset($params['prices']['free'])) ? $params['prices']['free'] : false,
          'free_name' => (isset($params['prices']['free_name'])) ? $params['prices']['free_name'] : '',
          'free_title' => (isset($params['prices']['free_title'])) ? $params['prices']['free_title'] : __('Free from','omnivalt'),
          'coupon' => (isset($params['prices']['coupon'])) ? $params['prices']['coupon'] : false,
          'coupon_name' => (isset($params['prices']['coupon_name'])) ? $params['prices']['coupon_name'] : '',
          'coupon_title' => (isset($params['prices']['coupon_title'])) ? $params['prices']['coupon_title'] : __('Free with coupon','omnivalt'),
          'coupon_enable' => (isset($params['prices']['coupon_enable'])) ? $params['prices']['coupon_enable'] : false,
          'coupon_enable_name' => (isset($params['prices']['coupon_enable_name'])) ? $params['prices']['coupon_enable_name'] : '',
          'coupon_enable_class' => (isset($params['prices']['coupon_enable_class'])) ? $params['prices']['coupon_enable_class'] : '',
        ),
        'data' => array(
          'coupons' => (isset($params['data']['coupons'])) ? $params['data']['coupons'] : array(),
        ),
        'other' => array(
          'desc' => (isset($params['other']['desc'])) ? $params['other']['desc'] : false,
          'desc_name' => (isset($params['other']['desc_name'])) ? $params['other']['desc_name'] : '',
          'desc_title' => (isset($params['other']['desc_title'])) ? $params['other']['desc_title'] : __('Description','omnivalt'),
        ),
      );

      if ( empty($params['type']) || empty($params['box_key']) ) {
        return '';
      }

      ob_start();
      ?>
      <div class="block-prices <?php echo $params['type']; ?>">
        <div class="sec-title">
          <?php
          /* -Compatibility with old data- */
          if ( $params['prices']['single']['is_old'] && isset($params['prices']['single']['value']) && $params['prices']['single']['value'] !== '' ) {
            $params['enable']['checked'] = 'checked';
          }
          /* -End of Compatibility with old data- */
          $html_params = array(
            'label' => $params['title'],
            'title' => $params['enable']['title'],
            'id' => $params['enable']['id'],
            'name' => $params['box_key'] . '[' . $params['enable']['name'] . ']',
            'class' => $params['enable']['class'],
            'checked' => ($params['enable']['checked'] === 'checked') ? true : false,
          );
          echo OmnivaLt_Admin_Html::buildSwitcher($html_params);
          ?>
        </div>
        <div class="sec-prices">
          <?php if ( $params['prices']['type'] !== false ) : ?>
            <?php
            $html_params = array(
              'field_id' => $params['prices']['type']['key'],
              'field_name' => $params['box_key'] . '[' . $params['prices']['type_name'] . ']',
              'field_value' => $params['prices']['type']['value'],
            );
            if ( $params['prices']['boxsize'] !== false ) {
              $html_params['add_select_options'] = array(
                'boxsize' => __('By box size','omnivalt'),
              );
            }
            echo OmnivaLt_Admin_Html::buildPriceType($html_params);
            ?>
          <?php endif; ?>
          <?php if ( $params['prices']['single'] !== false ) : ?>
            <div class="prices-single">
              <?php
              $field_value = $params['prices']['single']['value'];
              if ( empty($field_value) && $field_value !== 0 && $field_value !== '0' ) {
                $field_value = 2;
              }
              $html_params = array(
                'label' => $params['prices']['single_title'] . ':',
                'id' => $params['prices']['single']['key'],
                'type' => 'number',
                'name' => $params['box_key'] . '[' . $params['prices']['single_name'] . ']',
                'value' => $field_value,
                'step' => 0.01,
                'min' => 0,
              );
              echo OmnivaLt_Admin_Html::buildSimpleField($html_params);
              ?>
            </div>
          <?php endif; ?>
          <?php if ( $params['prices']['weight'] !== false ) : ?>
            <?php
            $html_params = array(
              'type' => 'weight',
              'field_id' => $params['prices']['weight']['key'],
              'field_name' => $params['box_key'] . '[' . $params['prices']['weight_name'] . ']',
              'values' => $params['prices']['weight']['value'],
              'c1_title' => $params['prices']['weight_title'] . ' (kg)',
              'c1_step' => 0.001,
            );
            echo OmnivaLt_Admin_Html::buildPricesTable($html_params);
            ?>
          <?php endif; ?>
          <?php if ( $params['prices']['amount'] !== false ) : ?>
            <?php
            $html_params = array(
              'type' => 'amount',
              'field_id' => $params['prices']['amount']['key'],
              'field_name' => $params['box_key'] . '[' . $params['prices']['amount_name'] . ']',
              'values' => $params['prices']['amount']['value'],
              'c1_title' => $params['prices']['amount_title'],
              'c1_step' => 0.01,
            );
            echo OmnivaLt_Admin_Html::buildPricesTable($html_params);
            ?>
          <?php endif; ?>
          <?php if ( $params['prices']['boxsize'] !== false ) : ?>
            <?php
            $method_params = $this->omnivalt_configs['method_params'];
            $box_sizes = array();
            if ( isset($method_params[$params['type']]['sizes']) ) {
              foreach ( $method_params[$params['type']]['sizes'] as $key => $sizes ) {
                if ( $key !== 'min' ) {
                  $box_sizes[] = $key;
                }
              }
            }
            if ( empty($params['prices']['boxsize']['value']) ) {
              $default_values = array();
              for ( $i=0;$i<count($box_sizes);$i++ ) {
                $default_values[] = (object) array(
                  'value' => $box_sizes[$i],
                  'price' => 2,
                );
              }
              $params['prices']['boxsize']['value'] = (object) $default_values;
            } else {
              $i = 0;
              foreach ( $params['prices']['boxsize']['value'] as $value ) {
                if ( isset($box_sizes[$i]) ) {
                  $value->value = $box_sizes[$i];
                }
                $i++;
              }
            }
            $box_titles = $method_params[$params['type']]['titles'];
            foreach ( $box_titles as $key => $title ) {
              $h = $method_params[$params['type']]['sizes'][$key][0];
              $w = $method_params[$params['type']]['sizes'][$key][1];
              $l = $method_params[$params['type']]['sizes'][$key][2];
              $text = sprintf(__('Max %s cm', 'omnivalt'), $h . '×' . $w . '×' . $l);
              $box_titles[$key] = $title . '<br/><small>' . $text . '</small>';
            }
            $html_params = array(
              'type' => 'boxsize',
              'field_id' => $params['prices']['boxsize']['key'],
              'field_name' => $params['box_key'] . '[' . $params['prices']['boxsize_name'] . ']',
              'values' => $params['prices']['boxsize']['value'],
              'c1_title' => $params['prices']['boxsize_title'],
              'allow_add' => false,
              'c1_text' => $box_titles,
              'desc' => __('NOTE', 'omnivalt') . ': ' . __('If at least one item in the cart does not have the specified size, then this shipping method will not be displayed', 'omnivalt'),
            );
            echo OmnivaLt_Admin_Html::buildPricesTable($html_params);
            ?>
          <?php endif; ?>
          <?php if ( $params['prices']['free'] !== false ) : ?>
            <div class="prices-free">
              <?php
              $field_checked = ($params['prices']['free_enable']['value']) ? 'checked' : '';
              /* -Compatibility with old data- */
              if ( $params['prices']['free']['is_old'] && isset($params['prices']['free']['value']) && $params['prices']['free']['value'] !== '' ) {
                $field_checked = 'checked';
              }
              /* -End of Compatibility with old data- */
              $html_params = array(
                'label' => $params['prices']['free_title'] . ':',
                'label_position' => 'after',
                'id' => $params['prices']['free_enable']['key'],
                'class' => $params['prices']['free_enable_class'],
                'name' => $params['box_key'] . '[' . $params['prices']['free_enable_name'] . ']',
                'checked' => ($field_checked === 'checked') ? true : false,
                'value' => 1,
              );
              echo OmnivaLt_Admin_Html::buildCheckbox($html_params);

              $field_value = $params['prices']['free']['value'];
              if ( empty($field_value) && $field_value != 0 ) {
                $field_value = 100;
              }
              $html_params = array(
                'id' => $params['prices']['free']['key'],
                'type' => 'number',
                'name' => $params['box_key'] . '[' . $params['prices']['free_name'] . ']',
                'value' => $field_value,
                'step' => 0.01,
                'min' => 0,
                'class' => 'input-text regular-input price_free',
              );
              echo ' ' . OmnivaLt_Admin_Html::buildSimpleField($html_params);
              ?>
            </div>
          <?php endif; ?>
          <?php if ( $params['prices']['coupon'] !== false ) : ?>
            <div class="prices-coupon">
              <?php
              $field_checked = ($params['prices']['coupon']['value']) ? 'checked' : '';
              $html_params = array(
                'label' => $params['prices']['coupon_title'] . ':',
                'label_position' => 'after',
                'id' => $params['prices']['coupon_enable']['key'],
                'class' => $params['prices']['coupon_enable_class'],
                'name' => $params['box_key'] . '[' . $params['prices']['coupon_enable_name'] . ']',
                'checked' => ($field_checked === 'checked') ? true : false,
                'value' => 1,
              );
              echo OmnivaLt_Admin_Html::buildCheckbox($html_params);

              $options = array();
              foreach( $params['data']['coupons'] as $coupon ) {
                $options[] = array(
                  'value' => strtolower($coupon->post_title),
                  'title' => $coupon->post_title,
                );
              }
              $selected = (empty($params['prices']['coupon']['value'])) ? 'selected' : '';
              $html_params = array(
                'name' => $params['box_key'] . '[' . $params['prices']['coupon_name'] . ']',
                'id' => $params['prices']['coupon']['key'],
                'class' => 'price_coupon',
                'options' => $options,
                'selected' => $params['prices']['coupon']['value'],
              );
              echo ' ' . OmnivaLt_Admin_Html::buildSelectField($html_params);
              ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="sec-other">
          <?php if ( $params['other']['desc'] !== false ) : ?>
            <div class="other-description">
              <?php
              $html_params = array(
                'label' => $params['other']['desc_title'] . ':',
                'id' => $params['other']['desc']['key'],
                'name' => $params['box_key'] . '[' . $params['other']['desc_name'] . ']',
                'value' => $params['other']['desc']['value'],
              );
              echo OmnivaLt_Admin_Html::buildTextareaField($html_params);
              ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php
      $html = ob_get_contents();
      ob_end_clean();

      return $html;
    }

    public function validate_prices_box_field( $key, $value ) {
      $values = wp_json_encode($value);
      return $values;
    }

    public function generate_dimensions_html( $key, $value ) {
      $field_key = $this->get_field_key($key);
      $field_class = (isset($value['class'])) ? $value['class'] : '';

      if ( $this->get_option($key) !== '' ) {
        $dim_values = $this->get_option($key);
        if ( is_string($dim_values) ) {
          $dim_values = json_decode($this->get_option($key), true);
        }
      } else {
        $dim_values = array();
      }

      ob_start();
      ?>
      <tr valign="top">
        <th scope="row" class="titledesc">
          <label><?php echo esc_html($value['title']); ?></label>
        </th>
        <td class="forminp">
          <fieldset class="field-dimensions <?php echo $field_class; ?>">
            <input type="number" value="<?php echo $dim_values[0] ?? ''; ?>"
              id="<?php echo esc_html($field_key); ?>_0"
              name="<?php echo esc_html($field_key); ?>[0]"
              min="0.001" step="0.001" placeholder="<?php echo __('Length','omnivalt'); ?>">
            <span>x</span>
            <input type="number" value="<?php echo $dim_values[1] ?? ''; ?>"
              id="<?php echo esc_html($field_key); ?>_1"
              name="<?php echo esc_html($field_key); ?>[1]"
              min="0.001" step="0.001" placeholder="<?php echo __('Width','omnivalt'); ?>">
            <span>x</span>
            <input type="number" value="<?php echo $dim_values[2] ?? ''; ?>"
              id="<?php echo esc_html($field_key); ?>_2"
              name="<?php echo esc_html($field_key); ?>[2]"
              min="0.001" step="0.001" placeholder="<?php echo __('Height','omnivalt'); ?>">
             <span><?php echo get_option('woocommerce_dimension_unit'); ?></span>
            <?php if ( ! empty($value['description']) ) : ?>
              <p class="description"><?php echo __($value['description']); ?></p>
            <?php endif; ?>
          </fieldset>
        </td>
      </tr>
      <?php
      $html = ob_get_contents();
      ob_end_clean();
      return $html;
    }
    public function validate_dimensions_field( $key, $value ) {
      $values = wp_json_encode($value);
      return $values;
    }

    public function generate_label_name_html( $key, $value ) {
      $field_key = $this->get_field_key($key);
      $field_class = (isset($value['class'])) ? $value['class'] : '';
      $field_values = array();

      if ( $this->get_option($key) !== '' ) {
        $field_values = $this->get_option($key);
        if ( is_string($field_values) ) {
          $field_values = json_decode($this->get_option($key), true);
        }
      }

      $avalable_methods = OmnivaLt_Shipmethod_Helper::get_available_shipping_methods($this->omnivalt_configs);
      $splited_methods = array_chunk($avalable_methods, 3, true);

      ob_start();
      ?>
      <tr valign="top">
        <th scope="row" class="titledesc">
          <label><?php echo esc_html($value['title']); ?></label>
        </th>
        <td class="forminp">
          <fieldset class="field-custom_label <?php echo $field_class; ?>">
            <table>
              <?php foreach ( $splited_methods as $methods_row) : ?>
                <tr>
                  <?php foreach ( $methods_row as $method_key => $method_values ) : ?>
                    <th><?php echo $method_values['title']; ?></th>
                  <?php endforeach; ?>
                </tr>
                <tr>
                  <?php foreach ( $methods_row as $method_key => $method_values ) : ?>
                    <?php $current_value = (isset($field_values[$method_values['key']])) ? $field_values[$method_values['key']] : ""; ?>
                    <td>
                      <input type="text" name="<?php echo esc_html($field_key); ?>[<?php echo esc_html($method_values['key']); ?>]" value="<?php echo esc_html($current_value); ?>">
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </table>
            <p class="description"><?php echo $value['description']; ?></p>
          </fieldset>
        </td>
      </tr>
      <?php
      $html = ob_get_contents();
      ob_end_clean();
      return $html;
    }
    public function validate_label_name_field( $key, $value ) {
      $values = wp_json_encode($value);
      return $values;
    }

    public function generate_position_html( $key, $value ) {
      $field_key = $this->get_field_key($key);
      $field_class = (isset($value['class'])) ? $value['class'] : '';
      $field_values = array();

      if ( $this->get_option($key) !== '' ) {
        $field_values = $this->get_option($key);
        if ( is_string($field_values) ) {
          $field_values = json_decode($this->get_option($key), true);
        }
      }

      $avalable_methods = OmnivaLt_Shipmethod_Helper::get_available_shipping_methods($this->omnivalt_configs);
      $splited_methods = array_chunk($avalable_methods, 5, true);

      ob_start();
      ?>
      <tr valign="top">
        <th scope="row" class="titledesc">
          <label><?php echo esc_html($value['title']); ?></label>
        </th>
        <td class="forminp">
          <fieldset class="field-position <?php echo $field_class; ?>">
            <table>
              <?php foreach ( $splited_methods as $methods_row) : ?>
                <tr>
                  <?php foreach ( $methods_row as $method_key => $method_values ) : ?>
                    <th><?php echo $method_values['title']; ?></th>
                  <?php endforeach; ?>
                </tr>
                <tr>
                  <?php foreach ( $methods_row as $method_key => $method_values ) : ?>
                    <?php $current_value = (isset($field_values[$method_values['key']])) ? $field_values[$method_values['key']] : ""; ?>
                    <td>
                      <input type="number" name="<?php echo esc_html($field_key); ?>[<?php echo esc_html($method_values['key']); ?>]" value="<?php echo esc_html($current_value); ?>" min="0" max="90" step="1">
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </table>
            <p class="description"><?php echo $value['description']; ?></p>
          </fieldset>
        </td>
      </tr>
      <?php
      $html = ob_get_contents();
      ob_end_clean();
      return $html;
    }
    public function validate_position_field( $key, $value ) {
      $values = wp_json_encode($value);
      return $values;
    }

    /**
     * Get categories for "restricted_categories" field
     */
    public function omnivalt_get_categories()
    {
      $cats = $this->get_categories_hierarchy();
      $result = [];
          
      foreach ( $cats as $item ) {
        $this->create_categories_list('', $item, $result);
      }

      return $result;
    }

    /**
     * Makes a list of categories to select from in settings page. array(lowest cat id => full cat path name)
     */
    private function create_categories_list($prefix, $data, &$results)
    {
      if ( $prefix ) {
        $prefix = $prefix . ' &gt; ';
        $results[$data->term_id] = $prefix . $data->name;
      }
      if ( ! $data->children ) {
        $results[$data->term_id] = $prefix . $data->name;

        return true;
      }

      foreach ( $data->children as $child ) {
        $this->create_categories_list($prefix . $data->name, $child, $results);
      }
    }

    private function get_categories_hierarchy( $parent = 0 )
    {
      $taxonomy = 'product_cat';
      $orderby = 'name';
      $hide_empty = 0;

      $args = array(
        'taxonomy'   => $taxonomy,
        'parent'     => $parent,
        'orderby'    => $orderby,
        'hide_empty' => $hide_empty,
      );

      $cats = get_categories( $args );
      $children = array();

      if ( is_wp_error($cats) ) {
        OmnivaLt_Debug::log_error($cats->get_error_message());
        $cats = array();
      }

      foreach( $cats as $cat ) {
        $cat->children = $this->get_categories_hierarchy( $cat->term_id );
        $children[ $cat->term_id ] = $cat;
      }

      return $children;
    }

    /**
     * Get shipping classes for "restricted_shipclass" field
     */
    public function omnivalt_get_shipping_classes()
    {
      $result = [];
      $shipping_classes = $this->get_all_shipping_classes();
          
      foreach ( $shipping_classes as $item ) {
        $this->create_categories_list('', $item, $result);
      }

      return $result;
    }

    private function get_all_shipping_classes()
    {
      $taxonomy = 'product_shipping_class';
      $orderby = 'name';
      $hide_empty = 0;

      $args = array(
        'taxonomy'   => $taxonomy,
        'orderby'    => $orderby,
        'hide_empty' => $hide_empty,
      );

      $shipping_classes = get_terms($args);

      if ( is_wp_error($shipping_classes) ) {
        OmnivaLt_Debug::log_error($shipping_classes->get_error_message());
        return (object) array();
      }

      return $shipping_classes;
    }

    public function generate_debug_window_html( $key, $value ) {
      $field_class = (isset($value['class'])) ? $value['class'] : '';
      $files = (isset($value['files'])) ? $value['files'] : array();
      $files_dir = OmnivaLt_Debug::$_debug_dir;

      ob_start();
      ?>
      <tr class="omniva-debugview" valign="top">
        <th scope="row" class="titledesc"></th>
        <td class="forminp">
          <fieldset class="field-debug <?php echo $field_class; ?>">
            <span class="title"><?php echo esc_html($value['title']); ?></span>
            <?php if ( empty($files) ) : ?>
              <textarea readonly rows="2" style="width:100%">- <?php echo __('Debug files still not created','omnivalt'); ?> -</textarea>
            <?php else : ?>
              <?php foreach ( $files as $file_data ) : ?>
                <div class="debug-row">
                  <?php
                  $file_path = $files_dir . $file_data['name'];
                  $file = fopen($file_path, 'r');
                  if ( filesize($file_path) > 0 ) {
                    $file_content = fread($file,filesize($file_path));
                  } else {
                    $file_content = '- ' . __('File is empty','omnivalt') . ' -';
                  }
                  fclose($file);
                  if ( ! empty($file_data['day']) ) {
                    $date = date("Y-m-d H:i:s", strtotime($file_data['day'] . ' ' . $file_data['time']));
                  } else {
                    $date = __('Date unknown', 'omnivalt');
                  }
                  ?>
                  <span class="date"><?php echo esc_html($value['subtitle']) . ' ' . esc_html($date); ?></span>
                  <textarea readonly rows="11" style="width:100%;display:none;"><?php echo $file_content; ?></textarea>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </fieldset>
        </td>
      </tr>
      <?php
      $html = ob_get_contents();
      ob_end_clean();
      return $html;
    }

    /**
     * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping($package = array())
    {
      $weight = 0;
      $cost = 0;
      $country = $package["destination"]["country"];

      global $woocommerce;
      $cart_amount = $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total;

      $products_for_dim = array();
      foreach ( $package['contents'] as $item_id => $values ) {
        $_product = $values['data'];
        if ( $_product->get_weight() ) {
          $weight = $weight + $_product->get_weight() * $values['quantity'];
        }
        for ( $i=0;$i<$values['quantity'];$i++ ) {
          array_push($products_for_dim, $_product);
        }
      }

      $weight = wc_get_weight($weight, 'kg');

      $prices_key = (array_key_exists($country, $this->omnivalt_configs['shipping_params'])) ? 'prices_' . $country : 'prices_LT';
      $prices = (isset($this->settings[$prices_key])) ? json_decode($this->settings[$prices_key]) : array();

      $this->add_shipping_rate('pt', $products_for_dim, $weight, $country, $cart_amount, $prices, $package);
      $this->add_shipping_rate('c', false, $weight, $country, $cart_amount, $prices, $package);
      $this->add_shipping_rate('cp', false, $weight, $country, $cart_amount, $prices, $package);
      $this->add_shipping_rate('pc', false, $weight, $country, $cart_amount, $prices, $package);
      $this->add_shipping_rate('po', false, $weight, $country, $cart_amount, $prices, $package);
    }

    private function add_shipping_rate($rate_key, $products_for_dim, $weight, $country, $cart_amount, $prices, $package)
    {
      $method_params = OmnivaLt_Shipmethod_Helper::get_current_method_params($this->omnivalt_configs['method_params'], $rate_key);
      
      $check_restrictions = OmnivaLt_Shipmethod_Helper::check_restrictions($this->settings, $rate_key, $weight, $products_for_dim);

      if ( $this->settings['method_' . $rate_key] == 'yes' && $check_restrictions ) {
        $show = true;
        $amount_data = OmnivaLt_Shipmethod_Helper::get_amount($rate_key, $prices, $weight, $cart_amount);
        $amount = $amount_data['amount'];
        $meta_data = $amount_data['meta_data'];

        if ( empty($amount) && $amount !== 0 && $amount !== '0' ) {
          $show = false;
        }
        if ( ! isset($prices->{$rate_key . '_enable'}) ) {
          $show = false;
        }

        $amount = OmnivaLt_Shipmethod_Helper::check_amount_free($rate_key, $prices, $amount, $cart_amount);
        $amount = OmnivaLt_Shipmethod_Helper::check_coupon($rate_key, $prices, $amount, $package['applied_coupons']);

        $rate_name = $method_params['title'];
        $show_prefix_on = array('classic', 'full');
        if ( ! isset($this->settings['label_design']) || (isset($this->settings['label_design']) && in_array($this->settings['label_design'], $show_prefix_on)) ) {
          $rate_name = 'Omniva ' . strtolower($rate_name);
        }
        if ( ! empty($this->settings['custom_label']) ) {
          $custom_labels = json_decode($this->settings['custom_label']);
          $rate_name = (!empty($custom_labels->{$rate_key})) ? $custom_labels->{$rate_key} : $rate_name;
        }

        $rate = array(
          'id' => 'omnivalt_' . $rate_key,
          'label' => $rate_name,
          'cost' => $amount,
          'meta_data' => $meta_data,
        );

        if ( ! OmnivaLt_Shipmethod_Helper::is_rate_allowed($rate_key, $country, $this->settings) ) {
          $show = false;
        }

        if ($show) {
          $this->add_rate($rate);
        }
      }
    }

    function printBulkManifests($orderIds = false)
    {
      OmnivaLt_Core::load_vendors(array('tcpdf'));

      if ( ! is_array($orderIds) ) {
        $orderIds = array($orderIds);
      }

      $object = '';
      $configs = OmnivaLt_Core::get_configs();

      $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

      $pdf->setPrintHeader(false);
      $pdf->setPrintFooter(false);
      $pdf->AddPage();
      $order_table = '';
      $count = 0;
      if ( is_array($orderIds) ) {
        foreach ( $orderIds as $orderId ) {
          $order = get_post((int) $orderId);
          $wc_order = wc_get_order((int) $orderId);
          $send_method = "";
          
          foreach ( $wc_order->get_items('shipping') as $item_id => $shipping_item_obj ) {
            $send_method = $shipping_item_obj->get_method_id();
          }
          if ($send_method == 'omnivalt') {
            $send_method = get_post_meta($orderId, $configs['meta_keys']['method'], true);
          }

          $is_omniva = false;
          foreach ( $configs['method_params'] as $method_key => $method_values ) {
            if ( $send_method == 'omnivalt_' . $method_values['key'] ) {
              $is_omniva = true;
            }
          }
          if ( ! $is_omniva ) {
            OmnivaLt_Helper::add_msg($orderId . ' - ' . __('Shipping method is not Omniva', 'omnivalt'), 'error');
            continue;
          }

          $track_numer = get_post_meta($orderId, $configs['meta_keys']['barcode'], true);
          if ( $track_numer == '' ) {
            $status = $this->omnivalt_api->get_tracking_number($orderId);
            if ( ! empty($status['debug']) ) {
              OmnivaLt_Helper::add_msg('<b>OMNIVA RESPONSE DEBUG:</b><br/><pre style="white-space:pre-wrap;">' . htmlspecialchars($status['debug']) . '</pre>', 'notice');
            }

            if ($status['status']) {
              update_post_meta($orderId, $configs['meta_keys']['barcode'], $status['barcodes'][0]);
              $track_numer = $status['barcodes'][0];

              $label_status = $this->omnivalt_api->get_shipment_labels($status['barcodes'], $orderId);
              
              if ( ! $label_status['status'] ) {
                update_post_meta($orderId, $configs['meta_keys']['error'], $label_status['msg']);
                OmnivaLt_Helper::add_msg($orderId . ' - ' . $label_status['msg'], 'error');
                continue;
              }
            } else {
              OmnivaLt_Helper::add_msg($orderId . ' - ' . $status['msg'], 'error');
              continue;
            }
          }

          /*if (get_post_meta($orderId, $configs['meta_keys']['manifest_date'],true)) {
            OmnivaLt_Helper::add_msg($orderId . ' - ' . __('Manifest already generated','omnivalt'), 'error');
            continue;
          }*/

          update_post_meta($orderId, $configs['meta_keys']['manifest_date'], current_time('Y-m-d H:i:s'));
          
          $pt_address = '';
          if ( $send_method == 'omnivalt_pt' || $send_method == 'omnivalt_po' ) {
            $pt_address = $this->get_terminal_address($terminal_id = get_post_meta($orderId, $configs['meta_keys']['terminal_id'], true));
          }

          $client_address = get_post_meta($orderId, '_shipping_address_index', true);
          if ($pt_address != '') {
            $client_address = '';
          }

          $count++;
          $cart_weight = get_post_meta($orderId, '_cart_weight', true);
          $weight_unit = get_option('woocommerce_weight_unit');
          if ( $weight_unit != 'kg' ) {
            $cart_weight = wc_get_weight($cart_weight, 'kg', $weight_unit);
          }
          $cell_shipment_number = '<td width="110">' . $track_numer . '</td>';
          if ( $this->settings['manifest_show_barcode'] === 'yes' ) {
            $barcode_params = $pdf->serializeTCPDFtagParameters(array($track_numer, 'C128', '', '', 25, 6, 0.4, array('position'=>'C', 'border'=>false, 'padding'=>0, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N'));
            $cell_shipment_number = '<td width="110" style="line-height: 50%;"><tcpdf method="write1DBarcode" params="' . $barcode_params . '" /></td>';
          }
          $order_table .= '<tr><td width = "30" align="right">' . $count . '.</td>' . $cell_shipment_number . '<td width = "60">' . current_time('Y-m-d') . '</td><td width = "40">1</td><td width = "60">' . $cart_weight . '</td><td width = "">' . $client_address . $pt_address . '</td></tr>';

          //make order shipped after creating manifest
          /*
            $history = new OrderHistory();
            $history->id_order = (int)$orderId;
            $history->id_employee = (int)$cookie->id_employee;
            $history->changeIdOrderState((int)Configuration::get('PS_OS_SHIPPING'), $order);
            $history->addWithEmail(true);*/
        }
      }

      $pdf->SetFont('freeserif', '', 14);
      $shop_addr = '<table cellspacing="0" cellpadding="1" border="0"><tr><td>' . current_time('Y-m-d H:i:s') . '</td><td>' . _x('Sender address', 'Manifest', 'omnivalt') . ':<br/>' . $this->settings['shop_name'] . '<br/>' . $this->settings['shop_address'] . ', ' . $this->settings['shop_postcode'] . '<br/>' . $this->settings['shop_city'] . ', ' . $this->settings['shop_countrycode'] . '<br/></td></tr></table>';

      $pdf->writeHTML($shop_addr, true, false, false, false, '');
      $tbl = '
        <table cellspacing="0" cellpadding="4" border="1" width="100%">
          <thead>
            <tr>
              <th width="30" align="right">' . _x('No.', 'Manifest', 'omnivalt') . '</th>
              <th width="110">' . _x('Shipment number', 'Manifest', 'omnivalt') . '</th>
              <th width="60">' . _x('Date', 'Manifest', 'omnivalt') . '</th>
              <th width="40">' . _x('Quantity', 'Manifest', 'omnivalt') . '</th>
              <th width="60">' . _x('Weight (kg)', 'Manifest', 'omnivalt') . '</th>
              <th width="">' . _x("Recipient's address", 'Manifest', 'omnivalt') . '</th>
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
      } else {
        // $this->call_omniva();
      }
      $pdf->SetFont('freeserif', '', 9);
      $pdf->writeHTML($tbl, true, false, false, false, '');
      $pdf->SetFont('freeserif', '', 14);
      $sign = _x("Courier name, surname, signature", 'Manifest', 'omnivalt') . ' ________________________________________________<br/><br/>';
      $sign .= _x("Sender name, surname, signature", 'Manifest', 'omnivalt') . ' ________________________________________________';
      $pdf->writeHTML($sign, true, false, false, false, '');
      $pdf->Output('Omnivalt_manifest.pdf', 'D');
    }

    function get_terminal_address($terminal_id)
    {
      $terminals_json_file_dir = OMNIVALT_DIR . '/' . "locations.json";
      $terminals_file = fopen($terminals_json_file_dir, "r");
      $terminals = fread($terminals_file, filesize($terminals_json_file_dir) + 10);
      fclose($terminals_file);
      $terminals = json_decode($terminals, true);
      $parcel_terminals = '';
      if ( is_array($terminals) && $terminal_id ) {
        foreach ( $terminals as $terminal ) {
          if ( $terminal['ZIP'] == $terminal_id ) {
            return $terminal['NAME'] . ', ' . $terminal['A1_NAME'] . ', ' . $terminal['A0_NAME'];
          }
        }
      }
      return '';
    }
  }
}
