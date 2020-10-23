<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.print.php
 * Type: modifier
 * Name: print
 * Description:  Print the given value.
 * -------------------------------------------------------------
 */
function smarty_modifier_print($value, $pre = true, $esc = true)
{
    $result = $esc ? htmlspecialchars(print_r($value, 1)) : print_r($value, 1);
    return $pre ? '<pre>' . $result . '</pre>' : $result;
}