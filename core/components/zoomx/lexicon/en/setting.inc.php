<?php

$_lang['area_zoomx_main'] = 'Main';
$_lang['area_zoomx_smarty'] = 'Smarty';

$_lang['setting_zoomx_caching'] = 'Cache templates';
$_lang['setting_zoomx_caching_desc'] = 'Cache template files.';
$_lang['setting_zoomx_default_tpl'] = 'Default error template';
$_lang['setting_zoomx_default_tpl_desc'] = 'It\'s used to output errors for which a custom template is not defined.';
$_lang['setting_zoomx_theme'] = 'Theme';
$_lang['setting_zoomx_theme_desc'] = 'Folder name in the template directory. Allows you to manage site themes.';
$_lang['setting_zoomx_template_dir'] = 'Template Path';
$_lang['setting_zoomx_template_dir_desc'] = 'Full path to template files.';
$_lang['setting_zoomx_routing_mode'] = 'Route mode';
$_lang['setting_zoomx_routing_mode_desc'] = '0 - disabled; 1 - mixed (if no route is found, MODX will continue the search); 2 - strict (if no route is found, error 404 will occur).';
$_lang['setting_zoomx_parser_class'] = 'Parser class';
$_lang['setting_zoomx_parser_class_desc'] = 'Specify the ZoomX parser class. It should implement the Zoomx\ParserInterface interface. By default, ZoomSmarty.';
$_lang['setting_zoomx_include_modx'] = 'Include $modx';
$_lang['setting_zoomx_include_modx_desc'] = 'Allow the $modx object in templates.';

$_lang['setting_zoomx_smarty_cache_dir'] = 'Cache path';
$_lang['setting_zoomx_smarty_cache_dir_desc'] = 'Full path to cached template files.';
$_lang['setting_zoomx_smarty_compile_dir'] = 'Compiled file path';
$_lang['setting_zoomx_smarty_compile_dir_desc'] = 'Full path to compiled template files.';
$_lang['setting_zoomx_smarty_config_dir'] = 'Config path';
$_lang['setting_zoomx_smarty_config_dir_desc'] = 'Full path to config files.';
$_lang['setting_zoomx_smarty_custom_plugin_dir'] = 'Smarty plugins path';
$_lang['setting_zoomx_smarty_custom_plugin_dir_desc'] = 'Full path to custom Smarty plugins.';
$_lang['setting_zoomx_modx_tag_syntax'] = 'MODX tag syntax';
$_lang['setting_zoomx_modx_tag_syntax_desc'] = "Allows to use MODX style tags - {'*pagetitle'}, {'++site_name'}, {'~5'} and {'%lexicon'}. A negative impact on performance.";
$_lang['setting_zoomx_http_method_override'] = 'Override HTTP method';
$_lang['setting_zoomx_http_method_override_desc'] = 'Allows to specify the HTTP methods "PATCH", "PUT" and "DELETE" in the form input element named with the name "_method".';
$_lang['setting_zoomx_autoload_resource'] = 'Resource auto-loading';
$_lang['setting_zoomx_autoload_resource_desc'] = 'Disables searching and auto-loading the resource.';