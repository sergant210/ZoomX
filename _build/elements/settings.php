<?php

return [
    'zoomx_caching' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'zoomx_main',
    ],
    'zoomx_theme' => [
        'xtype' => 'textfield',
        'value' => 'default',
        'area' => 'zoomx_main',
    ],
    'zoomx_template_dir' => [
        'xtype' => 'textfield',
        'value' => '{core_path}components/zoomx/templates/',
        'area' => 'zoomx_main',
    ],
    'zoomx_parser_class' => [
        'xtype' => 'textfield',
        'value' => 'ZoomSmarty',
        'area' => 'zoomx_main',
    ],
    'zoomx_include_modx' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'zoomx_main',
    ],
    'zoomx_http_method_override' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'zoomx_main',
    ],
    'zoomx_autoload_resource' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'zoomx_main',
    ],
    'zoomx_include_request_info' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'zoomx_main',
    ],
    'zoomx_file_snippets_path' => [
        'xtype' => 'textfield',
        'value' => '{core_path}elements/snippets/',
        'area' => 'zoomx_main',
    ],
    'zoomx_autodetect_content_type' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'zoomx_main',
    ],
    'zoomx_show_error_details' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'zoomx_main',
    ],
    'zoomx_enable_pdotools_adapter' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'zoomx_main',
    ],
    'zoomx_use_zoomx_parser_as_default' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'zoomx_main',
    ],
    'zoomx_enable_exception_handler' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'zoomx_main',
    ],
    'zoomx_cache_event_map' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'zoomx_main',
    ],
    /* Routing */
    'zoomx_routing_mode' => [
        'xtype' => 'numberfield',
        'value' => 1,
        'area' => 'zoomx_routing',
    ],
    'zoomx_cache_routes' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'zoomx_routing',
    ],
    'zoomx_controller_namespace' => [
        'xtype' => 'textfield',
        'value' => 'Zoomx\\Controller\\',
        'area' => 'zoomx_routing',
    ],
    /* Smarty */
    'zoomx_smarty_cache_dir' => [
        'xtype' => 'textfield',
        'value' => 'zoomx/smarty/cache/',
        'area' => 'zoomx_smarty',
    ],
    'zoomx_smarty_compile_dir' => [
        'xtype' => 'textfield',
        'value' => 'zoomx/smarty/compile/',
        'area' => 'zoomx_smarty',
    ],
    'zoomx_smarty_config_dir' => [
        'xtype' => 'textfield',
        'value' => '{core_path}config/',
        'area' => 'zoomx_smarty',
    ],
    'zoomx_smarty_custom_plugin_dir' => [
        'xtype' => 'textfield',
        'value' => '{core_path}components/zoomx/smarty/custom_plugins/',
        'area' => 'zoomx_smarty',
    ],
    'zoomx_modx_tag_syntax' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'zoomx_smarty',
    ],
	'zoomx_default_tpl' => [
        'xtype' => 'textfield',
        'value' => 'error.tpl',
        'area' => 'zoomx_smarty',
    ],
	'zoomx_template_extension' => [
        'xtype' => 'textfield',
        'value' => 'tpl',
        'area' => 'zoomx_smarty',
    ],
    'zoomx_smarty_security_class' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'zoomx_smarty',
    ],
    'zoomx_smarty_security_enable' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'zoomx_smarty',
    ],
];