<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.auth.php
 * Type:     block
 * Name:     auth
 * Purpose:  return a block content only for authenticated users.
 * -------------------------------------------------------------
 */
function smarty_block_auth($params, $content, Smarty_Internal_Template $template, &$repeat)
{
    global $modx;

    $context = $params['context'] ?? $modx->context->key;

    // only output on the closing tag
    if(!$repeat){
        if ($modx->user->isAuthenticated($context)) {
            return $content;
        }

        return '';
    }
}