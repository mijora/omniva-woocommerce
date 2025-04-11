# Calculation of box size

This library calculates the box size that is required to contain the given items.

## Features

- Units are not required for calculation
- The wall thickness of the box can be specified
- The volume of the box and each item is calculated
- Can calculate the smallest box or check if items fit into a box of a specified size
- Automatically items rotation
- Debugging calculated data and performed actions

## Requirements

- Minimum PHP 7.0, tested up to PHP 8.1.16


## Instalation

To install via composer:

```sh
composer require mijora/box-calculator
```

## How to use

All examples can be viewed in `example/` folder.


### Preparing items
```php

use Mijora\BoxCalculator\Elements\Item;

$products_list = [ //Initial array of products
    'prod_1' => ['l' => 3.3, 'w' => 3.3, 'h' => 11.6],
    'prod_2' => ['l' => 5.5, 'w' => 2.5, 'h' => 12.7],
    'prod_3' => ['l' => 4.0, 'w' => 3.5, 'h' => 11.3],
    'prod_4' => ['l' => 5.7, 'w' => 2.3, 'h' => 10.5],
];

$items_list = array(); //Items list
foreach ($products_list as $product) {
    $items_list[] = new Item($product['w'], $product['h'], $product['l']); //Converting product array to Items array
}

```

### Selection of the calculation method
```php
use Mijora\BoxCalculator\CalculateBox;

$box_calculator = new CalculateBox($items_list); //Initialing the calculation class and adding list of items to it

$box_calculator->setMethod('AddToEdge'); //Provides the ability to change the method used for calculation. All available methods can be obtained with the function getAvailableMethods(). (Optional. Default: AddToEdge)
```

- **AddToEdge** - The simplest and fastest method that calculates the size of a box by adding the items to the box's edges. The calculation accuracy is low because the items are not added to the depth, leaving a lot of empty space in the box.
- **Heuristic3D** - A simplified heuristic 3D brute-force placement algorithm is used, which fills the empty space efficiently. The calculation accuracy is high, but it cannot be used to calculate the smallest box (it is necessary to specify the max value of the box).


### Calculating the smallest box
```php

use Mijora\BoxCalculator\CalculateBox;

$box_calculator = new CalculateBox($items_list); //Initialing the calculation class and adding list of items to it

$min_box_size = $box_calculator //Getting calculated box
    ->enableDebug(true) //Activating logging of performed actions. Functions called before this parameter is enabled will not be added to the log. (Optional. Default: false)
    ->setMethod('AddToEdge') //Provides the ability to change the method used for calculation. All available methods can be obtained with the function getAvailableMethods(). (Optional. Default: AddToEdge)
    ->setBoxWallThickness(0.2) //The wall thickness of the box is specified (Optional. Default: 0)
    ->findMinBoxSize(); //Calculating box size

$debug_data = $box_calculator->getDebugData(); //Getting logged data (if debug disabled, then this get only Items list and Box)

```

### Check if the products fit in the box
```php

use Mijora\BoxCalculator\CalculateBox;

$box_calculator = new CalculateBox($items_list); //Initialing the calculation class and adding list of items to it

$min_box_size = $box_calculator //Getting calculated box
    ->enableDebug(true) //Activating logging of performed actions. Functions called before this parameter is enabled will not be added to the log. (Optional. Default: false)
    ->setMethod('Heuristic3D') //Provides the ability to change the method used for calculation. All available methods can be obtained with the function getAvailableMethods(). (Optional. Default: AddToEdge)
    ->setBoxWallThickness(0.2) //The wall thickness of the box is specified (Optional. Default: 0)
    ->setMaxBoxSize(20, 15.5, 18) //Set max box size (width x height x length) (Required)
    ->findBoxSizeUntilMaxSize(); //Calculating box size and get false is reach max size

$debug_data = $box_calculator->getDebugData(); //Getting logged data (if debug disabled, then this get only Items list and Box)

```

### Result output
```php

$box_size_inside = array( //Inside box size
    'width' => $min_box_size->getWidth(),
    'height' => $min_box_size->getHeight(),
    'length' => $min_box_size->getLength(),
    'volume' => $min_box_size->getVolume()
);

$box_size_outside = array( //Absolute box size (with walls)
    'width' => $min_box_size->getOutsideWidth(),
    'height' => $min_box_size->getOutsideHeight(),
    'length' => $min_box_size->getOutsideLength(),
    'volume' => $min_box_size->getOutsideVolume()
);

```
