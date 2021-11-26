<?php
if (!class_exists('Omnivalt_Shipping_Method')) {
  class Omnivalt_Shipping_Method extends WC_Shipping_Method
  {
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public $errors = array();

    private $omnivalt_shipping_params;
    private $omnivalt_text_variables;

    private $omnivalt_locations_url = 'https://www.omniva.ee/locations.json';

    private $omnivalt_api;

    public function __construct()
    {
      $this->id = 'omnivalt';
      $this->method_title = __('Omniva Shipping', 'omnivalt');
      $this->method_description = __('Shipping Method for Omniva', 'omnivalt');

      $this->omnivalt_api = new OmnivaLt_Api();
      $this->omnivalt_shipping_params = omnivalt_configs('shipping_params');
      $this->omnivalt_text_variables = omnivalt_configs('text_variables');

      // Availability & Countries
      $this->availability = 'including';
      $this->countries = array_keys($this->omnivalt_shipping_params);

      $this->init();

      $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
      $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Omnivalt Shipping', 'omnivalt');
    }

    private function omnivalt_convert_method_name_to_short($method_name, $reverse = false)
    {
      $asociations = array(
        'c' => 'courier',
        'cp' => 'courier_plus',
        'pt' => 'pickup',
        'po' => 'post',
      );

      foreach ($asociations as $key => $value) {
        if (!$reverse) {
          if ($method_name === $value) {
            return $key;
          }
        } else {
          if ($method_name === $key) {
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

      $this->title = $this->get_option('title');
      
      // Save settings in admin if you have any defined
      add_action('woocommerce_update_options_shipping_' . $this->id, array(
        $this,
        'process_admin_options'
      ));
      //$this->updateT();
    }

    public function updateT()
    {
      $fp = fopen(dirname(__file__) . '/' . "locations.json", "w");
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $this->omnivalt_locations_url);
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
      <h2><?php echo $this->method_title; ?></h2>
      <p><?php echo $this->method_description; ?></p>
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
      foreach ($this->omnivalt_shipping_params as $country_code => $ship_params) {
        $countries_options[$country_code] = $country_code . ' - ' . $ship_params['title'];
      }
      $fields = array(
        'enabled' => array(
          'title' => __('Enable', 'omnivalt'),
          'type' => 'checkbox',
          'description' => __('Enable this shipping.', 'omnivalt'),
          'default' => 'yes'
        ),
        'hr_api' => array(
          'type' => 'hr'
        ),
        'api_url' => array(
          'title' => __('Api URL', 'omnivalt'),
          'type' => 'text',
          'default' => 'https://edixml.post.ee',
          'description' => __('Change only if want use custom Api URL.', 'omnivalt') . ' ' . sprintf(__('Default is %s', 'omnivalt'),'<code>https://edixml.post.ee</code>'),
        ),
        'api_user' => array(
          'title' => __('Api user', 'omnivalt'),
          'type' => 'text',
        ),
        'api_pass' => array(
          'title' => __('Api user password', 'omnivalt'),
          'type' => 'password',
        ),
        'hr_shop' => array(
          'type' => 'hr'
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
            'c' => __('Courrier', 'omnivalt'),
            'po' => __('Post office', 'omnivalt'),
            'lc' => __('Logistics center', 'omnivalt'),
          )
        ),
      );
      $fields['hr_methods'] = array(
        'type' => 'hr'
      );
      $fields['method_pt'] = array(
        'title' => __('Parcel terminal', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Show parcel terminal method in checkout.', 'omnivalt')
      );
      $fields['method_c'] = array(
        'title' => __('Courier', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Show courier method in checkout.', 'omnivalt')
      );
      $fields['method_cp'] = array(
        'title' => __('Courier Plus', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Show courier plus method in checkout.', 'omnivalt') . ' ' . sprintf(__('Available only in %s.', 'omnivalt'), __('Estonia', 'omnivalt')),
      );
      $fields['method_po'] = array(
        'title' => __('Post office', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Show post office method in checkout.', 'omnivalt') . ' ' . sprintf(__('Available only in %s.', 'omnivalt'), __('Estonia', 'omnivalt')),
      );
      foreach ($this->omnivalt_shipping_params as $country_code => $ship_params) {
        $fields['prices_'.$country_code] = array(
          'type' => 'prices_box',
          'lang' => $country_code,
        );
      }
      $fields['hr_settings'] = array(
        'type' => 'hr'
      );
      $fields['weight'] = array(
        'title' => sprintf(__('Max cart weight (%s) for terminal', 'omnivalt'),'kg'),
        'type' => 'number',
        'custom_attributes' => array(
          'step' => 0.001,
          'min' => 0
        ),
        'description' => __('Maximum allowed all cart products weight for parcel terminals.', 'omnivalt'),
        'default' => 30,
        'class' => 'omniva_terminal'
      );
      $fields['weight_c'] = array(
        'title' => sprintf(__('Max cart weight (%s) for courier', 'omnivalt'),'kg'),
        'type' => 'number',
        'custom_attributes' => array(
          'step' => 0.001,
          'min' => 0
        ),
        'description' => __('Maximum allowed all cart products weight for courier.', 'omnivalt'),
        'default' => 100,
        'class' => 'omniva_courier'
      );
      $fields['weight_po'] = array(
        'title' => sprintf(__('Max cart weight (%s) for post office', 'omnivalt'),'kg'),
        'type' => 'number',
        'custom_attributes' => array(
          'step' => 0.001,
          'min' => 0
        ),
        'description' => __('Maximum allowed all cart products weight for post office.', 'omnivalt'),
        'default' => 100,
        'class' => 'omniva_post'
      );
      $fields['size_pt'] = array(
        'title' => sprintf(__('Max cart size (%s) for terminal', 'omnivalt'),get_option('woocommerce_dimension_unit')),
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
        'description' => __('Select categories you want to disable the Omniva method', 'omnivalt'),
        'options' => $this->omnivalt_get_categories(),
        'desc_tip' => true,
        'required' => false,
        'custom_attributes' => array(
          'data-placeholder' => __('Select Categories', 'omnivalt'),
          'data-name' => 'restricted_categories'
        ),
      );
      $fields['show_map'] = array(
        'title' => __('Map', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Show map of terminals.', 'omnivalt'),
        'default' => 'yes',
        'class' => 'omniva_terminal'
      );
      $fields['auto_select'] = array(
        'title' => __('Automatic terminal selection', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Automatically select terminal by postcode.', 'omnivalt'),
        'default' => 'yes',
        'class' => 'omniva_terminal'
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
      foreach ($this->omnivalt_text_variables as $key => $title) {
        $inline_variables .= '<br/><code>{' . $key . '}</code> - ' . __('Order number', 'omnivalt');
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
      $fields['hr_debug'] = array(
        'type' => 'hr'
      );
      $fields['debug_mode'] = array(
        'title' => __('Enable debug mode', 'omnivalt'),
        'type' => 'checkbox',
        'description' => __('Enable request and response logging.', 'omnivalt') . ' ' . sprintf(__('Log files are stored for %s days.', 'omnivalt'), 30),
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
      $this->form_fields = $fields;
    }

    public function generate_hr_html( $key, $value ) {
      $class = (isset($value['class'])) ? $value['class'] : '';
      $html = '<tr valign="top"><td colspan="2"><hr class="' . $class . '"></td></tr>';
      return $html;
    }
    public function generate_empty_html( $key, $value ) {
      $class = (isset($value['class'])) ? $value['class'] : '';
      $html = '<tr valign="top"><td colspan="2" class="' . $class . '"></td></tr>';
      return $html;
    }

    public function generate_prices_box_html( $key, $value ) {
      $box_key = $this->get_field_key($key);
      $html = '';
      if (isset($value['lang'])) {
        $flag_img_url = OMNIVALT_URL . 'css/images/flags/' . strtolower($value['lang']) . '.png';
        if (isset($this->omnivalt_shipping_params[$value['lang']])) {
          $shipping_methods = $this->omnivalt_shipping_params[$value['lang']]['methods'];
          $shipping_keys = array();
          foreach ($shipping_methods as $ship_method) {
            $shipping_keys[] = $this->omnivalt_convert_method_name_to_short($ship_method);
          }
        } else {
          $shipping_keys = array('pt','c','cp','po');
        }
        $fields = array();
        foreach ($shipping_keys as $ship_key) {
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
        foreach ($fields as $id => $field) {
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
                    if ($ship_key === 'pt') {
                      $params['type'] = 'terminal';
                      $params['title'] = __('Parcel terminal','omnivalt');
                      $params['enable']['title'] = __('Enable parcel terminal','omnivalt');
                      $params['prices']['boxsize'] = (isset($values[$ship_key . '_price_by_boxsize'])) ? $values[$ship_key . '_price_by_boxsize'] : false;
                      $params['prices']['boxsize_name'] = $ship_key . '_price_by_boxsize';
                    }
                    if ($ship_key === 'c') {
                      $params['type'] = 'courier';
                      $params['title'] = __('Courier','omnivalt');
                      $params['enable']['title'] = __('Enable courier','omnivalt');
                    }
                    if ($ship_key === 'cp') {
                      $params['type'] = 'courier_plus';
                      $params['title'] = __('Courier Plus','omnivalt');
                      $params['enable']['title'] = __('Enable courier plus','omnivalt');
                    }
                    if ($ship_key === 'po') {
                      $params['type'] = 'post';
                      $params['title'] = __('Post office','omnivalt');
                      $params['enable']['title'] = __('Enable post office','omnivalt');
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

      if (empty($params['type']) || empty($params['box_key'])) {
        return '';
      }

      ob_start();
      ?>
      <div class="block-prices <?php echo $params['type']; ?>">
        <div class="sec-title">
          <?php
          /* -Compatibility with old data- */
          if ($params['prices']['single']['is_old'] && isset($params['prices']['single']['value']) && $params['prices']['single']['value'] !== '') {
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
          <?php if ($params['prices']['type'] !== false) : ?>
            <?php
            $html_params = array(
              'field_id' => $params['prices']['type']['key'],
              'field_name' => $params['box_key'] . '[' . $params['prices']['type_name'] . ']',
              'field_value' => $params['prices']['type']['value'],
            );
            if ($params['prices']['boxsize'] !== false) {
              $html_params['add_select_options'] = array(
                'boxsize' => __('By box size','omnivalt'),
              );
            }
            echo OmnivaLt_Admin_Html::buildPriceType($html_params);
            ?>
          <?php endif; ?>
          <?php if ($params['prices']['single'] !== false) : ?>
            <div class="prices-single">
              <?php
              $field_value = $params['prices']['single']['value'];
              if (empty($field_value) && $field_value !== 0 && $field_value !== '0') {
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
          <?php if ($params['prices']['weight'] !== false) : ?>
            <?php
            $html_params = array(
              'type' => 'weight',
              'field_id' => $params['prices']['weight']['key'],
              'field_name' => $params['box_key'] . '[' . $params['prices']['weight_name'] . ']',
              'values' => $params['prices']['weight']['value'],
              'c1_title' => $params['prices']['weight_title'] . ' (' . get_option('woocommerce_weight_unit') . ')',
              'c1_step' => 0.001,
            );
            echo OmnivaLt_Admin_Html::buildPricesTable($html_params);
            ?>
          <?php endif; ?>
          <?php if ($params['prices']['amount'] !== false) : ?>
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
          <?php if ($params['prices']['boxsize'] !== false) : ?>
            <?php
            $method_params = omnivalt_configs('method_params');
            $box_sizes = array();
            if (isset($method_params[$params['type']]['sizes'])) {
              foreach ($method_params[$params['type']]['sizes'] as $key => $sizes) {
                if ($key !== 'min') {
                  $box_sizes[] = $key;
                }
              }
            }
            if (empty($params['prices']['boxsize']['value'])) {
              $default_values = array();
              for ($i=0;$i<count($box_sizes);$i++) {
                $default_values[] = (object) array(
                  'value' => $box_sizes[$i],
                  'price' => 2,
                );
              }
              $params['prices']['boxsize']['value'] = (object) $default_values;
            } else {
              $i = 0;
              foreach ($params['prices']['boxsize']['value'] as $value) {
                if (isset($box_sizes[$i])) {
                  $value->value = $box_sizes[$i];
                }
                $i++;
              }
            }
            $box_titles = $method_params[$params['type']]['titles'];
            foreach ($box_titles as $key => $title) {
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
          <?php if ($params['prices']['free'] !== false) : ?>
            <div class="prices-free">
              <?php
              $field_checked = ($params['prices']['free_enable']['value']) ? 'checked' : '';
              /* -Compatibility with old data- */
              if ($params['prices']['free']['is_old'] && isset($params['prices']['free']['value']) && $params['prices']['free']['value'] !== '') {
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
              if (empty($field_value) && $field_value != 0) {
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
          <?php if ($params['prices']['coupon'] !== false) : ?>
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
              foreach($params['data']['coupons'] as $coupon) {
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
          <?php if ($params['other']['desc'] !== false) : ?>
            <div class="other-description">
              <?php
              $html_params = array(
                'label' => $params['other']['desc_title'],
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
            <input type="number" value="<?php echo $dim_values[0]; ?>"
              id="<?php echo esc_html($field_key); ?>"
              name="<?php echo esc_html($field_key); ?>[0]"
              min="0.001" step="0.001" placeholder="<?php echo __('Length','omnivalt'); ?>">
            <span>x</span>
            <input type="number" value="<?php echo $dim_values[1]; ?>"
              id="<?php echo esc_html($field_key); ?>"
              name="<?php echo esc_html($field_key); ?>[1]"
              min="0.001" step="0.001" placeholder="<?php echo __('Width','omnivalt'); ?>">
            <span>x</span>
            <input type="number" value="<?php echo $dim_values[2]; ?>"
              id="<?php echo esc_html($field_key); ?>"
              name="<?php echo esc_html($field_key); ?>[2]"
              min="0.001" step="0.001" placeholder="<?php echo __('Height','omnivalt'); ?>">
             <span><?php echo get_option('woocommerce_dimension_unit'); ?></span>
            <?php if (!empty($value['description'])) : ?>
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

    /**
     * Get categories for "restricted_categories" field
     */
    public function omnivalt_get_categories()
    {
      $cats = $this->get_categories_hierarchy();
      $result = [];
          
      foreach ($cats as $item) {
        $this->create_categories_list('', $item, $result);
      }

      return $result;
    }

    /**
     * Makes a list of categories to select from in settings page. array(lowest cat id => full cat path name)
     */
    private function create_categories_list($prefix, $data, &$results)
    {
      if ($prefix) {
        $prefix = $prefix . ' &gt; ';
        $results[$data->term_id] = $prefix . $data->name;
      }
      if (!$data->children) {
        $results[$data->term_id] = $prefix . $data->name;

        return true;
      }

      foreach ($data->children as $child) {
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

      foreach( $cats as $cat ) {
        $cat->children = $this->get_categories_hierarchy( $cat->term_id );
        $children[ $cat->term_id ] = $cat;
      }

      return $children;
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
            <?php if (empty($files)) : ?>
              <textarea readonly rows="2" style="width:100%">- <?php echo __('Debug files still not created','omnivalt'); ?> -</textarea>
            <?php else : ?>
              <?php foreach ($files as $file_data) : ?>
                <div class="debug-row">
                  <?php
                  $file_path = $files_dir . $file_data['name'];
                  $file = fopen($file_path, 'r');
                  if (filesize($file_path) > 0) {
                    $file_content = fread($file,filesize($file_path));
                  } else {
                    $file_content = '- ' . __('File is empty','omnivalt') . ' -';
                  }
                  fclose($file);
                  if (!empty($file_data['day'])) {
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
      foreach ($package['contents'] as $item_id => $values) {
        $_product = $values['data'];
        if ($_product->get_weight()) {
          $weight = $weight + $_product->get_weight() * $values['quantity'];
        }
        for ($i=0;$i<$values['quantity'];$i++) {
          array_push($products_for_dim, $_product);
        }
      }

      $weight = wc_get_weight($weight, 'kg');

      $prices_key = (array_key_exists($country, $this->omnivalt_shipping_params)) ? 'prices_' . $country : 'prices_LT';
      $prices = (isset($this->settings[$prices_key])) ? json_decode($this->settings[$prices_key]) : array();

      $this->add_parcel_terminal_rate($products_for_dim, $weight, $country, $cart_amount, $prices, $package);
      $this->add_courier_rate($weight, $country, $cart_amount, $prices, $package);
      $this->add_courier_plus_rate($weight, $country, $cart_amount, $prices, $package);
      $this->add_post_office_rate($weight, $prices, $cart_amount, $package);
    }

    private function add_parcel_terminal_rate($products_for_dim, $weight, $country, $cart_amount, $prices, $package)
    {
      $weight_pass = $this->check_weight($weight, 'weight');
      $dimension_pass = $this->check_dimmension($products_for_dim, 'size_pt');

      if ($this->settings['method_pt'] == 'yes' && $weight_pass && $dimension_pass) {
        $show = true;
        $meta_data = array();
        /* -For compatibility with old version settings- */
        if ( array_key_exists($country, $this->omnivalt_shipping_params) ) {
          $price_name = (isset($this->settings['pt_price_' . $country])) ? 'pt_price_' . $country : 'pt_price' . $country;
          $free_name = (isset($this->settings['pt_price_' . $country . '_FREE'])) ? 'pt_price_' . $country . '_FREE' : 'pt_price' . $country . '_FREE';
          if ($country == 'LT') {
            $price_name = (isset($this->settings['pt_price_LT'])) ? 'pt_price_LT' : 'pt_price';
            $free_name = (isset($this->settings['pt_price_LT_FREE'])) ? 'pt_price_LT_FREE' : 'pt_priceFREE';
          }
        } else {
          $price_name = (isset($this->settings['pt_price_LT'])) ? 'pt_price_LT' : 'pt_price';
          $free_name = (isset($this->settings['pt_price_LT_FREE'])) ? 'pt_price_LT_FREE' : 'pt_priceFREE';
        }
        $amount = isset($this->settings[$price_name]) ? $this->settings[$price_name] : '';
        $amount_free = isset($this->settings[$free_name]) ? floatval($this->settings[$free_name]) : 100;
        if (isset($this->settings[$price_name]) && $amount === '') 
          $show = false;
        if ($cart_amount >= $amount_free && $amount_free > 0)
          $amount = 0.0;
        /* -End of compatibility- */
        $amount = (isset($prices->pt_price_single)) ? $prices->pt_price_single : $amount;
        if (isset($prices->pt_price_type)) {
          if ($prices->pt_price_type == 'weight' && isset($prices->pt_price_by_weight)) {
            $amount = $this->get_price_from_table($prices->pt_price_by_weight, $weight, $amount);
            $meta_data[__('Weight', 'omnivalt')] = $weight;
          }
          if ($prices->pt_price_type == 'amount' && isset($prices->pt_price_by_amount)) {
            $amount = $this->get_price_from_table($prices->pt_price_by_amount, $cart_amount, $amount);
          }
          if ($prices->pt_price_type == 'boxsize' && isset($prices->pt_price_by_boxsize)) {
            $box = $this->check_omniva_box_size();
            $amount = $this->get_price_from_table($prices->pt_price_by_boxsize, $box, '');
            if (empty($amount)) {
              $show = false;
            }
            $meta_data[__('Size', 'omnivalt')] = $box;
          }
        }
        if ($amount !== '') { //Fix compatibility
          $show = true;
        }
        $amount_free = (isset($prices->pt_free_from)) ? $prices->pt_free_from : $amount_free;
        if (!isset($prices->pt_enable)) {
          $show = false;
        }
        if (isset($prices->pt_enable_free_from)) {
          if ($cart_amount >= $amount_free) $amount = 0.0;
        }
        if (isset($prices->pt_enable_coupon)) {
          if (isset($prices->pt_coupon) && !empty($package["applied_coupons"])) {
            foreach ($package["applied_coupons"] as $coupon) {
              if ($prices->pt_coupon == $coupon) $amount = 0.0;
            }
          }
        }

        $rate = array(
          'id' => 'omnivalt_pt',
          'label' => __('Omniva parcel terminal', 'omnivalt'),
          'cost' => $amount,
          'meta_data' => $meta_data,
        );
        if ($show) {
          $this->add_rate($rate);
        }
      }
    }

    private function add_courier_rate($weight, $country, $cart_amount, $prices, $package)
    {
      $weight_pass = $this->check_weight($weight, 'weight_c');

      if ($this->settings['method_c'] == 'yes' && $weight_pass) {
        $show = true;
        $meta_data = array();
        /* -For compatibility with old version settings- */
        if ( array_key_exists($country, $this->omnivalt_shipping_params) ) {
          $price_name = (isset($this->settings['c_price_' . $country])) ? 'c_price_' . $country : 'c_price' . $country;
          $free_name = (isset($this->settings['c_price_' . $country . '_FREE'])) ? 'c_price_' . $country . '_FREE' : 'pt_price_C_' . $country . '_FREE';
          if ($country == 'LT') {
            $price_name = (isset($this->settings['c_price_LT'])) ? 'c_price_LT' : 'c_price';
            $free_name = (isset($this->settings['c_price_LT_FREE'])) ? 'c_price_LT_FREE' : 'pt_price_C_FREE';
          }
        } else {
          $price_name = (isset($this->settings['c_price_LT'])) ? 'c_price_LT' : 'c_price';
          $free_name = (isset($this->settings['c_price_LT_FREE'])) ? 'c_price_LT_FREE' : 'pt_price_C_FREE';
        }
        $amount = isset($this->settings[$price_name]) ? $this->settings[$price_name] : '';
        $amount_free = isset($this->settings[$free_name]) ? floatval($this->settings[$free_name]) : 100;
        if (isset($this->settings[$price_name]) && $amount === '') 
          $show = false;
        if ($cart_amount >= $amount_free && $amount_free > 0)
          $amount = 0.0;
        /* -End of compatibility- */
        $amount = (isset($prices->c_price_single)) ? $prices->c_price_single : $amount;
        if (isset($prices->c_price_type)) {
          if ($prices->c_price_type == 'weight' && isset($prices->c_price_by_weight)) {
            $amount = $this->get_price_from_table($prices->c_price_by_weight, $weight, $amount);
            $meta_data[__('Weight', 'omnivalt')] = $weight;
          }
          if ($prices->c_price_type == 'amount' && isset($prices->c_price_by_amount)) {
            $amount = $this->get_price_from_table($prices->c_price_by_amount, $cart_amount, $amount);
          }
        }
        if ($amount !== '') { //Fix compatibility
          $show = true;
        }
        $amount_free = (isset($prices->c_free_from)) ? $prices->c_free_from : $amount_free;
        if (!isset($prices->c_enable)) {
          $show = false;
        }
        if (isset($prices->c_enable_free_from)) {
          if ($cart_amount >= $amount_free) $amount = 0.0;
        }
        if (isset($prices->c_enable_coupon)) {
          if (isset($prices->c_coupon) && !empty($package["applied_coupons"])) {
            foreach ($package["applied_coupons"] as $coupon) {
              if ($prices->c_coupon == $coupon) $amount = 0.0;
            }
          }
        }

        $rate = array(
          'id' => 'omnivalt_c',
          'label' => __('Omniva courier', 'omnivalt'),
          'cost' => $amount,
          'meta_data' => $meta_data,
        );
        if ($show) {
          $this->add_rate($rate);
        }
      }
    }

    private function add_courier_plus_rate($weight, $country, $cart_amount, $prices, $package)
    {
      $weight_pass = $this->check_weight($weight, 'weight_cp');

      if ($this->settings['method_cp'] == 'yes' && $weight_pass) {
        $show = true;
        $meta_data = array();
        $amount = (isset($prices->cp_price_single)) ? $prices->cp_price_single : '';
        if (isset($prices->cp_price_type)) {
          if ($prices->cp_price_type == 'weight' && isset($prices->cp_price_by_weight)) {
            $amount = $this->get_price_from_table($prices->cp_price_by_weight, $weight, $amount);
            $meta_data[__('Weight', 'omnivalt')] = $weight;
          }
          if ($prices->cp_price_type == 'amount' && isset($prices->cp_price_by_amount)) {
            $amount = $this->get_price_from_table($prices->cp_price_by_amount, $cart_amount, $amount);
          }
        }
        $amount_free = (isset($prices->cp_free_from)) ? $prices->cp_free_from : 100;
        if (!isset($prices->cp_enable)) {
          $show = false;
        }
        if (isset($prices->cp_enable_free_from)) {
          if ($cart_amount >= $amount_free) $amount = 0.0;
        }
        if (isset($prices->cp_enable_coupon)) {
          if (isset($prices->cp_coupon) && !empty($package["applied_coupons"])) {
            foreach ($package["applied_coupons"] as $coupon) {
              if ($prices->cp_coupon == $coupon) $amount = 0.0;
            }
          }
        }

        $rate = array(
          'id' => 'omnivalt_cp',
          'label' => __('Omniva courier plus', 'omnivalt'),
          'cost' => $amount,
          'meta_data' => $meta_data,
        );
        if ($show) {
          $this->add_rate($rate);
        }
      }
    }

    private function add_post_office_rate($weight, $prices, $cart_amount, $package)
    {
      $weight_pass = $this->check_weight($weight, 'weight_po');

      if ($this->settings['method_po'] == 'yes' && $weight_pass) {
        $show = true;
        $meta_data = array();
        $amount = (isset($prices->po_price_single)) ? $prices->po_price_single : '';
        if (isset($prices->po_price_type)) {
          if ($prices->po_price_type == 'weight' && isset($prices->po_price_by_weight)) {
            $amount = $this->get_price_from_table($prices->po_price_by_weight, $weight, $amount);
            $meta_data[__('Weight', 'omnivalt')] = $weight;
          }
          if ($prices->po_price_type == 'amount' && isset($prices->po_price_by_amount)) {
            $amount = $this->get_price_from_table($prices->po_price_by_amount, $cart_amount, $amount);
          }
        }
        $amount_free = (isset($prices->po_free_from)) ? $prices->po_free_from : 100;
        if (!isset($prices->po_enable)) {
          $show = false;
        }
        if (isset($prices->po_enable_free_from)) {
          if ($cart_amount >= $amount_free) $amount = 0.0;
        }
        if (isset($prices->po_enable_coupon)) {
          if (isset($prices->po_coupon) && !empty($package["applied_coupons"])) {
            foreach ($package["applied_coupons"] as $coupon) {
              if ($prices->po_coupon == $coupon) $amount = 0.0;
            }
          }
        }

        $rate = array(
          'id' => 'omnivalt_po',
          'label' => __('Omniva post office', 'omnivalt'),
          'cost' => $amount,
          'meta_data' => $meta_data,
        );
        if ($show) {
          $this->add_rate($rate);
        }
      }
    }

    private function check_weight($weight, $settings_key)
    {
      if (isset($this->settings[$settings_key])) {
        return (floatval($this->settings[$settings_key]) >= $weight || floatval($this->settings[$settings_key]) == 0);
      }

      return true;
    }

    private function check_dimmension($products_for_dim, $settings_key)
    {
      $max_dimension = (isset($this->settings[$settings_key])) ? json_decode($this->settings[$settings_key]) : array(999999,999999,999999);
      
      if ( (isset($max_dimension[0]) && !empty($max_dimension[0]))
        || (isset($max_dimension[1]) && !empty($max_dimension[1]))
        || (isset($max_dimension[2]) && !empty($max_dimension[2])) )
      {
        return $this->cart_size_prediction($products_for_dim, $max_dimension);
      }

      return true;
    }

    private function get_price_from_table($table_values, $cart_value, $default_value)
    {
      foreach ($table_values as $values) {
        if (empty($values->value) && !empty($values->price)) {
          return $values->price;
        }
        if (is_numeric($cart_value) && $cart_value < $values->value) {
          return $values->price;
        } elseif ($cart_value === $values->value) {
          return $values->price;
        }
      }

      return $default_value;
    }

    private function cart_size_prediction($products, $max_dimension)
    {
      $all_cart_dim_length = 0;
      $all_cart_dim_width = 0;
      $all_cart_dim_height = 0;
      $max_dim_length = (!empty($max_dimension[0])) ? $max_dimension[0] : 999999;
      $max_dim_width = (!empty($max_dimension[1])) ? $max_dimension[1] : 999999;
      $max_dim_height = (!empty($max_dimension[2])) ? $max_dimension[2] : 999999;

      foreach ($products as $product) {
        $prod_dim_length = (!empty($product->get_length())) ? $product->get_length() : 0;
        $prod_dim_width = (!empty($product->get_width())) ? $product->get_width() : 0;
        $prod_dim_height = (!empty($product->get_height())) ? $product->get_height() : 0;

        //Add to length
        if ( ($prod_dim_length + $all_cart_dim_length) <= $max_dim_length 
          && $prod_dim_width <= $max_dim_width && $prod_dim_height <= $max_dim_height
        ) {
          $all_cart_dim_length = $all_cart_dim_length + $prod_dim_length;
          $all_cart_dim_width = ($prod_dim_width > $all_cart_dim_width) ? $prod_dim_width : $all_cart_dim_width;
          $all_cart_dim_height = ($prod_dim_height > $all_cart_dim_height) ? $prod_dim_height : $all_cart_dim_height;
        }
        //Add to width
        else if ( ($prod_dim_width + $all_cart_dim_width) <= $max_dim_width 
          && $prod_dim_length <= $max_dim_length && $prod_dim_height <= $max_dim_height
        ) {
          $all_cart_dim_length = ($prod_dim_length > $all_cart_dim_length) ? $prod_dim_length : $all_cart_dim_length;
          $all_cart_dim_width = $all_cart_dim_width + $prod_dim_width;
          $all_cart_dim_height = ($prod_dim_height > $all_cart_dim_height) ? $prod_dim_height : $all_cart_dim_height;
        }
        //Add to height
        else if ( ($prod_dim_height + $all_cart_dim_height) <= $max_dim_height 
          && $prod_dim_length <= $max_dim_length && $prod_dim_width <= $max_dim_width
        ) {
          $all_cart_dim_length = ($prod_dim_length > $all_cart_dim_length) ? $prod_dim_length : $all_cart_dim_length;
          $all_cart_dim_width = ($prod_dim_width > $all_cart_dim_width) ? $prod_dim_width : $all_cart_dim_width;
          $all_cart_dim_height = $all_cart_dim_height + $prod_dim_height;
        }
        //If all fails
        else {
          return false;
        }
      }
      return true;
    }

    private function check_omniva_box_size()
    {
      omnivalt_add_required_directories();

      // Check if all cart items have all dimensions
      $dimensions_present = true;
      $cart_items = $this->get_cart_items_dimensions();
      foreach ( $cart_items as $cart_item ) {
        foreach ( $cart_item as $cart_item_value ) {
          if (empty($cart_item_value)) {
            file_put_contents(OMNIVALT_DIR . 'logs/boxsize.log', PHP_EOL . date('Y-m-d H:i:s') . ' BAD DIMMENSIONS ' . json_encode($cart_item) . PHP_EOL, FILE_APPEND);
            $dimensions_present = false;
            break;
          }
        }
      }
      if(!$dimensions_present) {
        return false;
      }

      // Pack
      $arranged_cart_items = $this->arrange_cart_items($cart_items);
      $packer = new OmnivaLt_Packer($arranged_cart_items);
      $box_size = $packer->pack();

      if (!$box_size) {
        file_put_contents(OMNIVALT_DIR . 'logs/boxsize.log', PHP_EOL . date('Y-m-d H:i:s') . ' NO BOX TO FIT. CART ITEMS DIMMENSIONS: ' . json_encode($cart_items) . PHP_EOL, FILE_APPEND);
      }

      return $box_size;
    }

    private function arrange_cart_items($cart_items)
    {
      $arranged_cart_items = [];

      foreach ($cart_items as $cart_item) {
        if ($cart_item['qty'] > 1) {
          for($i = 0; $i < $cart_item['qty']; $i++) {
            $arranged_cart_items[] = [
              'length'    => $cart_item['length'],
              'width'     => $cart_item['width'],
              'height'    => $cart_item['height'],
            ];
          }
        } else {
          $arranged_cart_items[] = [
            'length'    => $cart_item['length'],
            'width'     => $cart_item['width'],
            'height'    => $cart_item['height'],
          ];
        }
      }

      return $arranged_cart_items;
    }

    private function get_cart_items_dimensions()
    {
      $items_dimensions = [];
      $dimension_unit = get_option( 'woocommerce_dimension_unit' );

      // Get rate
      switch ($dimension_unit) {
        case 'mm':
          $rate = 1;
          break;
        case 'cm':
          $rate = pow(10, 1);
          break;
        case 'm':
          $rate = pow(10, 2);
          break;
        default:
          $rate = null;
      }

      foreach(WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $qty     = $cart_item['quantity'];

        $length = floatval($product->get_length()) * $rate;
        $width = floatval($product->get_width()) * $rate;
        $height = floatval($product->get_height()) * $rate;

        $items_dimensions[] = [
          'product_id' => $product->get_id(),
          'product_name' => $product->get_name(),
          'length'    => $length,
          'width'     => $width,
          'height'    => $height,
          'volume'    => $length * $height * $width,
          'qty'       => $qty,
        ];
      }

      // Sort by largest first
      usort($items_dimensions, function($a, $b) {
        $a = $a['volume'];
        $b = $b['volume'];

        if ($a === $b) {
          return 0;
        }

        return ($a < $b) ? -1 : 1;
      });

      return $items_dimensions;
    }

    public function printLabels($orderIds = false, $download = true)
    {
      if (empty($orderIds) || !$orderIds) {
        return;
      }
      $omniva_settings = get_option('woocommerce_omnivalt_settings');
      $print_type = (isset($omniva_settings['print_type'])) ? $omniva_settings['print_type'] : '4';
      $count = 0;
      $label_count = 0;
      require_once(OMNIVALT_DIR . 'tcpdf/tcpdf.php');
      require_once(OMNIVALT_DIR . 'fpdi/src/autoload.php');
      $pdf = new \setasign\Fpdi\Tcpdf\Fpdi('P');
      $pdf->setPrintHeader(false);
      $pdf->setPrintFooter(false);
      if (!is_array($orderIds))
        $orderIds = array($orderIds);
      foreach (array_unique($orderIds) as $orderId) {
        $order = get_post((int) $orderId);
        $wc_order = wc_get_order((int) $orderId);
        $send_method = "";
        foreach ($wc_order->get_items('shipping') as $item_id => $shipping_item_obj) {
          $send_method        = $shipping_item_obj->get_method_id();
        }
        if ($send_method == 'omnivalt') {
          $send_method = get_post_meta($orderId, '_omnivalt_method', true);
        }
        if (!($send_method == 'omnivalt_pt' || $send_method == 'omnivalt_c' || $send_method == 'omnivalt_cp' || $send_method == 'omnivalt_po')) {
          OmnivaLt_Helper::add_msg($orderId . ' - ' . __('Shipping method is not Omniva', 'omnivalt'), 'error');
          continue;
        }
        $track_numer = get_post_meta($orderId, '_omnivalt_barcode', true);
        if ($track_numer == '' || !$download || !file_exists(OMNIVALT_DIR . 'pdf/' . $orderId . '.pdf')) {
          if (file_exists(OMNIVALT_DIR . 'pdf/' . $orderId . '.pdf')) {
            unlink(OMNIVALT_DIR . 'pdf/' . $orderId . '.pdf');
          }
          $status = $this->omnivalt_api->get_tracking_number($orderId);
          if (!empty($status['debug'])) {
            OmnivaLt_Helper::add_msg('<b>OMNIVA RESPONSE DEBUG:</b><br/><pre style="white-space:pre-wrap;">' . htmlspecialchars($status['debug']) . '</pre>', 'notice');
          }
          if (isset($status['status'])) {
            update_post_meta($orderId, '_omnivalt_barcode', $status['barcodes'][0]);
            $label_status = $this->omnivalt_api->get_shipment_labels($status['barcodes'], $orderId);
            if (!$label_status['status']) {
              update_post_meta($orderId, '_omnivalt_error', $label_status['msg']);
              OmnivaLt_Helper::add_msg($orderId . ' - ' . $label_status['msg'], 'error');
              continue;
            }
            if (!$download)
              OmnivaLt_Helper::add_msg($orderId . ' - ' . __('Omniva label generated', 'omnivalt'), 'updated');
            $send_email = (isset($omniva_settings['email_created_label'])) ? $omniva_settings['email_created_label'] : 'yes';
            if ($send_email === 'yes') {
              $emails = new Omniva_Emails( OMNIVALT_DIR . '/templates/');
              $email_subject = (isset($omniva_settings['email_created_label_subject'])) ? $omniva_settings['email_created_label_subject'] : '';
              $email_params = array(
                'tracking_code' => $status['barcodes'][0],
                'tracking_link' => getTrackingLink($wc_order->get_shipping_country(), $status['barcodes'][0], true),
                'subject' => $email_subject
              );
              $emails->send_label($wc_order, $wc_order->get_billing_email(), $email_params);
            }
          } else {
            update_post_meta($orderId, '_omnivalt_error', $status['msg']);
            OmnivaLt_Helper::add_msg($orderId . ' - ' . $status['msg'], 'error');
            continue;
          }
        }

        $label_url = '';
        if (file_exists(OMNIVALT_DIR . 'pdf/' . $orderId . '.pdf')) {
          $label_url = OMNIVALT_DIR . 'pdf/' . $orderId . '.pdf';
        }
        if ($label_url == '') {
          continue;
        }
        update_post_meta($orderId, '_omnivalt_error', '');
        $pagecount = $pdf->setSourceFile($label_url);
        for ($i = 1; $i <= $pagecount; $i++) {
          $tplidx = $pdf->ImportPage($i);
          if ($print_type == '1') {
            $s = $pdf->getTemplatesize($tplidx);
            $pdf->AddPage('P', array($s['width'], $s['height']));
            $pdf->useTemplate($tplidx);
          } else if ($print_type == '4') {
            if ($label_count == 0 || $label_count == 4) {
              $pdf->AddPage('P');
              $label_count = 0;
              $pdf->useTemplate($tplidx, 5, 15, 94.5, 108, false);
            } else if ($label_count == 1) {
              $pdf->useTemplate($tplidx, 110, 15, 94.5, 108, false);
            } else if ($label_count == 2) {
              $pdf->useTemplate($tplidx, 5, 160, 94.5, 108, false);
            } else if ($label_count == 3) {
              $pdf->useTemplate($tplidx, 110, 160, 94.5, 108, false);
            }
            $label_count++;
          }
        }
        $count++;
      }
      if ($count == 0) {
        wp_safe_redirect(wp_get_referer());
        exit;
      }
      if ($download)
        $pdf->Output('Omnivalt_labels.pdf', 'D');
    }

    function printBulkManifests($orderIds = false)
    {
      require_once(OMNIVALT_DIR . 'tcpdf/tcpdf.php');
      if (!is_array($orderIds))
        $orderIds = array($orderIds);
      $object = '';
      $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

      $pdf->setPrintHeader(false);
      $pdf->setPrintFooter(false);
      $pdf->AddPage();
      $order_table = '';
      $count = 0;
      if (is_array($orderIds))
        foreach ($orderIds as $orderId) {
          $order = get_post((int) $orderId);
          $wc_order = wc_get_order((int) $orderId);
          $send_method = "";
          foreach ($wc_order->get_items('shipping') as $item_id => $shipping_item_obj) {
            $send_method        = $shipping_item_obj->get_method_id();
          }
          if ($send_method == 'omnivalt') {
            $send_method = get_post_meta($orderId, '_omnivalt_method', true);
          }
          if (!($send_method == 'omnivalt_pt' || $send_method == 'omnivalt_c' || $send_method == 'omnivalt_cp' || $send_method == 'omnivalt_po')) {
            OmnivaLt_Helper::add_msg($orderId . ' - ' . __('Shipping method is not Omniva', 'omnivalt'), 'error');
            continue;
          }
          $track_numer = get_post_meta($orderId, '_omnivalt_barcode', true);
          if ($track_numer == '') {
            $status = $this->omnivalt_api->get_tracking_number($orderId);
            if (!empty($status['debug'])) {
              OmnivaLt_Helper::add_msg('<b>OMNIVA RESPONSE DEBUG:</b><br/><pre style="white-space:pre-wrap;">' . htmlspecialchars($status['debug']) . '</pre>', 'notice');
            }
            if ($status['status']) {
              update_post_meta($orderId, '_omnivalt_barcode', $status['barcodes'][0]);
              $track_numer = $status['barcodes'][0];
              if (file_exists(OMNIVALT_DIR . 'pdf/' . $orderId . '.pdf')) {
                unlink(OMNIVALT_DIR . 'pdf/' . $orderId . '.pdf');
              }
              $label_status = $this->omnivalt_api->get_shipment_labels($status['barcodes'], $orderId);
              if (!$label_status['status']) {
                update_post_meta($orderId, '_omnivalt_error', $label_status['msg']);
                OmnivaLt_Helper::add_msg($orderId . ' - ' . $label_status['msg'], 'error');
                continue;
              }
            } else {
              OmnivaLt_Helper::add_msg($orderId . ' - ' . $status['msg'], 'error');
              continue;
            }
          }
          //if (get_post_meta($orderId,'_manifest_generation_date',true)){
          //  $this->add_msg($orderId.' - '.__('Manifest already generated','omnivalt'),'error');
          //  continue;
          //}
          update_post_meta($orderId, '_manifest_generation_date', date('Y-m-d H:i:s'));
          $pt_address = '';
          if ($send_method == 'omnivalt_pt') {
            $pt_address = $this->get_terminal_address($terminal_id = get_post_meta($orderId, '_omnivalt_terminal_id', true));
          }
          $client_address = get_post_meta($orderId, '_shipping_address_index', true);
          if ($pt_address != '')
            $client_address = '';
          $count++;
          $cart_weight = get_post_meta($orderId, '_cart_weight', true);
          $weight_unit = get_option('woocommerce_weight_unit');
          if ($weight_unit != 'kg') {
            $cart_weight = wc_get_weight($cart_weight, 'kg', $weight_unit);
          }
          $order_table .= '<tr><td width = "40" align="right">' . $count . '.</td><td>' . $track_numer . '</td><td width = "60">' . date('Y-m-d') . '</td><td width = "40">1</td><td width = "60">' . $cart_weight . '</td><td width = "210">' . $client_address . $pt_address . '</td></tr>';

          //make order shipped after creating manifest
          /*
            $history = new OrderHistory();
            $history->id_order = (int)$orderId;
            $history->id_employee = (int)$cookie->id_employee;
            $history->changeIdOrderState((int)Configuration::get('PS_OS_SHIPPING'), $order);
            $history->addWithEmail(true);*/
        }
      $pdf->SetFont('freeserif', '', 14);
      $shop_addr = '<table cellspacing="0" cellpadding="1" border="0"><tr><td>' . date('Y-m-d H:i:s') . '</td><td>' . _x('Sender address', 'Manifest', 'omnivalt') . ':<br/>' . $this->settings['shop_name'] . '<br/>' . $this->settings['shop_address'] . ', ' . $this->settings['shop_postcode'] . '<br/>' . $this->settings['shop_city'] . ', ' . $this->settings['shop_countrycode'] . '<br/></td></tr></table>';

      $pdf->writeHTML($shop_addr, true, false, false, false, '');
      $tbl = '
        <table cellspacing="0" cellpadding="4" border="1">
          <thead>
            <tr>
              <th width = "40" align="right">' . _x('No.', 'Manifest', 'omnivalt') . '</th>
              <th>' . _x('Shipment number', 'Manifest', 'omnivalt') . '</th>
              <th width = "60">' . _x('Date', 'Manifest', 'omnivalt') . '</th>
              <th width = "40">' . _x('Quantity', 'Manifest', 'omnivalt') . '</th>
              <th width = "60">' . _x('Weight (kg)', 'Manifest', 'omnivalt') . '</th>
              <th width = "210">' . _x("Recipient's address", 'Manifest', 'omnivalt') . '</th>
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
      $terminals_json_file_dir = dirname(__file__) . '/' . "locations.json";
      $terminals_file = fopen($terminals_json_file_dir, "r");
      $terminals = fread($terminals_file, filesize($terminals_json_file_dir) + 10);
      fclose($terminals_file);
      $terminals = json_decode($terminals, true);
      $parcel_terminals = '';
      if (is_array($terminals) && $terminal_id) {
        foreach ($terminals as $terminal) {
          if ($terminal['ZIP'] == $terminal_id) {
            return $terminal['NAME'] . ', ' . $terminal['A1_NAME'] . ', ' . $terminal['A0_NAME'];
          }
        }
      }
      return '';
    }
  }
}
