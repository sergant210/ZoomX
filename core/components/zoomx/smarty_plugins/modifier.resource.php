<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Файл:    modifier.resource.php
 * Тип:     modifier
 * Имя:     resource
 * Назначение:  Get a resource field.
 * -------------------------------------------------------------
 */
function smarty_modifier_resource($field, $default = null)
{
    global $modx;

    if (!isset($modx->resource)) {
        return null;
    }
    if (empty($modx->resource->{$field})) {
        $output = $modx->resource->get($default) ?? '';
        return is_array($output) ? $output[1] : $output;
    }

    return is_array($modx->resource->{$field}) ? $modx->resource->{$field}[1] : $modx->resource->get($field);
}