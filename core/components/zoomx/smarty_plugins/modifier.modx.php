<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.modx.php
 * Type: modifier
 * Name: modx
 * Description: Parse content with the MODX parser.
 * -------------------------------------------------------------
 */
function smarty_modifier_modx($string)
{
    global $modx;

    if ($modx instanceof modX && $parser = $modx->getParser()) {
        $maxIterations = (int)$modx->getOption('parser_max_iterations', null, 10);
        $parser->processElementTags('', $string, false, false, '[[', ']]', [], $maxIterations);
        $parser->processElementTags('', $string, true, true, '[[', ']]', [], $maxIterations);
    }

    return $string;
}