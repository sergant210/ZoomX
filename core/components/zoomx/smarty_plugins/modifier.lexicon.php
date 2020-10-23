<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.lexicon.php
 * Type: modifier
 * Name: lexicon
 * Description:  Get a translation for a given key.
 * -------------------------------------------------------------
 */
function smarty_modifier_lexicon($key, $params = [], $language = '')
{
    global $modx;

    return $modx->lexicon($key, $params, $language);
}