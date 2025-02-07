<?php

namespace Mijora\BoxCalculator\Elements;

class Box
{
    private $width = 0;
    private $height = 0;
    private $length = 0;
    private $volume = 0;
    private $outside_width = 0;
    private $outside_height = 0;
    private $outside_length = 0;
    private $outside_volume = 0;
    private $max_width = false;
    private $max_height = false;
    private $max_length = false;
    private $wall_thickness = 0;

    public function __construct($width, $height, $length, $wall_thickness = 0)
    {
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
        $this->volume = $this->calcVolume();
        $this->wall_thickness = $wall_thickness;
        $this->addWallThickness();
    }

    public function isEmpty()
    {
        return (empty($this->volume));
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setWidth($width)
    {
        $this->width = $width;
        
        return $this;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setHeight($height)
    {
        $this->height = $height;
        
        return $this;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function setLength($length)
    {
        $this->length = $length;
        
        return $this;
    }

    public function getVolume()
    {
        return $this->volume;
    }

    public function getOutsideWidth()
    {
        return $this->outside_width;
    }

    public function getOutsideHeight()
    {
        return $this->outside_height;
    }

    public function getOutsideLength()
    {
        return $this->outside_length;
    }

    public function getOutsideVolume()
    {
        return $this->outside_volume;
    }

    public function refreshOutsideSize()
    {
        $this->addWallThickness();

        return $this;
    }

    public function getMaxWidth()
    {
        return $this->max_width;
    }

    public function getMaxHeight()
    {
        return $this->max_height;
    }

    public function getMaxLength()
    {
        return $this->max_length;
    }

    public function setMaxSize($width, $height, $length)
    {
        $this->max_width = $width;
        $this->max_height = $height;
        $this->max_length = $length;

        return $this;
    }

    private function calcVolume($calc_outside = false)
    {
        $volume = $this->width * $this->height * $this->length;
        if ( $calc_outside ) {
            $volume = $this->outside_width * $this->outside_height * $this->outside_length;
        }
        
        return round($volume, 4);
    }

    private function addWallThickness()
    {
        $this->outside_width = round($this->width + $this->wall_thickness, 4);
        $this->outside_height = round($this->height + $this->wall_thickness, 4);
        $this->outside_length = round($this->length + $this->wall_thickness, 4);
        $this->outside_volume = $this->calcVolume(true);
    }
}
