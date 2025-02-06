<?php
namespace Mijora\BoxCalculator;

class Helper
{
    public static function cubeDeviationPercentage($width, $height, $length) {
        // Calculation of average dimension
        $average = ($width + $height + $length) / 3;

        // Calculation of deviations from the mean dimension
        $width_deviation = abs($width - $average);
        $height_deviation = abs($height - $average);
        $length_deviation = abs($length - $average);

        // Calculation of the total percentage deviation
        $total_deviation = ($width_deviation + $height_deviation + $length_deviation) / 3;

        // The percentage deviation from the ideal cube
        $deviation_percentage = ($total_deviation / $average) * 100;

        return $deviation_percentage;
    }
}
