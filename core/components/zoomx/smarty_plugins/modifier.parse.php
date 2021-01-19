<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.parse.php
 * Type: modifier
 * Name: parse
 * Description: Parse content.
 * -------------------------------------------------------------
 */
function smarty_modifier_parse($string, $parser = '')
{
    global $modx;

    if ($parser === '') {
        $parser = parserx();
    }
    if ($parser instanceof Zoomx\Contracts\ParserInterface) {
        $string = $parser->parse($string);
    } else {
        $parser = new $parser($modx);
        $maxIterations = (int)$modx->getOption('parser_max_iterations', null, 10);
        $parser->processElementTags('', $string, false, false, '[[', ']]', [], $maxIterations);
        $parser->processElementTags('', $string, true, true, '[[', ']]', [], $maxIterations);
    }

    return $string;
}