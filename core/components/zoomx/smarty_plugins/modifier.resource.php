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
function smarty_modifier_resource($field, $defaultField = null)
{
    global $modx;

    if (!isset($modx->resource)) {
        return '';
    }
    if (empty($modx->resource->{$field})) {
        if (isset($defaultField, $modx->resource->{$defaultField})) {
            $output = $modx->resource->get($defaultField) ?? '';
            return is_array($output) ? $output[1] : $output;
        }
        return '';
    }

    return is_array($modx->resource->{$field}) ? $modx->resource->{$field}[1] : $modx->resource->get($field);
}