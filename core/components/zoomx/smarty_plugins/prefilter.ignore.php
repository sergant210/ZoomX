<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     prefilter.ignore.php
 * Type:     prefilter
 * Name:     ignore
 * Purpose:  Add literal tags.
 * -------------------------------------------------------------
 */
function smarty_prefilter_ignore($source, Smarty_Internal_Template $template)
{

    return preg_replace('~({.+)\|ignore(.*})~', "{literal}$1$2{/literal}", $source);
}