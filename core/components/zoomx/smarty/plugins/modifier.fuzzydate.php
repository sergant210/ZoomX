<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.fuzzydate.php
 * Type: modifier
 * Name: fuzzyDate
 * Description: Display fuzzy date (yesterday at 11:15, today at 06:00, tomorrow at 15:30).
 * -------------------------------------------------------------
 */
function smarty_modifier_fuzzyDate($date, $format = '')
{
    $dfClass = zoomx()->config('zoomx_date_formatter_class', 'Zoomx\Support\DateFormatter');
    $dateFormatter = new $dfClass(zoomx()->getModx());

    return $dateFormatter->fuzzyDate($date, $format);
}