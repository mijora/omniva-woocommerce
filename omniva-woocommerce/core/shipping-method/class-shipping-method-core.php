<?php
defined('OMNIVALT_VERSION') or die();

class OmnivaLt_Shipping_Method_Core
{

    private $_type = '';
    private $_key = '';
    private $_title = '';
    private $_img_url = '';
    private $_options_key = '';
    private $_methods = array();
    private $_settings = array();
    private $_current_method_key = '';

    protected $omniva_configs = array();

    public function __construct()
    {
        // Empty
    }

    final protected function setType( $type )
    {
        $this->_type = $type;
        return $this;
    }

    final public function getType()
    {
        return $this->_type;
    }

    final protected function setKey( $key )
    {
        $this->_key = $key;
        return $this;
    }

    final public function getKey()
    {
        return $this->_key;
    }

    final protected function setTitle( $title )
    {
        $this->_title = $title;
        return $this;
    }

    final public function getTitle()
    {
        return $this->_title;
    }

    final protected function setImgUrl( $img_url )
    {
        $this->_img_url = $img_url;
        return $this;
    }

    final public function getImgUrl()
    {
        return $this->_img_url;
    }

    final protected function setOptionsKey( $options_key )
    {
        $this->_options_key = $options_key;
        return $this;
    }

    final protected function getOptionsKey()
    {
        return $this->_options_key;
    }

    final protected function setMethods( $methods )
    {
        $this->_methods = $methods;
        return $this;
    }

    final public function getMethods()
    {
        return $this->_methods;
    }

    final protected function setSettings( $settings )
    {
        $this->_settings = $settings;
        return $this;
    }

    final public function getSettings()
    {
        return $this->_settings;
    }

    final public function setCurrentMethodKey( $method_key )
    {
        $this->_current_method_key = $method_key;
        return $this;
    }

    final public function getCurrentMethodKey()
    {
        return $this->_current_method_key;
    }

    final public function getCurrentMethod()
    {
        if ( ! isset($this->getMethods()[$this->getCurrentMethodKey()]) ) {
            return null;
        }
        return $this->getMethods()[$this->getCurrentMethodKey()];
    }

    protected function loadData()
    {
        $this->omniva_configs = OmnivaLt_Core::get_configs();

        if ( ! isset($this->omniva_configs['plugin']) || empty($this->getOptionsKey()) ) {
            return;
        }
        $all_settings = get_option($this->omniva_configs['plugin']['settings_key']);
        if ( isset($all_settings[$this->getOptionsKey()]) ) {
            $settings = json_decode($all_settings[$this->getOptionsKey()], true);
            if ( is_array($settings) ) {
                $this->setSettings($settings);
            }
        }
    }

    public function getCartMethodAmount( $cart_weight, $cart_amount, $get_only_amount = true )
    {
        if ( $get_only_amount ) {
            return 0;
        }

        return array(
            'amount' => 0,
            'meta_data' => array(),
        );
    }

    public function isCartMethodFreeByValue( $cart_amount )
    {
        return false;
    }

    public function isCartMethodFreeByCoupon( $applied_coupons )
    {
        return false;
    }
}
