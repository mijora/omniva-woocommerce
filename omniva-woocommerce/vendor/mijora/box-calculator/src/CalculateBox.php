<?php

namespace Mijora\BoxCalculator;

use Mijora\BoxCalculator\Elements\Box;
use Mijora\BoxCalculator\Debug;

class CalculateBox
{
    public $items = array();
    public $box;
    public $wall_thickness = 0;
    public $box_max_size = false;
    public $debug;
    private $items_lines = array(); //TODO: In order to make more efficient use of space, items should be placed in lines when it adding in height. After filling one row as much as possible, only then create another.

    public function __construct($items)
    {
        $this->items = $items;
        $this->box = $this->updateBox(0, 0, 0);
        $this->debug = new Debug();
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
            'box' => $this->box,
            'actions' => $this->debug->getActions(),
        );
    }

    public function setBoxWallThickness($wall_thickness)
    {
        $this->wall_thickness = $wall_thickness;
        $this->box = $this->updateBox($this->box->getWidth(), $this->box->getHeight(), $this->box->getLength());

        return $this;
    }

    public function setMaxBoxSize($width, $height, $length)
    {
        $this->box->setMaxSize($width, $height, $length);
        $this->box_max_size = array($width, $height, $length);

        return $this;
    }

    public function findMinBoxSize()
    {
        $this->items = $this->sortItems($this->items);

        foreach ( $this->items as $item_id => $item ) {
            $this->debug->add('Adding item #' . $item_id . ': ' . $this->debug->obj($item, true));
            $item_longest_edge = $this->getLongestEdge($item);
            $this->debug->add('Item longest edge: ' . $item_longest_edge);
            $rotated_item = $this->rotateByEdge($item, $item_longest_edge);
            $this->debug->add("Rotated item: " . $this->debug->obj($rotated_item));

            if ( $this->box->isEmpty() ) {
                $this->box = $this->updateBox($rotated_item->getWidth(), $rotated_item->getHeight(), $rotated_item->getLength());
                $this->debug->add("Box empty. Box after first item: " . $this->debug->obj($this->box));
                $this->debug->end('ITEM ADD');
                continue;
            }
            $box_sortest_edge = $this->getSortestEdge($this->box);
            $this->box = $this->addItemToBox($this->box, $rotated_item, $box_sortest_edge);
            $this->debug->add("Item added to box. Box: " . $this->debug->obj($this->box));
            $this->debug->end('ITEM ADD');
        }

        return $this->box;
    }

    public function findBoxSizeUntilMaxSize()
    {
        if ( ! $this->box_max_size ) {
            $this->debug->add("Maximum box size not specified");
            return false;
        }

        $this->items = $this->sortItems($this->items);

        foreach ( $this->items as $item_id => $item ) {
            $this->debug->add('Adding item #' . $item_id . ': ' . $this->debug->obj($item, true));
            $is_placed = false;

            $orientations = ['width', 'height', 'length'];
            foreach ( $orientations as $orientation ) {
                $this->debug->add("Rotating item by edge: " . $orientation);
                $rotated_item = $this->rotateByEdge($item, $orientation);
                $this->debug->add("Rotated item: " . $this->debug->obj($rotated_item));

                foreach ( $orientations as $add_to_edge ) {
                    $this->debug->add("Rotating box by edge: " . $add_to_edge);
                    $temp_box = $this->addItemToBox($this->box, $rotated_item, $add_to_edge);
                    $this->debug->add("Temporary box received: " . $this->debug->obj($temp_box));

                    if ( $temp_box->getOutsideWidth() <= $this->box_max_size[0] &&
                        $temp_box->getOutsideHeight() <= $this->box_max_size[1] &&
                        $temp_box->getOutsideLength() <= $this->box_max_size[2] )
                    {
                        $this->debug->add("Box is smaller then: " . $this->debug->obj($this->box_max_size));
                        $this->box = $temp_box;
                        $this->debug->end('ITEM ADD');
                        $is_placed = true;
                        break 2;
                    }
                }
            }

            if ( ! $is_placed ) {
                $this->debug->add("Failed to insert item");
                $this->debug->end('ITEM ADD');
                return false;
            }
        }

        return $this->box;
    }

    public function canFit()
    {
        if ( ! $this->box_max_size ) {
            $this->debug->add("Maximum box size not specified");
            return false;
        }

        $this->items = $this->sortItems($this->items);
        $occupied = [];

        foreach ( $this->items as $item_id => $item ) {
            $this->debug->add('Adding item #' . $item_id . ': ' . $this->debug->obj($item, true));
            $is_placed = false;
            for ($l = 0; $l <= $this->box_max_size[2]; $l++) {
                for ($w = 0; $w <= $this->box_max_size[0]; $w++) {
                    for ($h = 0; $h <= $this->box_max_size[1]; $h++) {
                        $position = ['l' => $l, 'w' => $w, 'h' => $h];
                        if ( $this->canPlaceItem($item, $position, $occupied) ) {
                            $this->debug->add('Adding to position: ' . $this->debug->obj($position, true));
                            $occupied[] = array_merge($position, array(
                                'length' => $item->getLength(),
                                'width' => $item->getWidth(),
                                'height' => $item->getHeight(),
                            ));
                            $this->debug->add('Added: ' . $this->debug->obj($occupied, true)); //TODO: Patikrinti ar taip galima
                            $is_placed = true;
                            break 3;
                        }
                    }
                }
            }

            if ( ! $is_placed ) {
                $this->debug->add("Failed to insert item");
                $this->debug->end('ITEM ADD');
                return false;
            }
        }

        return true;
    }

    private function canPlaceItem($item, $position, $occupied)
    {
        if (
            $position['l'] + $item->getLength() > $this->box_max_size[2] ||
            $position['w'] + $item->getWidth() > $this->box_max_size[0] ||
            $position['h'] + $item->getHeight() > $this->box_max_size[1]
        ) {
            return false;
        }

        foreach ($occupied as $o) {
            if (
                $position['l'] < $o['l'] + $o['length'] &&
                $position['l'] + $item->getLength() > $o['l'] &&
                $position['w'] < $o['w'] + $o['width'] &&
                $position['w'] + $item->getWidth() > $o['w'] &&
                $position['h'] < $o['h'] + $o['height'] &&
                $position['h'] + $item->getHeight() > $o['h']
            ) {
                return false;
            }
        }

        return true;
    }

    private function updateBox($width, $height, $length)
    {
        $box = new Box($width, $height, $length, $this->wall_thickness);
        if ( $this->box_max_size ) {
            $box->setMaxSize($this->box_max_size[0], $this->box_max_size[1], $this->box_max_size[2]);
        }

        return $box;
    }

    private function sortItems($items)
    {
        usort($items, function ($item1, $item2) {
            return $item2->getVolume() <=> $item1->getVolume();
        });

        return $items;
    }

    private function addItemToBox($box, $item, $add_to_edge)
    {
        $this->debug->add('Adding item to box edge: ' . $add_to_edge);

        $new_box_width = $box->getWidth();
        $new_box_height = $box->getHeight();
        $new_box_length = $box->getLength();
        if ( $add_to_edge == 'width' ) {
            $new_box_width += $item->getWidth();
            if ( $item->getHeight() > $new_box_height ) {
                $new_box_height = $item->getHeight();
            }
            if ( $item->getLength() > $new_box_length ) {
                $new_box_length = $item->getLength();
            }
        } else if ( $add_to_edge == 'height' ) {
            $new_box_height += $item->getHeight();
            if ( $item->getWidth() > $new_box_width ) {
                $new_box_width = $item->getWidth();
            }
            if ( $item->getLength() > $new_box_length ) {
                $new_box_length = $item->getLength();
            }
        } else if ( $add_to_edge == 'length' ) {
            $new_box_length += $item->getLength();
            if ( $item->getWidth() > $new_box_width ) {
                $new_box_width = $item->getWidth();
            }
            if ( $item->getHeight() > $new_box_height ) {
                $new_box_height = $item->getHeight();
            }
        }

        return $this->updateBox($new_box_width, $new_box_height, $new_box_length);
    }

    private function getSortestEdge($object)
    {
        $min_value = min($object->getWidth(), $object->getHeight(), $object->getLength());

        return array_search($min_value, array(
            'width' => $object->getWidth(),
            'height' => $object->getHeight(),
            'length' => $object->getLength()
        ));
    }

    private function getLongestEdge($object)
    {
        $max_value = max($object->getWidth(), $object->getHeight(), $object->getLength());

        return array_search($max_value, array(
            'width' => $object->getWidth(),
            'height' => $object->getHeight(),
            'length' => $object->getLength()
        ));
    }

    private function rotateByEdge($object, $edge)
    {
        $new_width = 0;
        $new_height = 0;
        $new_length = 0;
        switch ($edge) {
            case 'height':
                $new_width = $object->getHeight();
                $new_height = $object->getWidth();
                $new_length = $object->getLength();
                break;
            case 'length':
                $new_width = $object->getLength();
                $new_height = $object->getHeight();
                $new_length = $object->getWidth();
                break;
            default:
                $new_width = $object->getWidth();
                $new_height = $object->getHeight();
                $new_length = $object->getLength();
        }

        $object->setWidth($new_width);
        $object->setHeight($new_height);
        $object->setLength($new_length);

        if ($new_height < $new_length) {
            $object->setHeight($new_length);
            $object->setLength($new_height);
        }

        return $object;
    }
}
