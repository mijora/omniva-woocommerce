<?php
defined('OMNIVALT_VERSION') or die();

class OmnivaLt_Shipping_Method_Country extends OmnivaLt_Shipping_Method_Core
{
    public function __construct( $country_code, $options_key )
    {
        $this->setType('country');
        $this->setKey($country_code);
        $this->setOptionsKey($options_key);

        $this->loadData();
        $this->setTitle(OmnivaLt_Wc::get_country_name($this->getKey()));
        $this->setImgUrl(OMNIVALT_URL . 'assets/img/flags/' . strtolower($this->getKey()) . '.png');
    }

    protected function loadData()
    {
        parent::loadData();

        if ( ! isset($this->omniva_configs['shipping_params']) ) {
            return;
        }
        $all_methods = OmnivaLt_Core::load_methods();
        $params_methods = array();
        if ( isset($this->omniva_configs['shipping_params'][$this->getKey()]) ) {
            $params_methods = $this->omniva_configs['shipping_params'][$this->getKey()]['methods'];
        }
        $methods = array();
        foreach ( $params_methods as $method_key ) {
            if ( isset($all_methods[$method_key]) ) {
                $method = $all_methods[$method_key];
                $method['fields'] = $this->getMethodFieldsData($all_methods[$method_key]['key']);
                $methods[$method_key] = $method;
            }
        }
        $this->setMethods($methods);
    }

    private function getMethodFields( $method_short_key )
    {
        $fields = array(
            'enable' => false,
            'price_type' => false,
            'price_single' => '',
            'price_by_weight' => null,
            'price_by_amount'=> null,
            'enable_free_from' => false,
            'free_from' => '',
            'enable_coupon' => false,
            'coupon' => '',
            'label' => '',
            'description' => '',
        );

        if ( $method_short_key == 'pt' ) {
            $fields['price_by_boxsize'] = null;
        }

        return $fields;
    }

    private function getMethodFieldsData( $method_short_key )
    {
        $fields = array();
        $empty_fields = $this->getMethodFields($method_short_key);
        $settings = $this->getSettings();

        foreach ( $empty_fields as $name => $value ) {
            $settings_field_key = $method_short_key . '_' . $name;
            $fields[$name] = (is_array($settings) && isset($settings[$settings_field_key])) ? $settings[$settings_field_key] : $value;
        }

        return $fields;
    }

