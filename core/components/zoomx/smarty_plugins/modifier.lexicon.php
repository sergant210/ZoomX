<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.lexicon.php
 * Type: modifier
 * Name: lexicon
 * Description:  Get a translation for a given key and load a specified in the $language argument topic (see https://docs.modx.com/current/en/extending-modx/internationalization).
 * -------------------------------------------------------------
 */
function smarty_modifier_lexicon($key, $params = [], $language = '')
{
    global $modx;

    if (strpos($language, ':') !== false) {
        $modx->lexicon->load($language);
        $array = explode(':', $language);
        $language = count($array) === 3 ? $array[0] : '';
    }

    return $modx->lexicon($key, $params, $language);
}