<?php

namespace Mijora\BoxCalculator;

use Mijora\BoxCalculator\Elements\Box;
use Mijora\BoxCalculator\Debug;

class CalculateBox
{
    public $items = array();
    public $wall_thickness = 0;
    public $box_max_size = false;
    public $debug;
    
    private $method_class;
    private $method;
    private $all_methods = array(
        'AddToEdge',
        'Heuristic3D'
    );

    public function __construct($items)
    {
        $this->debug = Debug::getInstance();
        $this->setMethod('AddToEdge');
        $this->items = $items;
    }

    public function enableDebug($enable)
    {
        $this->debug->enable($enable);
        return $this;
    }

    public function getDebugData()
    {
        return array(
            'items' => $this->items,
            'box' => ($this->method) ? $this->method->box : 'Method not loaded',
            'actions' => $this->debug->getActions(),
        );
    }

    public function setBoxWallThickness($wall_thickness)
    {
        $this->wall_thickness = $wall_thickness;
        return $this;
    }

    public function setMaxBoxSize($width, $height, $length)
    {
        $this->box_max_size = array(
            'width' => $width,
            'height' => $height,
            'length' => $length
        );
        return $this;
    }

    public function setMethod( $method )
    {
        if ( ! in_array($method, $this->all_methods) ) {
            $this->debug->add('The method "' . $method . '" is not allowed. Using method "' . $this->method_class . '"...');
            return $this;
        }

        $this->method_class = $method;
        $this->loadMethod();
        return $this;
    }

    public function getAvailableMethods()
    {
        return $this->all_methods;
    }

    private function loadMethod()
    {
        $full_class_name = 'Mijora\\BoxCalculator\\Methods\\' . $this->method_class;

        if ( empty($this->method_class) ) {
            $this->debug->add('An empty method class name was received');
            return $this;
        }
        if ( ! class_exists($full_class_name) ) {
            $this->debug->add('Failed to load method class ' . $full_class_name);
            return $this;
        }

        $this->method = new $full_class_name();
        return $this;
    }

    public function findBoxSizeUntilMaxSize()
    {
        if ( ! $this->method ) {
            $this->debug->add('Method not loaded');
            return false;
        }

        $this->method->setItems($this->items);
        $this->method->setWallThickness($this->wall_thickness);
        if ( $this->box_max_size ) {
            $this->method->setBoxMaxSize(
                $this->box_max_size['width'],
                $this->box_max_size['height'],
                $this->box_max_size['length']
            );
        }

        return $this->method->findBoxSizeUntilMaxSize();
    }

    public function findMinBoxSize()
    {
        if ( ! $this->method ) {
            $this->debug->add('Method not loaded');
            return false;
        }

        $this->method->setItems($this->items);
        $this->method->setWallThickness($this->wall_thickness);

        return $this->method->findMinBoxSize();
    }
}
