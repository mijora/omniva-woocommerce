<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

//require '../../../../vendor/autoload.php';

use Mijora\BoxCalculator\Elements\Item;
use Mijora\BoxCalculator\CalculateBox;

/* Required parameters */
$products_list = [ //Initial array of products
    'item_1' => ['l' => 3.3, 'w' => 3.3, 'h' => 11.6],
    'item_2' => ['l' => 5.5, 'w' => 2.5, 'h' => 12.7],
    'item_3' => ['l' => 4.0, 'w' => 3.5, 'h' => 11.3],
    'item_4' => ['l' => 5.7, 'w' => 2.3, 'h' => 10.5],
];
$box_wall_thickness = 0.2; //Box wall thickness

/* Preparing items */
$items_list = array(); //Items list
foreach ($products_list as $product) {
    $items_list[] = new Item($product['w'], $product['h'], $product['l']); //Converting product array to Items array
}

/* Box calculation */
$box_calculator = new CalculateBox($items_list); //Initialing the calculation class and adding list of items to it
$min_box_size = $box_calculator //Getting calculated box
    ->setBoxWallThickness($box_wall_thickness) //The wall thickness of the box is specified (Optional. Default: 0)
    ->enableDebug(true) //Activating logging of performed actions (Optional. Default: false)
    ->findMinBoxSize(); //Calculating box size
$debug_data = $box_calculator->getDebugData(); //Getting logged data (if debug disabled, then this get only Items list and Box)


/***************************** EXAMPLE OUTPUT *************************************/

/* Helper functions */
function debug($variable) //Prepares a variable for printing
{
    return '<pre>'.print_r($variable, true).'</pre><br/>';
}

/* Outputting the results */
foreach ( $debug_data as $key => $value ) {
    if ( empty($value) ) {
        echo ucfirst($key) . ' empty.<br/>';
        continue;
    }
    echo '<b>' . ucfirst($key) . ':</b><br/>';
    if ( $key != 'actions' ) {
        echo debug($value);
        continue;
    }
    echo '<table border="1" cellspacing="0" cellpadding="10">';
    foreach ( $value as $action ) {
        echo '<tr><td>' . debug($action) . '</td></tr>';
    }
    echo '</table>';
}
echo '<br/><b>Calculated space in box inside:</b> ' . $min_box_size->getWidth() . ' x ' . $min_box_size->getHeight() . ' x ' . $min_box_size->getLength();
echo '<br/><b>Calculated box size (with ' . $box_wall_thickness . ' walls):</b> ' . $min_box_size->getOutsideWidth() . ' x ' . $min_box_size->getOutsideHeight() . ' x ' . $min_box_size->getOutsideLength();
echo '<br/><br/>';
