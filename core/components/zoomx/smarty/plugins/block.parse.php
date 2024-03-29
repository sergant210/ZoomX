<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.parse.php
 * Type:     block
 * Name:     parse
 * Purpose:  Parse a block of text.
 * -------------------------------------------------------------
 */

function smarty_block_parse($params, $content, Smarty_Internal_Template $template, &$repeat)
{
    global $modx;

    // only output on the closing tag
    if(!$repeat) {
        if ($parserClass = @$params['parser']) {
            $parser = new $parserClass($modx, zoomx());
        } else {
            $parser = parserx();
        }

        if ($parser instanceof modParser) {
            $maxIterations = $params['iteration'] ?? $modx->getOption('parser_max_iterations', null, 10);
            $parser->processElementTags('', $content, false, false, '[[', ']]', [], (int)$maxIterations);
            $parser->processElementTags('', $content, true, true, '[[', ']]', [], (int)$maxIterations);
        } elseif ($parser instanceof Zoomx\Contracts\ParserInterface) {
            $content = $parser->parse($content);
        }

        return $content;
    }
}