<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Файл:    modifier.user.php
 * Тип:     modifier
 * Имя:     user
 * Назначение:  Get a user field.
 * -------------------------------------------------------------
 */
function smarty_modifier_user($field, $default = null)
{
    global $modx;

    if (!isset($modx->user)) {
        return null;
    }
    $profile = $modx->user->Profile ?? $modx->newObject('modUserProfile');
    $data = array_merge($modx->user->toArray(), $profile->toArray());
    unset($data['cachepwd'], $data['salt'], $data['sessionid'], $data['password'], $data['session_stale'], $data['hash_class']);

    if (strpos($field, 'extended.') === 0) {
        $result = $data['extended'][substr($field, 9)] ?? $default;
    } elseif (strpos($field, 'remote_data.') === 0) {
        $result = $data['remote_data'][substr($field, 12)] ?? $default;
    } else {
        $result = $data[$field] ?? $default;
    }

    return $result;
}