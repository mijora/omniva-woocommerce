<?php
class OmnivaLt_Method_Core
{
    protected $_id = '';
    protected $_key = '';
    protected $_type = 'parcel';
    protected $_title = '';
    protected $_front_title = '';
    protected $_title_logo = 'omniva_horizontal_s.png';
    protected $_prefix = 'Omniva';
    protected $_map_marker = 'omnivalt_icon.png';
    protected $_restrict_api = array();
    protected $_restrict_country = array();
    protected $_display_by_country = array();
    protected $_description = '';
    protected $_is_shipping_method =  false;
    protected $_terminals_type = '';
    protected $_max_weight = 0;
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
            'type' => $this->_type,
            'title' => $this->_title,
            'front_title' => $this->getFrontTitle(),
            'title_logo' => $this->_title_logo,
            'prefix' => $this->_prefix,
            'map_marker' => $this->_map_marker,
            'restrict_api' => $this->_restrict_api,
            'restrict_country' => $this->_restrict_country,
            'display_by_country' => $this->getDisplayByCountry(),
            'description' => $this->_description,
            'is_shipping_method' => $this->_is_shipping_method,
            'terminals_type' => $this->_terminals_type,
            'max_weight' => $this->_max_weight,
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

    public function setType($type)
    {
        $this->_type = $type;
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

    /**
     * Set for which country's API users are allowed to use this method.
     * If this value is not set, the method is allowed for all countries API users.
     * 
     * @param array|string $restrict_api List of API countries
     * @return OmnivaLt_Method_Core
     */
    public function setRestrictApi($restrict_api)
    {
        $this->_restrict_api = (is_array($restrict_api)) ? $restrict_api : array($restrict_api);
        return $this;
    }

    /**
     * Set for which countries to load this method.
     * If this value is not set, the method is allowed for all countries.
     * 
     * @param array|string $restrict_country List of countries
     * @return OmnivaLt_Method_Core
     */
    public function setRestrictCountry($restrict_country)
    {
        $this->_restrict_country = (is_array($restrict_country)) ? $restrict_country : array($restrict_country);
        return $this;
    }

    /**
     * Overwrite this method information for a specific recipient country (when a method needs to be displayed differently for a specific country)
     * 
     * @param array $param An array whose key is the recipient country to overwrite and array element is an array listing the values to overwrite
     * @return OmnivaLt_Method_Core
     */
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

    public function setMaxWeight($max_weight)
    {
        $this->_max_weight = $max_weight;
        return $this;
    }

    public function setParams($params)
    {
        $this->_params = $params;
        return $this;
    }
}
