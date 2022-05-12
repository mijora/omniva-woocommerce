<?php

/**
 * Largest Area Fit First (LAFF) 3D box packing algorithm class
 *
 * @author    Maarten de Boer <info@maartendeboer.net>
 * @copyright Maarten de Boer 2012
 * @version   1.1.0
 */
class OmnivaLt_Packer
{
    // Give margin for error when packing
    const MARGIN_FOR_PACKING = 0.9;
    const MAX_EDGE = 640 * self::MARGIN_FOR_PACKING;

    /**
     * Available container sizes
     *
     * @var array
     */
    private $containers = [
        [
            'length' => 640,
            'height' => 90,
            'width' => 380,
            'volume' => 21888000,
        ],
        [
            'length' => 640,
            'height' => 190,
            'width' => 380,
            'volume' => 46208000,
        ],
        [
            'length' => 640,
            'height' => 390,
            'width' => 380,
            'volume' => 94848000,
        ],
    ];

    /**
     * Container sizes with calculated margin for error
     *
     * @var array
     */
    private $adjusted_containers;

    /**
     * Current container index
     *
     * @var int
     */
    private $current_container;

    /**
     * Current container Volume
     *
     * @var float|int
     */
    private $current_container_remaining_volume;


    /**
     * For checking if fits
     *
     * @var boolean
     */
    private $fits;


    /**
     * Current containers maximum height
     *
     * @var float|int
     */
    private $max_height;

    /**
     * Array of boxes to pack
     *
     * @var array
     */
    private $boxes = null;


    /**
     * Given initial boxes
     *
     * @var array
     */
    private $starting_boxes;

    /**
     * Array of boxes that have been packed
     *
     * @var array
     */
    private $packed_boxes = null;

    /**
     * Current level we're packing (0 based index)
     *
     * @var int
     */
    private $level = -1;

    /**
     * Current container dimensions
     *
     * @var array
     */
    private $container_dimensions = null;

    /**
     * Constructor of the BoxPacking class
     *
     * @param array $boxes Array of boxes to pack
     */
    public function __construct($boxes = null)
    {
        $this->current_container = 0;

        if (isset($boxes) && is_array($boxes)) {
            $this->boxes = $this->rotate_boxes($boxes);
            $this->starting_boxes = $this->boxes;
            $this->packed_boxes = array();
        }

        // Allow margin errors for packing
        $this->adjusted_containers = $this->add_margin_for_error($this->containers);
    }

    /**
     *
     * Rotates boxes so that longest edge is 'length' and second longest edge is 'width'
     *
     * @param $boxes
     * @return array
     */
    private function rotate_boxes($boxes)
    {
        $rotated_boxes = [];

        foreach ($boxes as $box) {
            $edges = array('length', 'width', 'height');
            $le = $this->_calc_longest_edge(array($box));
            $edges = array_diff($edges, array($le['edge_name']));
            $sle = $this->_calc_longest_edge(array($box), $edges);
            $edges = array_values(array_diff($edges, array($sle['edge_name'])));

            $rotated_boxes[] = [
                "length" => $le['edge_size'],
                'width' => $sle['edge_size'],
                'height' => $box[$edges[0]],
            ];
        }

        return $rotated_boxes;
    }

    /**
     * Calculates containers with margin for error
     *
     * @param $containers
     * @return array
     */
    private function add_margin_for_error($containers)
    {
        return array_map(
            function ($array_element) {
                return array_map(
                    function ($element_dimension) {
                        return $element_dimension * self::MARGIN_FOR_PACKING;
                    },
                    $array_element);
            },
            $containers
        );
    }

