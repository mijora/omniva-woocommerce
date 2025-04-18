<?php

namespace Mijora\BoxCalculator\Methods;

use Mijora\BoxCalculator\Elements\Box;
use Mijora\BoxCalculator\Debug;

class Core
{
    public $items = array();
    public $box;
    public $wall_thickness = 0;
    public $box_max_size = false;
    public $debug;

    public function __construct()
    {
        $this->debug = Debug::getInstance();
        $this->box = $this->updateBox(0, 0, 0);
    }

    /**************************************************
     * Init functions
     **************************************************/

    /**
     * Set items that will be placed in the box
     */
    public function setItems( $items )
    {
        $this->items = $items;
        return $this;
    }

    public function setBox( $box )
    {
        $this->box = $box;
        return $this;
    }

    public function setWallThickness( $wall_thickness )
    {
        $this->wall_thickness = $wall_thickness;
        $this->box = $this->updateBox($this->box->getWidth(), $this->box->getHeight(), $this->box->getLength());

        return $this;
    }

    public function setBoxMaxSize( $width, $height, $length )
    {
        $this->box->setMaxSize($width, $height, $length);
        $this->box_max_size = array($width, $height, $length);

        return $this;
    }

    public function loadDebug( $debug_class )
    {
        $this->debug = $debug_class;
        return $this;
    }

    /**************************************************
     * Common functions for methods
     **************************************************/

    protected function updateBox( $width, $height, $length )
    {
        $box = new Box($width, $height, $length, $this->wall_thickness);
        if ( $this->box_max_size ) {
            $box->setMaxSize($this->box_max_size[0], $this->box_max_size[1], $this->box_max_size[2]);
        }

        return $box;
    }

    protected function sortItemsByVolume( $items )
    {
        usort($items, function ($item1, $item2) {
            return $item2->getVolume() <=> $item1->getVolume();
        });

        return $items;
    }

    protected function rotateToPosition( $object, $position ) {
        $rotated = clone $object;
        $values = array(
            $object->{'get' . ucfirst($position[0])}(),
            $object->{'get' . ucfirst($position[1])}(),
            $object->{'get' . ucfirst($position[2])}()
        );

        $rotated->setWidth($values[0]);
        $rotated->setHeight($values[1]);
        $rotated->setLength($values[2]);

        return $rotated;
    }

    /**************************************************
     * Required functions overridden by methods
     **************************************************/

    public function findMinBoxSize()
    {
        return $this->box;
    }

    public function findBoxSizeUntilMaxSize()
    {
        if ( ! $this->box_max_size ) {
            $this->debug->add("Maximum box size not specified");
            return false;
        }

        return $this->box;
    }

    public function canFit()
    {
        if ( ! $this->box_max_size ) {
            $this->debug->add("Maximum box size not specified");
            return false;
        }

        return true;
    }
}
