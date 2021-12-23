<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     prefilter.include.php
 * Type:     prefilter
 * Name:     include
 * Purpose:  Filter the file name.
 * -------------------------------------------------------------
 */
function smarty_prefilter_include($source, Smarty_Internal_Template $template)
{
    return preg_replace_callback('#{include\s+(file\s*=\s*)?[\'"](.+)[\'"](.*)}#iU', function($matches) {
        /** @var Zoomx\Support\ElementService $elService */
        $elService = zoomx('elementService');
        $file = ltrim($elService->sanitizePath($matches[2]), '/\\');
        $tplExtension = pathinfo($file, PATHINFO_EXTENSION);
        $ext = zoomx('modx')->getOption('zoomx_template_extension', null, 'tpl');
        if ($tplExtension !== $ext) {
            $file .= ".$ext";
        }

        return "{include file='$file'$matches[3]}";
    } , $source);
}