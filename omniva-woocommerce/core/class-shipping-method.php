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
    private $omnivalt_api_int;
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
      $this->omnivalt_api_int = new OmnivaLt_Api_International();
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
      $this->countries = array_unique(array_merge(array_keys($this->destinations_countries), $this->omnivalt_api_int->get_all_available_countries()));

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
        $countries_options[$country_code] = $country_code . ' - ' . OmnivaLt_Wc::get_country_name($country_code);
      }

      $active_omx = ($this->omnivalt_configs['api']['type'] === 'omx');
      $feature_not_available_txt = '<br/><b>' . __('This feature is not working yet', 'omnivalt') . '.</b>';
      $feature_not_allowed_txt = '<br/><b>' . __('This feature is not allowed', 'omnivalt') . '.</b>';
      
      $fields = array(
        'enabled' => array(
          'title' => __('Enable', 'omnivalt'),
          'type' => 'checkbox',
          'description' => sprintf(__('Activate this plugin and allow to use %s methods', 'omnivalt'), $this->method_title),
          //'desc_tip' => true,
          'default' => 'yes',
        )
      );
      $fields['hr_api'] = array(
        'type' => 'hr',
        'title' => __('API', 'omnivalt'),
      );
      if ( ! $active_omx ) {
        $fields['api_url'] = array(
          'title' => __('API URL', 'omnivalt'),
          'type' => 'text',
          'default' => 'https://edixml.post.ee',
          'description' => __('Change only if want use custom API URL.', 'omnivalt') . ' ' . sprintf(__('Default Omniva API URL is %s', 'omnivalt'),'<code>https://edixml.post.ee</code>'),
        );
      }
      $fields['api_user'] = array(
        'title' => __('API user', 'omnivalt'),
        'type' => 'text',
        'description' => __('Please contact Omniva for API access codes.', 'omnivalt'),
      );
      $fields['api_pass'] = array(
        'title' => __('API password', 'omnivalt'),
        'type' => 'password',
      );
      $fields['api_country'] = array(
        'title' => __('API account country', 'omnivalt'),
        'type'    => 'select',
        'options' => array(
          'LT' => OmnivaLt_Wc::get_country_name('LT'),
          'LV' => OmnivaLt_Wc::get_country_name('LV'),
          'EE' => OmnivaLt_Wc::get_country_name('EE'),
        ),
        'default' => 'LT',
        'description' => __('Choose the country of Omniva support from which you received API logins.', 'omnivalt'),
      );
      $fields['hr_shop'] = array(
        'type' => 'hr',
        'title' => __('Sender information', 'omnivalt'),
      );
      $fields['company'] = array(
        'title' => __('Company name', 'omnivalt'),
        'type' => 'text',
      );
      $fields['bank_account'] = array(
        'title' => __('Bank account', 'omnivalt'),
        'type' => 'text',
      );
      $fields['shop_name'] = array(
        'title' => __('Shop name', 'omnivalt'),
        'type' => 'text',
      );
      $fields['shop_city'] = array(
        'title' => __('Shop city', 'omnivalt'),
        'type' => 'text',
      );
      $fields['shop_address'] = array(
        'title' => __('Shop address', 'omnivalt'),
        'type' => 'text',
      );
      $fields['shop_postcode'] = array(
        'title' => __('Shop postcode', 'omnivalt'),
        'type' => 'text',
        'description' => sprintf(__('Example for Latvia: %1$s. Example for other countries: %2$s.', 'omnivalt'), '<code>LV-0123</code>', '<code>01234</code>'),
      );
      $fields['shop_countrycode'] = array(
        'title' => __('Shop country code', 'omnivalt'),
        'type'    => 'select',
        'class' => 'checkout-style pickup-point',
        'options' => $countries_options,
        'default' => 'LT',
      );
      $fields['shop_phone'] = array(
        'title' => __('Shop phone number', 'omnivalt'),
        'type' => 'text',
        'description' => __('The value of this field is used only if the mobile number is not entered.', 'omnivalt'),
      );
      $fields['shop_mobile'] = array(
        'title' => __('Shop mobile number', 'omnivalt'),
        'type' => 'text',
        'description' => sprintf(__('Required mobile phone number if want use service "%s".', 'omnivalt'), $this->omnivalt_configs['additional_services']['delivery_confirmation_sms']['title']),
      );
      $fields['shop_email'] = array(
        'title' => __('Shop email', 'omnivalt'),
        'type' => 'text',
      );
      $fields['pick_up_start'] = array(
        'title' => __('Pick up time start', 'omnivalt'),
        'type' => 'text',
        'placeholder' => '08:00',
        'description' => sprintf(__('Allowed formats: %1$s. Default time is %2$s, if incorrect value is entered or field is empty.', 'omnivalt'),'<i>07:00, 7:00, 7</i>', '08:00'),
      );
      $fields['pick_up_end'] = array(
        'title' => __('Pick up time end', 'omnivalt'),
        'type' => 'text',
        'placeholder' => '17:00',
        'description' => sprintf(__('Allowed formats: %1$s. Default time is %2$s, if incorrect value is entered or field is empty.', 'omnivalt'),'<i>09:00, 9:00, 9</i>', '17:00'),
      );
      $fields['send_off'] = array(
        'title' => __('Send off type', 'omnivalt'),
        'type' => 'select',
        'description' => __('Send from store type.', 'omnivalt'),
        'options' => array(
          'pt' => __('Parcel terminal', 'omnivalt'),
          'c' => __('Courier', 'omnivalt'),
          'po' => __('Post office', 'omnivalt'),
          'lc' => __('Logistics center', 'omnivalt'),
        )
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
          'custom_attributes' => array(
            'data-method' => $ship_method,
          ),
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
        $fields['prices_' . $country_code] = array(
          'type' => 'prices_box',
          'lang' => $country_code,
        );
      }
      foreach ( $this->omnivalt_api_int->get_available_packages() as $package_key => $package_countries ) {
        $fields['prices_' . $package_key] = array(
          'type' => 'prices_box',
          'plan' => $package_key,
          'regions' => array_keys($package_countries),
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
      $fields['verify_phone'] = array(
        'title' => __('Check phone format', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('On the checkout page, check if the entered mobile phone number format is correct.', 'omnivalt'),
        'default' => '',
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
      $fields['custom_label'] = array( // The parameter is no longer used. Need delete in 2026.
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
          //'sms' => __('Add to SMS', 'omnivalt'),
          //'email' => __('Add to email', 'omnivalt'),
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
      $fields['hr_pickup'] = array(
        'type' => 'hr',
        'title' => __('Shipment pickup', 'omnivalt'),
      );
      $field_custom_attributes = array(
        'maxlength' => 120,
      );
      if (!$active_omx) $field_custom_attributes['disabled'] = true;
      $fields['pickup_comment'] = array(
        'title' => __('Comment to the courier', 'omnivalt'),
        'type' => 'text',
        'description' => __('A comment that will be sent with the courier call request', 'omnivalt') . ((!$active_omx) ? '.' . $feature_not_allowed_txt : ''),
        'custom_attributes' => $field_custom_attributes,
      );
      $fields['hr_debug'] = array(
        'type' => 'hr',
        'title' => __('Debug', 'omnivalt'),
      );
      $fields['debug_mode'] = array(
        'title' => __('Enable debug mode', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Enable request and response logging.', 'omnivalt') . ' ' . sprintf(__('Log files are stored for %d days.', 'omnivalt'), $this->omnivalt_configs['debug']['delete_after']) . '<br/>' . __('Allow to use other debug parameters.', 'omnivalt'),
        'default' => ''
      );
      $fields['debug_notice'] = array(
        'title' => __('Show debug data in WP notice', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Display debug information via WP notice at the top of the web page after some action', 'omnivalt'),
        'default' => '',
        'class' => 'omniva_debug'
      );
      $fields['debug_front_js'] = array(
        'title' => __('Show debug data in Checkout', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Display Javascript debug information in the console of the Checkout page', 'omnivalt'),
        'default' => '',
        'class' => 'omniva_debug'
      );
      $fields['debug_front_post_data'] = array(
        'title' => __('Log the received Checkout data', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Save the data in the logs, that is received during the creation of the Order. Sensitive information will not be stored. Intended for use when there is a problem that the delivery method of Omniva is not recognized or the parcel terminal is not added to the Order.', 'omnivalt'),
        'default' => '',
        'class' => 'omniva_debug'
      );
      $fields['debug_develop_mode'] = array(
        'title' => __('Enable development mode', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Enable testing mode for features that provide this capability', 'omnivalt') . '.<br/><b>' . __('It is not recommended to use on a website that is already LIVE, as the website data related to the Omniva plugin may be changed', 'omnivalt') . '.</b>',
        'default' => '',
        'class' => 'omniva_debug omniva_dev',
      );
      $fields['debugview_request'] = array(
        'type' => 'debug_window',
        'files' => OmnivaLt_Debug::get_all_files(),
        'title-main' => __('Logs list', 'omnivalt'),
        'title' => __('Logged communications with API', 'omnivalt'),
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
      $coupons = OmnivaLt_Wc::get_coupons(OmnivaLt_Filters::settings_coupon_args());
      if ( isset($value['lang']) ) {
        $shipping_country = new OmnivaLt_Shipping_Method_Country($value['lang'], $key);
        $shipping_methods = $shipping_country->getMethods();

        ob_start();
        ?>
        <tr class="row-prices" valign="top">
          <td colspan="2">
            <div class="prices_box" data-country="<?php echo $value['lang']; ?>">
              <div class="pb-lang">
                <img src="<?php echo $shipping_country->getImgUrl(); ?>" alt="[<?php echo $value['lang']; ?>]">
                <span><?php echo $shipping_country->getTitle(); ?></span>
              </div>
              <div class="pb-content">
                <?php foreach ($shipping_methods as $method_key => $method) : ?>
                  <?php
                  if ( empty($method['fields']) ) continue;
                  
                  $method_fields = $method['fields'];
                  $field_builder = new OmnivaLt_Shipping_Method_Field($method['key'], $value['lang']);

                  $params = array(
                    'type' => $method_key,
                    'box_key' => $box_key,
                    'title' => $method['title'],
                    'enable' => array(
                      'id' => $this->get_field_key($field_builder->buildIdFull('enable')),
                      'name' => $field_builder->buildIdPrefix('enable'),
                      'checked' => ($method_fields['enable']) ? 'checked' : '',
                      'class' => $field_builder->buildIdPrefix('enable'),
                      'title' => sprintf(__('Enable %s','omnivalt'), strtolower($method['title']))
                    ),
                    'prices' => array(
                      'type' => $this->omnivalt_build_price_field($field_builder->buildIdFull('price_type'), $method_fields['price_type']),
                      'type_name' => $field_builder->buildIdPrefix('price_type'),
                      'single' => $this->omnivalt_build_price_field($field_builder->buildIdFull('price'), $method_fields['price_single']),
                      'single_name' => $field_builder->buildIdPrefix('price_single'),
                      'weight' => $this->omnivalt_build_price_field($field_builder->buildIdFull('price_by_weight'), $method_fields['price_by_weight']),
                      'weight_name' => $field_builder->buildIdPrefix('price_by_weight'),
                      'amount' => $this->omnivalt_build_price_field($field_builder->buildIdFull('price_by_amount'), $method_fields['price_by_amount']),
                      'amount_name' => $field_builder->buildIdPrefix('price_by_amount'),
                      'free_enable' => $this->omnivalt_build_price_field($field_builder->buildIdFull('enable_free_from'), $method_fields['enable_free_from']),
                      'free_enable_name' => $field_builder->buildIdPrefix('enable_free_from'),
                      'free_enable_class' => $field_builder->buildIdPrefix('enable_free'),
                      'free' => $this->omnivalt_build_price_field($field_builder->buildIdFull('free_from'), $method_fields['free_from']),
                      'free_name' => $field_builder->buildIdPrefix('free_from'),
                      'coupon' => $this->omnivalt_build_price_field($field_builder->buildIdFull('coupon'), $method_fields['coupon']),
                      'coupon_name' => $field_builder->buildIdPrefix('coupon'),
                      'coupon_enable' => $this->omnivalt_build_price_field($field_builder->buildIdFull('enable_coupon'), $method_fields['enable_coupon']),
                      'coupon_enable_name' => $field_builder->buildIdPrefix('enable_coupon'),
                      'coupon_enable_class' => $field_builder->buildIdPrefix('enable_coupon'),
                    ),
                    'data' => array(
                      'coupons' => $coupons,
                    ),
                    'other' => array(
                      'label' => $this->omnivalt_build_price_field($field_builder->buildIdFull('label'), $method_fields['label']),
                      'label_name' => $field_builder->buildIdPrefix('label'),
                      'desc' => $this->omnivalt_build_price_field($field_builder->buildIdFull('description'), $method_fields['description']),
                      'desc_name' => $field_builder->buildIdPrefix('description'),
                    )
                  );
                  if ( array_key_exists('price_by_boxsize', $method_fields) ) {
                    $params['prices']['boxsize'] = $this->omnivalt_build_price_field($field_builder->buildIdFull('price_by_boxsize'), $method_fields['price_by_boxsize']);
                    $params['prices']['boxsize_name'] = $field_builder->buildIdPrefix('price_by_boxsize');
                  }
                  echo $shipping_country->setCurrentMethodKey($method_key)->buildSettingsBlock($params);
                  ?>
                <?php endforeach; ?>
              </div>
            </div>
          </td>
        </tr>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
      } else if ( isset($value['plan']) ) {
        $international_data = array(
          'key' => $value['plan'],
          'title' => $this->omnivalt_api_int->get_package_title($value['plan']),
        );
        $shipping_international = new OmnivaLt_Shipping_Method_International($international_data, $key);
        $shipping_methods = $shipping_international->getMethods();

        ob_start();
        ?>
        <tr class="row-prices" valign="top">
          <td colspan="2">
            <div class="prices_box" data-plan="<?php echo $value['plan']; ?>">
              <div class="pb-lang">
                <img src="<?php echo $shipping_international->getImgUrl(); ?>" alt="[<?php echo $value['plan']; ?>]">
                <span><?php echo __('International','omnivalt') . ': ' . $shipping_international->getTitle(); ?></span>
              </div>
              <div class="pb-content">
                <?php foreach ( $shipping_methods as $method_key => $method ) : ?>
                  <?php
                  if ( empty($method['fields']) ) continue;
                  
                  $method_fields = $method['fields'];
                  $field_builder = new OmnivaLt_Shipping_Method_Field($method_key, $value['plan']);
                  $region_title = $this->omnivalt_api_int->get_region_title($method_key);
                  
                  $params = array(
                    'type' => $method_key,
                    'box_key' => $box_key,
                    'title' => $region_title,
                    'cant_disable' => true,
                    'enable' => array(
                      'id' => $this->get_field_key($field_builder->buildIdFull('enable')),
                      'name' => $field_builder->buildIdPrefix('enable'),
                      'checked' => ($method_fields['enable']) ? 'checked' : '',
                      'class' => $field_builder->buildIdPrefix('enable'),
                      'title' => sprintf(__('Enable %s','omnivalt'), $region_title)
                    ),
                    'prices' => array(
                      'single' => $this->omnivalt_build_price_field($field_builder->buildIdFull('price'), $method_fields['price_single']),
                      'single_name' => $field_builder->buildIdPrefix('price_single'),
                      'free_enable' => $this->omnivalt_build_price_field($field_builder->buildIdFull('enable_free_from'), $method_fields['enable_free_from']),
                      'free_enable_name' => $field_builder->buildIdPrefix('enable_free_from'),
                      'free_enable_class' => $field_builder->buildIdPrefix('enable_free'),
                      'free' => $this->omnivalt_build_price_field($field_builder->buildIdFull('free_from'), $method_fields['free_from']),
                      'free_name' => $field_builder->buildIdPrefix('free_from'),
                      'coupon' => $this->omnivalt_build_price_field($field_builder->buildIdFull('coupon'), $method_fields['coupon']),
                      'coupon_name' => $field_builder->buildIdPrefix('coupon'),
                      'coupon_enable' => $this->omnivalt_build_price_field($field_builder->buildIdFull('enable_coupon'), $method_fields['enable_coupon']),
                      'coupon_enable_name' => $field_builder->buildIdPrefix('enable_coupon'),
                      'coupon_enable_class' => $field_builder->buildIdPrefix('enable_coupon'),
                    ),
                    'data' => array(
                      'coupons' => $coupons,
                    ),
                    'other' => array(
                      'label' => $this->omnivalt_build_price_field($field_builder->buildIdFull('label'), $method_fields['label']),
                      'label_name' => $field_builder->buildIdPrefix('label'),
                      'desc' => $this->omnivalt_build_price_field($field_builder->buildIdFull('description'), $method_fields['description']),
                      'desc_name' => $field_builder->buildIdPrefix('description'),
                    ),
                  );
                  echo $shipping_international->setCurrentMethodKey($value['plan'])->buildSettingsBlock($params);
                  ?>
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

    private function omnivalt_build_price_field( $field_key, $field_value ) {
      return array(
        'id' => $field_key,
        'key' => $this->get_field_key($field_key),
        'value' => $field_value,
      );
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
      <tr valign="top" style="display:none;">
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
                      <input type="text" name="<?php echo esc_html($field_key); ?>[<?php echo esc_html($method_values['key']); ?>]" value="">
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
    private function create_categories_list( $prefix, $data, &$results )
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
        'orderby'    => $orderby,
        'hide_empty' => $hide_empty,
      );

      $shipping_classes = OmnivaLt_Compatibility::get_terms($taxonomy, $args);

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
      $files_subtitle = (isset($value['subtitle'])) ? $value['subtitle'] : '';
      $main_title = (isset($value['title-main'])) ? $value['title-main'] : '';

      ob_start();
      ?>
      <tr class="omniva-debugview" valign="top">
        <th scope="row" class="titledesc">
          <label><?php echo esc_html($main_title); ?></label>
        </th>
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
                  $subtitle = '';
                  if ( empty($files_subtitle) ) {
                    $all_subtitles = array(
                      'request' => __('Request', 'omnivalt'),
                      'response' => __('Response', 'omnivalt'),
                    );
                    foreach ( $all_subtitles as $subtitle_key => $subtitle_value ) {
                      if ( str_contains($file_data['name'], $subtitle_key) ) {
                        if ( ! empty($subtitle) ) $subtitle .= '/';
                        $subtitle .= $subtitle_value;
                      }
                    }
                  }
                  ?>
                  <span class="date"><?php echo trim(esc_html($subtitle) . ' ' . esc_html($date)); ?></span>
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
    public function calculate_shipping( $package = array() )
    {
      $country = $package["destination"]["country"];
      $cart = OmnivaLt_Wc::get_cart();
      if ( empty($cart) || empty($cart->cart_contents) ) {
        return;
      }
      $cart_amount = $cart->cart_contents_total + $cart->tax_total;
      $products = $this->get_splited_cart_products($package['contents']);

      $prices_key = (array_key_exists($country, $this->omnivalt_configs['shipping_params'])) ? 'prices_' . $country : 'prices_LT';
      $shipping_country = new OmnivaLt_Shipping_Method_Country($country, $prices_key);
      foreach ( $shipping_country->getMethods() as $method_key => $method ) {
        if ( ! OmnivaLt_Shipmethod_Helper::is_rate_allowed($method['key'], $country, $this->settings) || $this->settings['method_' . $method['key']] != 'yes' ) {
          continue;
        }
        $shipping_country->setCurrentMethodKey($method_key);
        $this->add_shipping_rate($package, $country, $shipping_country, $cart_amount);
      }

      foreach ( $this->omnivalt_api_int->get_available_packages() as $package_key => $package_zones ) {
        foreach ( $package_zones as $zone => $zone_countries ) {
          if ( in_array($country, $zone_countries) ) {
            $prices_key = 'prices_' . $package_key;
            $international_data = array(
              'key' => $package_key,
              'title' => $this->omnivalt_api_int->get_package_title($package_key),
              'country' => $country,
            );
            $shipping_international = new OmnivaLt_Shipping_Method_International($international_data, $prices_key);
            $shipping_international->setCurrentMethodKey($zone);
            $shipping_international->setCartProducts($products);
            if ( ! $shipping_international->ifServiceAvaible() ) {
              continue;
            }
            $this->add_shipping_rate($package, $country, $shipping_international, $cart_amount);
          }
        }
      }
    }

    private function add_shipping_rate( $package, $country, $shipping_method, $cart_amount )
    {
      $weight = 0;
      $products_for_dim = array();
      $show = true;
      $method = $shipping_method->getCurrentMethod();
      $prices = $shipping_method->getSettings();

      if ( empty($method['key'])
        || ! $method['fields']['enable']
      ) {
        return;
      }
      
      foreach ( $package['contents'] as $item_id => $values ) {
        $product = $values['data'];
        if ( $product->get_weight() ) {
          $weight = $weight + $product->get_weight() * $values['quantity'];
        }
        for ( $i = 0; $i < $values['quantity']; $i++ ) {
          array_push($products_for_dim, $product);
        }
      }
      $weight = OmnivaLt_Wc::get_weight($weight, 'kg');

      $check_restrictions = OmnivaLt_Shipmethod_Helper::check_restrictions($this->settings, $method['key'], $weight, $products_for_dim);

      if ( ! $check_restrictions ) {
        return;
      }

      $amount_data = $shipping_method->getCartMethodAmount($weight, $cart_amount, false);
      $amount = $amount_data['amount'];

      if ( empty($amount) && $amount !== 0 && $amount !== '0' ) {
        return;
      }
      
      $meta_data = array(
        __('Carrier', 'omnivalt') => 'Omniva',
      );
      $meta_data = array_merge($meta_data, $amount_data['meta_data']);
      
      if ( $shipping_method->isCartMethodFreeByValue($cart_amount)
        || $shipping_method->isCartMethodFreeByCoupon($package['applied_coupons'])
      ) {
        $amount = 0.0;
      }

      $rate = array(
        'id' => 'omnivalt_' . $method['key'],
        'label' => OmnivaLt_Shipmethod_Helper::get_rate_name($method, $country, $this->settings),
        'cost' => $amount,
        'meta_data' => $meta_data,
      );

      $this->add_rate($rate);
    }

    private function get_splited_cart_products( $cart_contents )
    {
      $products = array();

      foreach ( $cart_contents as $item_id => $values ) {
        $product = $values['data'];
        for ( $i = 0; $i < $values['quantity']; $i++ ) {
          array_push($products, $product);
        }
      }

      return $products;
    }
  }
}
