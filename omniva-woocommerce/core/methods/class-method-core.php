<?php
class OmnivaLt_Method_Core
{
    protected $_id = '';
    protected $_key = '';
    protected $_title = '';
    protected $_front_title = '';
    protected $_title_logo = 'omniva_horizontal_s.png';
    protected $_prefix = 'Omniva';
    protected $_map_marker = 'omnivalt_icon.png';
    protected $_display_by_country = array();
    protected $_description = '';
    protected $_is_shipping_method =  false;
    protected $_terminals_type = '';
    protected $_default_weight = 0;
    protected $_params = array();

    public function __construct()
    {
        // Empty
    }

    public function getData()
    {
        return array(
            'id' => $this->_id,
            'key' => $this->_key,
            'title' => $this->_title,
            'front_title' => $this->getFrontTitle(),
            'title_logo' => $this->_title_logo,
            'prefix' => $this->_prefix,
            'map_marker' => $this->_map_marker,
            'display_by_country' => $this->getDisplayByCountry(),
            'description' => $this->_description,
            'is_shipping_method' => $this->_is_shipping_method,
            'terminals_type' => $this->_terminals_type,
            'default_weight' => $this->_default_weight,
            'params' => $this->_params,
        );
    }

    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    public function setKey($key)
    {
        $this->_key = $key;
        return $this;
    }

    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    public function setFrontTitle($front_title)
    {
        $this->_front_title = $front_title;
        return $this;
    }

    public function getFrontTitle()
    {
        return (! empty($this->_front_title)) ? $this->_front_title : $this->_title;
    }

    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
        return $this;
    }

    public function setMapMarker($marker_file_name)
    {
        $this->_map_marker = $marker_file_name;
        return $this;
    }

    public function setTitleLogo($logo_file_name)
    {
        $this->_title_logo = $logo_file_name;
        return $this;
    }

    public function setDisplayByCountry($params)
    {
        $this->_display_by_country = $params;
        return $this;
    }

    private function getDisplayByCountry()
    {
        $display = array();
        foreach ( $this->_display_by_country as $country_key => $country_params ) {
            $display[$country_key] = array(
                'title' => $country_params['title'] ?? $this->_title,
                'front_title' => $country_params['front_title'] ?? $this->getFrontTitle(),
                'title_logo' => $country_params['title_logo'] ?? $this->_title_logo,
                'prefix' => $country_params['prefix'] ?? $this->_prefix,
                'map_marker' => $country_params['map_marker'] ?? $this->_map_marker,
            );
        }

        return $display;
    }

    public function setDescription($description)
    {
        $this->_description = $description;
        return $this;
    }

    public function setIsShippingMethod($is_shipping_method)
    {
        $this->_is_shipping_method = (bool) $is_shipping_method;
        return $this;
    }

    public function setTerminalsType($terminals_type)
    {
        $this->_terminals_type = $terminals_type;
        return $this;
    }

    public function setDefaultWeight($default_weight)
    {
        $this->_default_weight = $default_weight;
        return $this;
    }

    public function setParams($params)
    {
        $this->_params = $params;
        return $this;
    }
}
