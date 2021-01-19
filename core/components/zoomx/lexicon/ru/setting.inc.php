<?php

$_lang['area_zoomx_main'] = 'Основные';
$_lang['area_zoomx_smarty'] = 'Smarty';

$_lang['setting_zoomx_caching'] = 'Кэшировать шаблоны';
$_lang['setting_zoomx_caching_desc'] = 'Кэшировать файлы шаблонов.';
$_lang['setting_zoomx_default_tpl'] = 'Шаблон по-умолчанию';
$_lang['setting_zoomx_default_tpl_desc'] = 'Используется когда роутер работает в строгом режиме, для текущей страницы не найден роут и не найдена страница ошибки.';
$_lang['setting_zoomx_theme'] = 'Тема';
$_lang['setting_zoomx_theme_desc'] = 'Имя папки в каталоге шаблонов. Позволяет управлять темами сайта.';
$_lang['setting_zoomx_template_dir'] = 'Путь к шаблонам';
$_lang['setting_zoomx_template_dir_desc'] = 'Полный путь к файлам шаблонов.';
$_lang['setting_zoomx_routing_mode'] = 'Режим роутинга';
$_lang['setting_zoomx_routing_mode_desc'] = '0 - роутинг выключен; 1 - смешанный режим (если роут не найден, поиском займётся MODX); 2 - монопольный режим (если роут не найден, то ошибка 404).';
$_lang['setting_zoomx_parser_class'] = 'Класс парсера';
$_lang['setting_zoomx_parser_class_desc'] = 'Укажите класс парсера ZoomX. Он должен имплементировать интерфейс Zoomx\ParserInterface. По-умолчанию, ZoomSmarty.';
$_lang['setting_zoomx_include_modx'] = 'Разрешить $modx в шаблонах';
$_lang['setting_zoomx_include_modx_desc'] = 'В шаблонах будет доступен объект $modx.';

$_lang['setting_zoomx_smarty_cache_dir'] = 'Путь к файлам кэша';
$_lang['setting_zoomx_smarty_cache_dir_desc'] = 'Полный путь к файлам кэшированных шаблонов.';
$_lang['setting_zoomx_smarty_compile_dir'] = 'Путь к компилированным файлам';
$_lang['setting_zoomx_smarty_compile_dir_desc'] = 'Полный путь к компилированным файлам шаблонов.';
$_lang['setting_zoomx_smarty_config_dir'] = 'Путь к файлам конфигов';
$_lang['setting_zoomx_smarty_config_dir_desc'] = 'Полный путь к файлам конфигов.';
$_lang['setting_zoomx_smarty_custom_plugin_dir'] = 'Путь к плагинам Smarty';
$_lang['setting_zoomx_smarty_custom_plugin_dir_desc'] = 'Полный путь к пользовательским плагинам Smarty.';
$_lang['setting_zoomx_modx_tag_syntax'] = 'Синтаксис MODX тегов';
$_lang['setting_zoomx_modx_tag_syntax_desc'] = "Позволяет использовать синтаксис в стиле MODX тегов - {'*pagetitle'}, {'++setting'}, {'~5'} и {'%lexicon'}. Негативно сказывается на производительности.";
$_lang['setting_zoomx_http_method_override'] = 'Переписать HTTP метод';
$_lang['setting_zoomx_http_method_override_desc'] = 'Позволяет указать HTTP методы "PATCH", "PUT" и "DELETE" в элементе формы с именем "_method".';
