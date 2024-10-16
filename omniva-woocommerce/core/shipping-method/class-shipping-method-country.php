<?php
defined('OMNIVALT_VERSION') or die();

class OmnivaLt_Shipping_Method_Country extends OmnivaLt_Shipping_Method_Core
{
    public function __construct( $country_code, $options_key )
    {
        $this->setType('country');
        $this->setKey($country_code);
        $this->setOptionsKey($options_key);
        $this->setTitle(OmnivaLt_Wc::get_country_name($this->getKey()));
        $this->setImgUrl(OMNIVALT_URL . 'assets/img/flags/' . strtolower($this->getKey()) . '.png');

        $this->loadData();
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

    protected function getMethodFields( $method_short_key )
    {
        $fields = parent::getMethodFields($method_short_key);

        if ( $method_short_key != 'pt' ) {
            unset($fields['price_by_boxsize']);
        }

        return $fields;
    }
}
