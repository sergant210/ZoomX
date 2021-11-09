<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.dateago.php
 * Type: modifier
 * Name: dateAgo
 * Description: Display date as "5 minutes ago", "7 hours ago",  "2 days ago".
 * -------------------------------------------------------------
 */
function smarty_modifier_dateAgo($date, $format = '')
{
    $dfClass = zoomx()->config('zoomx_date_formatter_class', 'Zoomx\Support\DateFormatter');
    $dateFormatter = new $dfClass(zoomx()->getModx());

    return $dateFormatter->dateAgo($date, $format);
}