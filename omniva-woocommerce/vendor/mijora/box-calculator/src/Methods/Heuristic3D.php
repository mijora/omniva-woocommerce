<?php

namespace Mijora\BoxCalculator\Methods;

use Mijora\BoxCalculator\Methods\Core;

class Heuristic3D extends Core
{
    private $placed_items = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function findMinBoxSize()
    {
        $this->debug->add("The method used does not have the ability to calculate the minimum box size");
        return $this->box;
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
            $placed = false;

            foreach ( $this->getItemRotations($item) as $rotated_item ) {
                $this->debug->add('Checking rotated item: ' . $this->debug->obj($rotated_item, true));
                $position = $this->findFreePosition($rotated_item);
                if ( $position ) {
                    $this->debug->add('Found suitable position: ' . implode(' - ' , $position) );
                    $this->placed_items[] = array(
                        'item' => $rotated_item,
                        'position' => $position
                    );
                    $placed = true;
                    $this->box = $this->calculateBoxSizeFromItems();
                    $this->debug->add("New box size: " . $this->debug->obj($this->box));
                    $this->debug->end('ITEM ADD');
                    break;
                }
            }

            if ( ! $placed ) {
                $this->debug->add("Failed to insert item #" . $item_id);
                $this->debug->end('ITEM ADD');
                return false;
            }
        }

        return $this->box;
    }

    private function getItemRotations($item)
    {
        $w = $item->getWidth();
        $l = $item->getLength();
        $h = $item->getHeight();

        $rotations = array(
            array('w' => $w, 'l' => $l, 'h' => $h),
            array('w' => $w, 'l' => $h, 'h' => $l),
            array('w' => $l, 'l' => $w, 'h' => $h),
            array('w' => $l, 'l' => $h, 'h' => $w),
            array('w' => $h, 'l' => $w, 'h' => $l),
            array('w' => $h, 'l' => $l, 'h' => $w),
        );

        $rotated_items = array();
        foreach ( $rotations as $rotation ) {
            $cloned_item = clone $item;
            $cloned_item->setWidth($rotation['w']);
            $cloned_item->setHeight($rotation['h']);
            $cloned_item->setLength($rotation['l']);
            $rotated_items[] = $cloned_item;
        }

        return $rotated_items;
    }

    private function findFreePosition( $item )
    {
        $step = 1;
        for ( $x = 0; $x <= $this->box->getMaxWidth() - $this->wall_thickness - $item->getWidth(); $x += $step ) {
            for ( $y = 0; $y <= $this->box->getMaxHeight() - $this->wall_thickness - $item->getHeight(); $y += $step ) {
                for ( $z = 0; $z <= $this->box->getMaxLength() - $this->wall_thickness - $item->getLength(); $z += $step ) {
                    $pos = ['x' => $x, 'y' => $y, 'z' => $z];
                    if ( ! $this->isCollide($pos, $item) ) {
                        return $pos;
                    }
                }
            }
        }

        return false;
    }

    private function isCollide( $pos, $item )
    {
        foreach ( $this->placed_items as $placed ) {
            if ( $this->checkIntersects($pos, $item, $placed['position'], $placed['item']) ) {
                return true;
            }
        }
        return false;
    }

    private function checkIntersects( $pos1, $item1, $pos2, $item2 )
    {
        return !(
            $pos1['x'] + $item1->getWidth() <= $pos2['x'] ||
            $pos2['x'] + $item2->getWidth() <= $pos1['x'] ||
            $pos1['y'] + $item1->getHeight() <= $pos2['y'] ||
            $pos2['y'] + $item2->getHeight() <= $pos1['y'] ||
            $pos1['z'] + $item1->getLength() <= $pos2['z'] ||
            $pos2['z'] + $item2->getLength() <= $pos1['z']
        );
    }

    private function calculateBoxSizeFromItems()
    {
        $max_width = 0;
        $max_height = 0;
        $max_length = 0;

        foreach ( $this->placed_items as $placed ) {
            $item = $placed['item'];
            $pos = $placed['position'];

            $max_width = max($max_width, $pos['x'] + $item->getWidth());
            $max_height = max($max_height, $pos['y'] + $item->getHeight());
            $max_length = max($max_length, $pos['z'] + $item->getLength());
        }

        return $this->updateBox($max_width, $max_height, $max_length);
    }
}
