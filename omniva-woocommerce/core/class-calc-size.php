<?php
class OmnivaLt_Calc_Size
{
    private $_box_margin = 1; //cm
    private $_edges = array('length', 'width', 'height');
    private $_total_size = array();
    private $_max_size = array();
    private $_items = array();
    private $_boxes = array();

    public function __construct( $items_data )
    {
        $this->prepare_variables();
        $this->prepare_items($items_data);
        $this->prepare_boxes();
    }

    private function prepare_variables()
    {
        foreach ( $this->_edges as $edge ) {
            $this->_total_size[$edge] = 0;
            $this->_max_size[$edge] = 999999;
        }
    }

    private function prepare_items( $items_data )
    {
        if ( ! is_array($items_data) ) {
            return;
        }

        $items_data = $this->spread_items($items_data);

        foreach ( $items_data as $item_data ) {
            $item = array();
            foreach ( $this->_edges as $edge ) {
                $item[$edge] = $item_data[$edge] ?? 0;
            }
            $this->_items[] = $item;
        }
    }

    private function spread_items( $items_data )
    {
        $spreaded_items = array();

        foreach ( $items_data as $item_data ) {
            if ( ! isset($item_data['quantity']) ) {
                $item_data['quantity'] = 1;
            }
            for( $i = 0; $i < $item_data['quantity']; $i++ ) {
                $item = array();
                foreach ( $item_data as $key => $value ) {
                    if ( $key == 'quantity' ) {
                        continue;
                    }
                    $item[$key] = $value;
                }
                $spreaded_items[] = $item;
            }
        }

        return $spreaded_items;
    }

    private function prepare_boxes()
    {
        foreach ( $this->_items as $item ) {
            $box = array();
            foreach ( $this->_edges as $edge ) {
                $box[$edge] = $item[$edge] + $this->_box_margin;
            }
            $this->_boxes[] = $box;
        }
    }

    private function rotate_boxes()
    {
        //TODO
    }

    public function set_max_size( $max_size )
    {
        if ( ! is_array($max_size) ) {
            return $this;
        }

        foreach ($this->_edges as $edge) {
            if ( isset($max_size[$edge]) ) {
                $this->_max_size[$edge] = $max_size[$edge];
            }
        }

        return $this;
    }

    public function set_box_margin( $margin_value )
    {
        $this->_box_margin = (float) $margin_value;

        return $this;
    }

    public function calc()
    {
        foreach ( $this->_boxes as $box ) {
            //TODO: Testi
        }

        return $this;
    }

    public function get_total_size()
    {
        return $this->_total_size;
    }
}
