<?php

namespace Mijora\BoxCalculator\Elements;

class Item
{
    private $width = 0;
    private $height = 0;
    private $length = 0;
    private $volume = 0;

    public function __construct($width, $height, $length)
    {
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
        $this->volume = round($this->width * $this->height * $this->length, 4);
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
}
