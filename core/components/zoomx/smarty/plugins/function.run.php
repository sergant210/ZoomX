<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.run.php
 * Type:     function
 * Name:     run
 * Purpose:  run a snippet or a file snippet.
 * -------------------------------------------------------------
 */
function smarty_function_run($params, Smarty_Internal_Template $template)
{
    $scriptProperties = isset($params['params']) && is_array($params['params']) ? $params['params'] : [];
    switch (true) {
    	case isset($params['file']):
            $output = zoomx('elementService')->runFileSnippet($params['file'], $scriptProperties);
    		break;
        case isset($params['snippet']):
            $output = zoomx('elementService')->runSnippet($params['snippet'], $scriptProperties);
            break;
        default:
            $output = '';
    }

    if (!empty($params['assign'])) {
        $template->assign((string)$params['assign'], $output);
        $output = '';
    }

    return $output;
}