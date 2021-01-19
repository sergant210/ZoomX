<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.url.php
 * Type: modifier
 * Name: url
 * Description:  Generates a URL representing a specified resource.
 * -------------------------------------------------------------
 */
/**
 * Generates a URL representing a specified resource.
 *
 * @param integer $id The id of a resource.
 * @param string|array $context Specifies a context to limit URL generation to or an array of other arguments.
 * @param string $args A query string to append to the generated URL.
 * @param int|string $scheme The scheme indicates in what format the URL is generated.<br>
 * <pre>
 *      -1 : (default value) URL is relative to site_url
 *       0 : see http
 *       1 : see https
 *    full : URL is absolute, prepended with site_url from config
 *     abs : URL is absolute, prepended with base_url from config
 *    http : URL is absolute, forced to http scheme
 *   https : URL is absolute, forced to https scheme
 * </pre>
 * @param array $options An array of options for generating the Resource URL.
 * @return string The URL for the resource.
 */
function smarty_modifier_url($id, $context = '', $args = [], $scheme = -1, array $options = [])
{
    global $modx;

    if (is_array($context)) {
        extract($context, EXTR_OVERWRITE);
        $context = is_string($context) ? $context : '';
    }

    return $modx->makeUrl((int)$id, $context, $args, $scheme, $options);
}