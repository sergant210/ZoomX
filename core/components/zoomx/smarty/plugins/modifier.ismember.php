<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Файл:    modifier.ismember.php
 * Тип:     modifier
 * Имя:     ismember
 * Назначение:  States whether the current user is a member of a group or groups.
 * -------------------------------------------------------------
 */
function smarty_modifier_ismember($groups, $matchAll = false)
{
    global $modx;

    if (!isset($modx->user) || $modx->user->id === 0) {
        return false;
    }
    if (is_string($groups)) {
        $groups = array_map('trim', explode(',', $groups));
    }

    return $modx->user->isMember($groups, $matchAll);
}