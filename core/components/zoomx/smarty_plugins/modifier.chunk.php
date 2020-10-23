<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.chunk.php
 * Type: modifier
 * Name: chunk
 * Description: Get a chunk.
 * -------------------------------------------------------------
 */
function smarty_modifier_chunk($name, $properties = [])
{
    $parser = zoomx('parser');

    return $parser->getChunk($name, $properties);
}