    public function buildSettingsBlock( $params )
    {
        $method = $this->getCurrentMethod();
        $units = OmnivaLt_Wc::get_units();
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
                'type' => $this->prepareFieldData('type', $params['prices']),
                'single' => $this->prepareFieldData('single', $params['prices'], array('title' => __('Price','omnivalt'))),
                'weight' => $this->prepareFieldData('weight', $params['prices'], array('title' => __('Weight','omnivalt'))),
                'amount' => $this->prepareFieldData('amount', $params['prices'], array('title' => __('Cart amount','omnivalt'))),
                'boxsize' => $this->prepareFieldData('boxsize', $params['prices'], array('title' => __('Box size','omnivalt'))),
                'free_enable' => $this->prepareFieldData('free_enable', $params['prices']),
                'free' => $this->prepareFieldData('free', $params['prices'], array('title' => __('Free from','omnivalt'))),
                'coupon' => $this->prepareFieldData('coupon', $params['prices'], array('title' => __('Free with coupon','omnivalt'))),
                'coupon_enable' => $this->prepareFieldData('coupon_enable', $params['prices']),
            ),
            'data' => array(
                'coupons' => (isset($params['data']['coupons'])) ? $params['data']['coupons'] : array(),
            ),
            'other' => array(
                'label' => $this->prepareFieldData('label', $params['other'], array('title' => __('Custom label','omnivalt'))),
                'desc' => $this->prepareFieldData('desc', $params['other'], array('title' => __('Description','omnivalt'))),
            ),
        );

        if ( empty($params['type']) || empty($params['box_key']) ) {
            return '';
        }

        $coupons_args = OmnivaLt_Filters::settings_coupon_args();

        ob_start();
        ?>
        <pre><?php //print_r($params); ?></pre>
        <div class="block-prices <?php echo $params['type']; ?>">
            <div class="sec-title">
                <?php
                $html_params = array(
                    'label' => $params['title'],
                    'title' => $params['enable']['title'],
                    'id' => $params['enable']['id'],
                    'name' => $params['box_key'] . '[' . $params['enable']['name'] . ']',
                    'class' => $params['enable']['class'],
                    'checked' => ($params['enable']['checked'] === 'checked') ? true : false,
                );
                echo OmnivaLt_Shipping_Method_Html::buildSwitcher($html_params);
                ?>
            </div>
            <div class="sec-prices">
                <?php if ( isset($params['prices']['type']['key']) ) : ?>
                    <?php
                    $field_data = $params['prices']['type'];
                    $html_params = array(
                        'field_id' => $field_data['key'],
                        'field_name' => $params['box_key'] . '[' . $field_data['name'] . ']',
                        'field_value' => $field_data['value'],
                    );
                    if ( $params['prices']['boxsize'] !== false ) {
                        $html_params['add_select_options'] = array(
                            'boxsize' => __('By box size','omnivalt'),
                        );
                    }
                    echo OmnivaLt_Shipping_Method_Html::buildPriceType($html_params);
                    ?>
                <?php endif; ?>
                <?php if ( isset($params['prices']['single']['key']) ) : ?>
                    <div class="prices-single">
                        <?php
                        $field_data = $params['prices']['single'];
                        $field_value = $params['prices']['single']['value'];
                        if ( empty($field_value) && $field_value !== 0 && $field_value !== '0' ) {
                            $field_value = 2;
                        }
                        $html_params = array(
                            'label' => $field_data['title'] . ':',
                            'id' => $field_data['key'],
                            'type' => 'number',
                            'name' => $params['box_key'] . '[' . $field_data['name'] . ']',
                            'value' => $field_value,
                            'step' => 0.01,
                            'min' => 0,
                        );
                        echo OmnivaLt_Shipping_Method_Html::buildSimpleField($html_params);
                        ?>
                    </div>
                <?php endif; ?>
                <?php if ( isset($params['prices']['weight']['key']) ) : ?>
                    <?php
                    $field_data = $params['prices']['weight'];
                    $html_params = array(
                        'type' => 'weight',
                        'field_id' => $field_data['key'],
                        'field_name' => $params['box_key'] . '[' . $field_data['name'] . ']',
                        'values' => $field_data['value'],
                        'c1_title' => $field_data['title'] . ' (kg)',
                        'c1_step' => 0.001,
                        'c2_title' => __('Price','omnivalt') . ' (' . $units->currency_symbol . ')',
                    );
                    echo OmnivaLt_Shipping_Method_Html::buildPricesTable($html_params);
                    ?>
                <?php endif; ?>
                <?php if ( isset($params['prices']['amount']['key']) ) : ?>
                    <?php
                    $field_data = $params['prices']['amount'];
                    $html_params = array(
                        'type' => 'amount',
                        'field_id' => $field_data['key'],
                        'field_name' => $params['box_key'] . '[' . $field_data['name'] . ']',
                        'values' => $field_data['value'],
                        'c1_title' => $field_data['title'],
                        'c1_step' => 0.01,
                    );
                    echo OmnivaLt_Shipping_Method_Html::buildPricesTable($html_params);
                    ?>
                <?php endif; ?>
                <?php if ( isset($params['prices']['boxsize']['key']) ) : ?>
                    <?php
                    $field_data = $params['prices']['boxsize'];
                    $field_data['value'] = $this->getBoxSizesValues($field_data['value']);
                    $box_titles = $method['params']['titles'];
                    foreach ( $box_titles as $key => $title ) {
                        $h = $method['params']['sizes'][$key][0];
                        $w = $method['params']['sizes'][$key][1];
                        $l = $method['params']['sizes'][$key][2];
                        $text = sprintf(__('Max %s cm', 'omnivalt'), $h . '×' . $w . '×' . $l);
                        $box_titles[$key] = $title . '<br/><small>' . $text . '</small>';
                    }
                    $html_params = array(
                        'type' => 'boxsize',
                        'field_id' => $field_data['key'],
                        'field_name' => $params['box_key'] . '[' . $field_data['name'] . ']',
                        'values' => $field_data['value'],
                        'c1_title' => $field_data['title'],
                        'allow_add' => false,
                        'c1_text' => $box_titles,
                        'desc' => __('NOTE', 'omnivalt') . ': ' . __('If at least one item in the cart does not have the specified size, then this shipping method will not be displayed', 'omnivalt'),
                    );
                    echo OmnivaLt_Shipping_Method_Html::buildPricesTable($html_params);
                    ?>
                <?php endif; ?>
                <?php if ( isset($params['prices']['free']['key']) ) : ?>
                    <div class="prices-free">
                        <?php
                        $field_data = $params['prices']['free_enable'];
                        $field_checked = ($field_data['value']) ? 'checked' : '';
                        $html_params = array(
                            'label' => $params['prices']['free']['title'] . ':',
                            'label_position' => 'after',
                            'id' => $field_data['key'],
                            'class' => $field_data['class'],
                            'name' => $params['box_key'] . '[' . $field_data['name'] . ']',
                            'checked' => ($field_checked === 'checked') ? true : false,
                            'value' => 1,
                        );
                        echo OmnivaLt_Shipping_Method_Html::buildCheckbox($html_params);

                        $field_data = $params['prices']['free'];
                        $field_value = $field_data['value'];
                        if ( empty($field_value) && $field_value != 0 ) {
                            $field_value = 100;
                        }
                        $html_params = array(
                            'id' => $field_data['key'],
                            'type' => 'number',
                            'name' => $params['box_key'] . '[' . $field_data['name'] . ']',
                            'value' => $field_value,
                            'step' => 0.01,
                            'min' => 0,
                            'class' => 'input-text regular-input price_free',
                        );
                        echo ' ' . OmnivaLt_Shipping_Method_Html::buildSimpleField($html_params);
                        ?>
                    </div>
                <?php endif; ?>
                <?php if ( isset($params['prices']['coupon']['key']) ) : ?>
                    <div class="prices-coupon">
                        <?php
                        $field_data = $params['prices']['coupon_enable'];
                        $field_checked = ($params['prices']['coupon']['value']) ? 'checked' : '';
                        $html_params = array(
                            'label' => $params['prices']['coupon']['title'] . ':',
                            'label_position' => 'after',
                            'id' => $field_data['key'],
                            'class' => $field_data['class'],
                            'name' => $params['box_key'] . '[' . $field_data['name'] . ']',
                            'checked' => ($field_checked === 'checked') ? true : false,
                            'value' => 1,
                        );
                        echo OmnivaLt_Shipping_Method_Html::buildCheckbox($html_params);

                        $field_data = $params['prices']['coupon'];
                        $options = array();
                        foreach( $params['data']['coupons'] as $coupon ) {
                            $options[] = array(
                                'value' => strtolower($coupon->post_title),
                                'title' => $coupon->post_title,
                            );
                        }
                        $selected = (empty($field_data['value'])) ? 'selected' : '';
                        $html_params = array(
                            'name' => $params['box_key'] . '[' . $field_data['name'] . ']',
                            'id' => $field_data['key'],
                            'class' => 'price_coupon',
                            'options' => $options,
                            'selected' => $field_data['value'],
                        );
                        echo ' ' . OmnivaLt_Shipping_Method_Html::buildSelectField($html_params);
                        ?>
                        <?php if ( count($params['data']['coupons']) >= $coupons_args['posts_per_page'] ) : ?>
                            <p class="description"><?php echo __('NOTE', 'omnivalt') . ': ' . sprintf(__('The website has too many coupons, so only the first %d coupons are displayed', 'omnivalt'), $coupons_args['posts_per_page']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sec-other">
                <?php if ( isset($params['other']['label']['key']) ) : ?>
                    <div class="other-label">
                        <?php
                        $field_data = $params['other']['label'];
                        $html_params = array(
                            'label' => $field_data['title'] . ':',
                            'id' => $field_data['key'],
                            'name' => $params['box_key'] . '[' . $field_data['name'] . ']',
                            'value' => $field_data['value'],
                        );
                        echo ' ' . OmnivaLt_Shipping_Method_Html::buildSimpleField($html_params);
                        ?>
                    </div>
                <?php endif; ?>
                <?php if ( isset($params['other']['desc']['key']) ) : ?>
                    <div class="other-description">
                        <?php
                        $field_data = $params['other']['desc'];
                        $html_params = array(
                            'label' => $field_data['title'] . ':',
                            'id' => $field_data['key'],
                            'name' => $params['box_key'] . '[' . $field_data['name'] . ']',
                            'value' => $field_data['value'],
                        );
                        echo OmnivaLt_Shipping_Method_Html::buildTextareaField($html_params);
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

    public function getBoxSizesValues( $values )
    {
        $method = $this->getCurrentMethod();
        $box_sizes = array();
        if ( ! $method ) {
            return array();
        }
        
        if ( isset($method['params']['sizes']) ) {
            foreach ( $method['params']['sizes'] as $key => $sizes ) {
                if ( $key !== 'min' ) {
                    $box_sizes[] = $key;
                }
            }
        }
        
        if ( empty($values) ) {
            $default_values = array();
            for ( $i = 0; $i < count($box_sizes); $i++ ) {
                $default_values[] = array(
                    'value' => $box_sizes[$i],
                    'price' => 2,
                );
            }
            $values = $default_values;
        } else {
            $i = 0;
            $new_values = $values;
            foreach ( $values as $value_key => $value ) {
                if ( isset($box_sizes[$i]) ) {
                    $new_values[$value_key] = array(
                        'value' => $box_sizes[$i],
                        'price' => $value['price']
                    );
                }
                $i++;
            }
            $values = $new_values;
        }

        return $values;
    }

    private function prepareFieldData( $field_key, $fields_data, $default_values = array() )
    {
        $field_attributes = array('field', 'name', 'title', 'class');
        $field_data = array();
        foreach ( $field_attributes as $attr ) {
            $key = $field_key;
            if ( $attr !== 'field' ) {
                $key .= '_' . $attr;
                $default_value = (isset($default_values[$attr])) ? $default_values[$attr] : '';
                $field_data[$attr] = (isset($fields_data[$key])) ? $fields_data[$key] : $default_value;
                continue;
            }
            if ( isset($fields_data[$field_key]) ) {
                $field_data['id'] = (isset($fields_data[$field_key]['id'])) ? $fields_data[$field_key]['id'] : '';
                $field_data['key'] = (isset($fields_data[$field_key]['key'])) ? $fields_data[$field_key]['key'] : '';
                $field_data['value'] = (isset($fields_data[$field_key]['value'])) ? $fields_data[$field_key]['value'] : '';
            }
        }
        return $field_data;
    }

    public function getCartMethodAmount( $cart_weight, $cart_amount, $get_only_amount = true )
    {
        $meta_data = array();
        $method = $this->getCurrentMethod();

        $amount = $method['fields']['price_single'];

        if ( $method['fields']['price_type'] ) {
            if ( $method['fields']['price_type'] == 'weight' && $method['fields']['price_by_weight'] ) {
                $amount = $this->getPriceFromTable($method['fields']['price_by_weight'], $cart_weight, $amount);
                $meta_data[__('Weight', 'omnivalt')] = $cart_weight;
            }
            if ( $method['fields']['price_type'] == 'amount' && $method['fields']['price_by_amount'] ) {
                $amount = $this->getPriceFromTable($method['fields']['price_by_amount'], $cart_amount, $amount);
            }
            if ( $method['fields']['price_type'] == 'boxsize' && $method['fields']['price_by_boxsize'] ) {
                $box = OmnivaLt_Shipmethod_Helper::check_omniva_box_size();
                $amount = $this->getPriceFromTable($method['fields']['price_by_boxsize'], $box, '');
                $meta_data[__('Size', 'omnivalt')] = $box;
            }
        }

        if ( $get_only_amount ) {
            return $amount;
        }

        return array(
            'amount' => $amount,
            'meta_data' => $meta_data,
        );
    }

    public function isCartMethodFreeByValue( $cart_amount )
    {
        $method = $this->getCurrentMethod();

        if ( ! $method['fields']['enable_free_from'] ) {
            return false;
        }

        $amount_free = ($method['fields']['free_from'] != '') ? $method['fields']['free_from'] : 100;
        if ( $cart_amount < $amount_free ) {
            return false;
        }

        return true;
    }

    public function isCartMethodFreeByCoupon( $applied_coupons )
    {
        $method = $this->getCurrentMethod();

        if ( ! $method['fields']['enable_coupon'] ) {
            return false;
        }

        if ( ! is_array($applied_coupons) || empty($applied_coupons) ) {
            return false;
        }

        foreach ( $applied_coupons as $coupon ) {
            if ( mb_strtolower($method['fields']['coupon']) == mb_strtolower($coupon) ) {
                return true;
            }
        }

        return false;
    }

    private function getPriceFromTable($table_values, $cart_value, $default_value)
    {
        foreach ( $table_values as $values ) {
            if ( empty($values['value']) && ! empty($values['price']) ) {
                return $values['price'];
            }
            if ( is_numeric($cart_value) && $cart_value < $values['value'] ) {
                return $values['price'];
            } elseif ( $cart_value === $values['value'] ) {
                return $values['price'];
            }
        }

        return $default_value;
    }
}
