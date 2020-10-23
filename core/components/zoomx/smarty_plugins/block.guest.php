<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.guest.php
 * Type:     block
 * Name:     guest
 * Purpose:  output a block of text only for guests
 * -------------------------------------------------------------
 */
function smarty_block_guest($params, $content, Smarty_Internal_Template $template, &$repeat)
{
    global $modx;

    // only output on the closing tag
    if(!$repeat){
        if (!$modx->user->isAuthenticated($modx->context->key)) {
            return $content;
        }

        return '';
    }
}