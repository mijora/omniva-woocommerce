<?php

namespace Mijora\BoxCalculator\Methods;

use Mijora\BoxCalculator\Methods\Core;

class AddToEdge extends Core
{
    private $orientations = array(
        array('width', 'height', 'length'),
        array('width', 'length', 'height'),
        array('height', 'width', 'length'),
        array('height', 'length', 'width'),
        array('length', 'width', 'height'),
        array('length', 'height', 'width')
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function findBoxSizeUntilMaxSize()
    {
        if ( ! $this->box_max_size ) {
            $this->debug->add("Maximum box size not specified");
            return false;
        }

        $this->items = $this->sortItemsByVolume($this->items);

        foreach ( $this->items as $item_id => $item ) {
            $this->debug->add('Adding item #' . $item_id . ': ' . $this->debug->obj($item, true));
            $is_placed = false;

            foreach ( $this->orientations as $orientation ) {
                $this->debug->add("Rotating item to position: " . implode(' x ', $orientation));
                $rotated_item = $this->rotateToPosition($item, $orientation);
                $this->debug->add("Rotated item: " . $this->debug->obj($rotated_item));

                foreach ( $this->orientations[0] as $add_to_edge ) {
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
                $this->debug->add("Failed to insert item #" . $item_id);
                $this->debug->end('ITEM ADD');
                return false;
            }
        }

        return $this->box;
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

    public function findMinBoxSize()
    {
        $this->items = $this->sortItemsByVolume($this->items);

        foreach ( $this->items as $item_id => $item ) {
            $this->debug->add('Adding item #' . $item_id . ': ' . $this->debug->obj($item, true));
            $item_longest_edge = $this->getLongestEdge($item);
            $this->debug->add('Item longest edge: ' . $item_longest_edge);
            $rotated_item = $this->rotateByEdge($item, $item_longest_edge);
            $this->debug->add("Rotated item: " . $this->debug->obj($rotated_item));

            if ( $this->box->isEmpty() ) {
                $this->box = $this->updateBox(
                    $rotated_item->getWidth(),
                    $rotated_item->getHeight(),
                    $rotated_item->getLength()
                );
                $this->debug->add("Box empty. Box after first item: " . $this->debug->obj($this->box));
                $this->debug->end('ITEM ADD');
            }
        }

        return $this->box;
    }

    private function getLongestEdge( $object )
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
