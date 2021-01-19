<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     prefilter.scripts.php
 * Type:     prefilter
 * Name:     scripts
 * Purpose:  Add the nocache option.
 * -------------------------------------------------------------
 */
function smarty_prefilter_scripts($source, Smarty_Internal_Template $template)
{
    $patterns = [
        '~(\|cssToHead)(.*)}~',
        '~(\|htmlToHead)(.*)}~',
        '~(\|htmlToBottom)(.*)}~',
        '~(\|jsToHead)(.*)}~',
        '~(\|jsToBottom)(.*)}~',
        '~(\|js)(.*)}~',
        '~(\|css)(.*)}~',
    ];

    return preg_replace($patterns, "$1$2 nocache}", $source);
}