    /**
     * Start packing boxes
     *
     */
    public function pack()
    {
        $this->fits = false;

        if (isset($this->boxes) && is_array($this->boxes)) {
            $this->level = -1;
            $this->container_dimensions = null;

        }

        $this->max_height = $this->adjusted_containers[$this->current_container]['height'];
        $this->current_container_remaining_volume = $this->adjusted_containers[$this->current_container]['volume'];
        // Calculate container size
        $this->container_dimensions['length'] = $this->adjusted_containers[$this->current_container]['length'];
        $this->container_dimensions['width'] = $this->adjusted_containers[$this->current_container]['width'];

        // Note: do NOT set height, it will be calculated on-the-go
        $this->container_dimensions['height'] = 0;

        if (!isset($this->boxes)) {
            throw new \InvalidArgumentException("No boxes!");
        }

        $longest_edge = $this->_calc_longest_edge($this->boxes);
        if ($longest_edge['edge_size'] > self::MAX_EDGE) {
            return false;
        }

        $this->pack_level();

        if (!$this->fits) {
            $this->current_container++;
            // We have only 3 size containers
            if ($this->current_container > 2) {
                return;
            }
            $this->boxes = $this->starting_boxes;
            $this->pack();
        }

        if ($this->fits && $this->current_container <= 2) {
        	if($this->current_container === 0) {
        		return 'S';
	        }
	        if($this->current_container === 1) {
		        return 'M';
	        }
	        if($this->current_container === 2) {
		        return 'L';
	        }
        }

        return false;
    }

    /**
     * Get remaining boxes to pack
     *
     * @return array
     */
    public function get_remaining_boxes()
    {
        return $this->boxes;
    }

    /**
     * Get packed boxes
     *
     * @return array
     */
    public function get_packed_boxes()
    {
        return $this->packed_boxes;
    }

    /**
     * Get container dimensions
     *
     * @return array
     */
    public function get_container_dimensions()
    {
        return $this->container_dimensions;
    }

    /**
     * Get container volume
     *
     * @return float
     */
    public function get_container_volume()
    {
        if (!isset($this->container_dimensions)) {
            return 0;
        }

        return $this->_get_volume($this->container_dimensions);
    }

    /**
     * Get number of levels
     *
     * @return int
     */
    public function get_levels()
    {
        return $this->level + 1;
    }

    /**
     * Get total volume of packed boxes
     *
     * @return float
     */
    public function get_packed_volume()
    {
        if (!isset($this->packed_boxes)) {
            return 0;
        }

        $volume = 0;

        for ($i = 0; $i < count(array_keys($this->packed_boxes)); $i++) {
            foreach ($this->packed_boxes[$i] as $box) {
                $volume += $this->_get_volume($box);
            }
        }

        return $volume;
    }

    /**
     * Get number of levels
     *
     * @return int
     */
    public function get_remaining_volume()
    {
        if (!isset($this->packed_boxes)) {
            return 0;
        }

        $volume = 0;

        foreach ($this->boxes as $box) {
            $volume += $this->_get_volume($box);
        }

        return $volume;
    }

    /**
     * Get dimensions of specified level
     *
     * @param int $level
     *
     * @return array
     */
    public function get_level_dimensions($level = 0)
    {
        if ($level < 0 || $level > $this->level || !array_key_exists($level, $this->packed_boxes)) {
            throw new \OutOfRangeException(sprintf('Level %d not found!', $level));
        }

        $boxes = $this->packed_boxes;
        $edges = array('length', 'width', 'height');

        // Get longest edge
        $le = $this->_calc_longest_edge($boxes[$level], $edges);
        $edges = array_diff($edges, array($le['edge_name']));

        // Re-iterate and get longest edge now (second longest)
        $sle = $this->_calc_longest_edge($boxes[$level], $edges);

        return array(
            'width' => $le['edge_size'],
            'length' => $sle['edge_size'],
            'height' => $boxes[$level][0]['height']
        );
    }

    /**
     * Get longest edge from boxes
     *
     * @param array $boxes
     * @param array $edges Edges to select the longest from
     *
     * @return array
     */
    public function _calc_longest_edge($boxes, $edges = array('length', 'width', 'height'))
    {
        if (!isset($boxes) || !is_array($boxes)) {
            throw new \InvalidArgumentException('_calc_longest_edge function requires an array of boxes, ' . count($boxes) . ' given');
        }

        // Longest edge
        $le = null;        // Longest edge
        $lef = null;    // Edge field (length | width | height) that is longest

        // Get longest edges
        foreach ($boxes as $k => $box) {
            foreach ($edges as $edge) {
                if (array_key_exists($edge, $box) && $box[$edge] > $le) {
                    $le = $box[$edge];
                    $lef = $edge;
                }
            }
        }

        return array(
            'edge_size' => $le,
            'edge_name' => $lef
        );
    }

