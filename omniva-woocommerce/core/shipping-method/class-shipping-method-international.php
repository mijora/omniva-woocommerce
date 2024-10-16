<?php
defined('OMNIVALT_VERSION') or die();

class OmnivaLt_Shipping_Method_International extends OmnivaLt_Shipping_Method_Core
{
    private $current_country = false;
    private $cart_products = array();
    private $service_key = null;

    public function __construct( $data, $options_key )
    {
        $this->setType('international');
        $this->setKey($data['key']);
        $this->setOptionsKey($options_key);
        $this->setTitle($data['title']);
        $this->setImgUrl(OMNIVALT_URL . 'assets/img/plans/' . strtolower($this->getKey()) . '.svg');
        if ( isset($data['country']) ) {
            $this->setCurrentCountry($data['country']);
        }

        $this->loadData();
    }

    protected function loadData()
    {
        parent::loadData();

        $methods = array();
        $api = new OmnivaLt_Api_International();
        $regions = $api->get_package_regions($this->getKey());
        foreach ( $regions as $region_key ) {
            if ( ! isset($methods[$region_key]) ) {
                $methods[$region_key] = array();
            }
            $methods[$region_key]['key'] = $this->getKey() . '_' . $region_key;
            $methods[$region_key]['front_title'] = __('International', 'omnivalt') . ' (' . $this->getTitle() . ')';
            $methods[$region_key]['prefix'] = 'Omniva';
            $methods[$region_key]['fields'] = $this->getMethodFieldsData($region_key);
            $methods[$region_key]['params'] = (!empty($this->getCurrentCountry())) ? $api->get_country_package_data($this->getCurrentCountry(), $this->getKey()) : false;
        }
        $this->setServiceKey($api->get_package_code($this->getKey()));
        $this->setMethods($methods);
    }

    public function setCurrentCountry( $country )
    {
        $this->current_country = $country;
        return $this;
    }

    public function getCurrentCountry()
    {
        return $this->current_country;
    }

    public function setServiceKey( $service_key )
    {
        $this->service_key = $service_key;
        return $this;
    }

    public function getServiceKey()
    {
        return $this->service_key;
    }

    public function setCartProducts( $products )
    {
        $this->cart_products = $products;
        return $this;
    }

    public function getCartProducts()
    {
        return $this->cart_products;
    }

    public function ifServiceAvaible()
    {
        if ( empty($this->getCartProducts()) || empty($this->getServiceKey()) || empty($this->getCurrentCountry()) ) {
            return false;
        }

        $api = new OmnivaLt_Api_International();
        $items = array();
        foreach ( $this->getCartProducts() as $product ) {
            $items[] = array(
                'weight' => OmnivaLt_Wc::get_weight((float)$product->get_weight(), $api->get_units('weight')),
                'length' => OmnivaLt_Wc::get_dimension((float)$product->get_length(), $api->get_units('dimension')),
                'width' => OmnivaLt_Wc::get_dimension((float)$product->get_width(), $api->get_units('dimension')),
                'height' => OmnivaLt_Wc::get_dimension((float)$product->get_height(), $api->get_units('dimension')),
            );
        }

        return $api->is_package_available_for_items($this->getServiceKey(), $this->getCurrentCountry(), $items);
    }
}
