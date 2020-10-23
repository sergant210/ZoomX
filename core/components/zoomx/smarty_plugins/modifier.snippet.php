<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.snippet.php
 * Type: modifier
 * Name: snippet
 * Description: Run a snippet.
 * -------------------------------------------------------------
 */
function smarty_modifier_snippet($name, $properties = [])
{
    $parser = zoomx('parser');

    return $parser->runSnippet($name, $properties);
}