    /**
     * Calculate container dimensions
     *
     * @return array
     */
    public function _calc_container_dimensions()
    {
        if (!isset($this->boxes)) {
            return array(
                'length' => 0,
                'width' => 0,
                'height' => 0
            );
        }

        $boxes = $this->boxes;

        $edges = array('length', 'width', 'height');

        // Get longest edge
        $le = $this->_calc_longest_edge($boxes, $edges);
        $edges = array_diff($edges, array($le['edge_name']));

        // Re-iterate and get longest edge now (second longest)
        $sle = $this->_calc_longest_edge($boxes, $edges);

        return array(
            'length' => $le['edge_size'],
            'width' => $sle['edge_size'],
            'height' => 0
        );
    }

    /**
     * Utility function to swap two elements in an array
     *
     * @param array $array
     * @param mixed $el1 Index of item to be swapped
     * @param mixed $el2 Index of item to swap with
     *
     * @return array
     */
    public function _swap($array, $el1, $el2)
    {
        if (!array_key_exists($el1, $array) || !array_key_exists($el2, $array)) {
            throw new \InvalidArgumentException("Both element to be swapped need to exist in the supplied array");
        }

        $tmp = $array[$el1];
        $array[$el1] = $array[$el2];
        $array[$el2] = $tmp;

        return $array;
    }

    /**
     * Utility function that returns the total volume of a box / container
     *
     * @param array $box
     *
     * @return float
     */
    public function _get_volume($box)
    {
        if (!is_array($box) || count(array_keys($box)) < 3) {
            throw new \InvalidArgumentException("_get_volume function only accepts arrays with 3 values (length, width, height)");
        }

        $box = array_filter($box, 'strlen');

        return (isset($box['length']) ? $box['length'] : $box[0]) * (isset($box['width']) ? $box['width'] : $box[1]) * (isset($box['height']) ? $box['height'] : $box[2]);;
    }

