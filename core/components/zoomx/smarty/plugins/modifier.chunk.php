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
    return zoomx('elementService')->getChunk($name, $properties);
}