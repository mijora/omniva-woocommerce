<?php

use \Mijora\Omniva\ServicePackageHelper\ServicePackageHelper;
use \Mijora\Omniva\ServicePackageHelper\PackageItem;

class OmnivaLt_Api_International
{
    private $package_titles = array();
    private $regions_titles = array();
    private $units = array('weight' => 'kg', 'dimension' => 'm');

    public function __construct()
    {
        $this->package_titles = array(
            'premium' => __('Premium', 'omnivalt'),
            'standard' => __('Standard', 'omnivalt'),
            'economy' => __('Economy', 'omnivalt'),
        );
        $this->regions_titles = array(
            'eu' => __('European Union', 'omnivalt'),
            'non' => __('Not the European Union', 'omnivalt'),
        );
    }

    public function get_all_services()
    {
        return ServicePackageHelper::getServices();
    }

    public function get_country_data( $country_code )
    {
        return ServicePackageHelper::getCountryOptions($country_code);
    }

    public function get_package_title( $package_code )
    {
        return (isset($this->package_titles[$package_code])) ? $this->package_titles[$package_code] : $package_code;
    }

    public function get_region_title( $region_key )
    {
        return (isset($this->regions_titles[$region_key])) ? $this->regions_titles[$region_key] : $region_key;
    }

    public function get_units( $unit_type = false )
    {
        return (isset($this->units[$unit_type])) ? $this->units[$unit_type] : $this->units;
    }

    public function get_available_packages()
    {
        $packages = array();
        $all_services = $this->get_all_services();
        if ( ! is_array($all_services) ) {
            return $packages;
        }

        foreach ( $all_services as $country_code => $service ) {
            if ( ! isset($service['package']) || ! is_array($service['package']) ) {
                continue;
            }
            foreach ( $service['package'] as $package_key => $package_data ) {
                if ( ! isset($packages[$package_key]) ) {
                    $packages[$package_key] = array();
                }
                $region = ($service['eu']) ? 'eu' : 'non';
                $packages[$package_key][$region][] = (isset($service['iso'])) ? $service['iso'] : $country_code;
            }
        }

        return $packages;
    }

    public function get_package_code( $package_code )
    {
        return ServicePackageHelper::getServicePackageCode($package_code);
    }

    public function get_all_available_regions()
    {
        $regions = array();
        foreach ( $this->get_available_packages() as $package_key => $package_regions ) {
            foreach ( array_keys($package_regions) as $region_key ) {
                if ( ! in_array($region_key, $regions) ) {
                    $regions[] = $region_key;
                }
            }
        }
        return $regions;
    }

    public function get_package_regions( $package_key )
    {
        $regions = array();
        $packages = $this->get_available_packages();
        if ( ! isset($packages[$package_key]) ) {
            return $regions;
        }
        return array_keys($packages[$package_key]);
    }

    public function get_all_available_countries()
    {
        $countries = array();
        foreach ( $this->get_available_packages() as $package_key => $package_regions ) {
            foreach ( $package_regions as $region_key => $region_countries ) {
                foreach ( $region_countries as $country ) {
                    if ( ! in_array($country, $countries) ) {
                        $countries[] = $country;
                    }
                }
            }
        }
        return $countries;
    }

    public function get_country_package_data( $country, $package_key )
    {
        if ( empty($country) ) {
            return false;
        }

        $country_data = $this->get_country_data($country);
        if ( empty($country_data['package']) || ! isset($country_data['package'][$package_key]) ) {
            return false;
        }

        $package_data = $country_data['package'][$package_key];
        return array(
            'units' => array('weight' => 'kg', 'measurement' => 'm'),
            'max_weight' => (isset($package_data['maxWeightKg'])) ? $package_data['maxWeightKg'] : false,
            'longest_side' => (isset($package_data['maxDimensionsM']) && isset($package_data['maxDimensionsM']['longestSide'])) ? $package_data['maxDimensionsM']['longestSide'] : false,
            'max_perimeter' => (isset($package_data['maxDimensionsM']) && isset($package_data['maxDimensionsM']['total'])) ? $package_data['maxDimensionsM']['total'] : false,
            'insurance' => (isset($package_data['insurance'])) ? $package_data['insurance'] : false,
        );
    }

    public function is_package_available_for_items( $package_code, $country_code, $items )
    {
        if ( ! $this->get_package_code($package_code) || empty($this->get_country_data($country_code)) || empty($items) ) {
            return false;
        }

        $package_items = array();
        foreach ( $items as $item ) {
            $package_items[] = new PackageItem($item['weight'], $item['length'], $item['width'], $item['height']);
        }
        $available_packages = ServicePackageHelper::getAvailablePackages($country_code, $package_items);

        if ( ! in_array($package_code, $available_packages) ) {
            return false;
        }

        return true;
    }
}