    /**
     * Check if box fits in specified space
     *
     * @param array $box Box to fit in space
     * @param array $space Space to fit box in
     *
     * @return bool
     */
    private function _try_fit_box($box, $space)
    {
        if (count($box) < 3) {
            throw new \InvalidArgumentException("_try_fit_box function parameter $box only accepts arrays with 3 values (length, width, height)");
        }

        if (count($space) < 3) {
            throw new \InvalidArgumentException("_try_fit_box function parameter $space only accepts arrays with 3 values (length, width, height)");
        }

        for ($i = 0; $i < count($box); $i++) {
            if (array_key_exists($i, $space)) {
                if ($box[$i] > $space[$i]) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if box fits in specified space
     * and rotate (3d) if necessary
     *
     * @param array $box Box to fit in space
     * @param array $space Space to fit box in
     *
     * @return bool
     */
    public function _box_fits($box, $space)
    {
        $box = array_values($box);
        $space = array_values($space);

        if ($this->_try_fit_box($box, $space)) {
            return true;
        }

        for ($i = 0; $i < count($box); $i++) {
            // Temp box size
            $t_box = $box;

            // Remove fixed column from list to be swapped
            unset($t_box[$i]);

            // Keys to be swapped
            $t_keys = array_keys($t_box);

            // Temp box with swapped sides
            $s_box = $this->_swap($box, $t_keys[0], $t_keys[1]);

            if ($this->_try_fit_box($s_box, $space)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Start a new packing level
     */
    private function pack_level()
    {
        $biggest_box_index = null;
        $biggest_surface = 0;
        $this->level++;

        // Find biggest (widest surface) box with minimum height
        foreach ($this->boxes as $k => $box) {
            $surface = $box['length'] * $box['width'];

            if ($surface > $biggest_surface) {
                $biggest_surface = $surface;
                $biggest_box_index = $k;
            } elseif ($surface == $biggest_surface) {
                if (!isset($biggest_box_index) || (isset($biggest_box_index) && $box['height'] < $this->boxes[$biggest_box_index]['height'])) {
                    $biggest_box_index = $k;
                }
            }
        }

        // Get biggest box as object
        $biggest_box = $this->boxes[$biggest_box_index];
        $biggest_box_volume = $this->_get_volume($biggest_box);
        if ($biggest_box_volume > $this->current_container_remaining_volume) {
            $this->packed_boxes = null;
            return;
        }

        // Set container height (ck = ck + ci)
        $this->container_dimensions['height'] += $biggest_box['height'];

        $c_area = $this->container_dimensions['length'] * $this->container_dimensions['width'];
        $p_area = $biggest_box['length'] * $biggest_box['width'];

        $exceeded_height = $this->container_dimensions['height'] > $this->max_height;
        $space_left = $c_area - $p_area > 0;

        if ($exceeded_height) {
            $this->packed_boxes = null;
            $this->container_dimensions['height'] -= $biggest_box['height'];
            return;
        }

        $this->current_container_remaining_volume = $this->current_container_remaining_volume - $biggest_box_volume;
        $this->packed_boxes[$this->level][] = $biggest_box;
        // Remove box from array (ki = ki - 1)
        unset($this->boxes[$biggest_box_index]);

        // Check if all boxes have been packed
        if (count($this->boxes) == 0) {
            $this->fits = true;
            return;
        }

        // No space left (not even when rotated / length and width swapped)
        if (!$space_left) {
            $this->pack_level();
        } else { // Space left, check if a package fits in
            $spaces = array();

            if ($this->container_dimensions['length'] - $biggest_box['length'] > 0) {
                $spaces[] = array(
                    'length' => $this->container_dimensions['length'] - $biggest_box['length'],
                    'width' => $this->container_dimensions['width'],
                    'height' => $biggest_box['height']
                );
            }

            if ($this->container_dimensions['width'] - $biggest_box['width'] > 0) {
                $spaces[] = array(
                    'length' => $biggest_box['length'],
                    'width' => $this->container_dimensions['width'] - $biggest_box['width'],
                    'height' => $biggest_box['height']
                );
            }

            // Fill each space with boxes
            foreach ($spaces as $space) {
                $this->_fill_space($space);
            }

            // Start packing remaining boxes on a new level
            if (count($this->boxes) > 0) {
                $this->pack_level();
            } else {
                $this->fits = true;
                return;
            }
        }
    }

    /**
     * Fills space with boxes recursively
     *
     * @param array $space
     */
    private function _fill_space($space)
    {

        // Total space volume
        $s_volume = $this->_get_volume($space);

        $fitting_box_index = null;
        $fitting_box_volume = null;

        foreach ($this->boxes as $k => $box) {
            // Skip boxes that have a higher volume than target space
            if ($this->_get_volume($box) > $s_volume) {
                continue;
            }

            if ($this->_box_fits($box, $space)) {
                $b_volume = $this->_get_volume($box);

                if (!isset($fitting_box_volume) || $b_volume > $fitting_box_volume) {
                    $fitting_box_index = $k;
                    $fitting_box_volume = $b_volume;
                }

            }

        }

        if (isset($fitting_box_index)) {
            $box = $this->boxes[$fitting_box_index];

            // Pack box
            $this->packed_boxes[$this->level][] = $this->boxes[$fitting_box_index];
            unset($this->boxes[$fitting_box_index]);

            // Calculate remaining space left (in current space)
            $new_spaces = array();

            if ($space['length'] - $box['length'] > 0) {
                $new_spaces[] = array(
                    'length' => $space['length'] - $box['length'],
                    'width' => $space['width'],
                    'height' => $box['height']
                );
            }

            if ($space['width'] - $box['width'] > 0) {
                $new_spaces[] = array(
                    'length' => $box['length'],
                    'width' => $space['width'] - $box['width'],
                    'height' => $box['height']
                );
            }

            if (count($new_spaces) > 0) {
                foreach ($new_spaces as $new_space) {
                    $this->_fill_space($new_space);
                }
            }
        }
    }
}
