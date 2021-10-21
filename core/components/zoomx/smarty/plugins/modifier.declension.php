<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.declension.php
 * Type: modifier
 * Name: declension
 * Description: Declension.
 * -------------------------------------------------------------
 */
function smarty_modifier_declension($number, array $words, $include = true)
{
    $number = (int)$number;
    if (zoomx()->config('cultureKey') === 'ru') {
        $keys = [2, 0, 1, 1, 1, 2];
        if (count($words) < 3) {
            $words[2] = $words[1];
        }
        $key = ($number % 100 > 4 && $number % 100 < 20) ? 2 : $keys[min($number % 10, 5)];
    } else {
        $key = $number === 1 ? 0 : 1;
    }

    return $include ? "$number $words[$key]" : $words[$key];
}