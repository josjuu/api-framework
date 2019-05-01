<?php
/**
 * Created by PhpStorm.
 * User: Mson
 * Date: 01-05-2019
 * Time: 15:14
 */

/**
 * Gets the ordinal string of an number.
 *
 * @param $number
 *      The number to be converted
 * @return string
 *      The number with the proper suffix.
 */
function ordinal($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
        return $number. 'th';
    else
        return $number. $ends[$number % 10];
}