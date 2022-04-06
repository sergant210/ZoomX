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
function smarty_modifier_chunk($name, $properties = [], $cache_lifetime = null)
{
    if (is_numeric($properties)) {
        [$properties, $cache_lifetime] = [[], $properties];
    }
    return zoomx('elementService')->getChunk($name, $properties, $cache_lifetime);
}