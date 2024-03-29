<?php

$_lang['area_zoomx_main'] = 'Основные';
$_lang['area_zoomx_smarty'] = 'Smarty';
$_lang['area_zoomx_routing'] = 'Маршрутизация';

$_lang['setting_zoomx_caching'] = 'Кэшировать шаблоны';
$_lang['setting_zoomx_caching_desc'] = 'Кэшировать файлы шаблонов.';
$_lang['setting_zoomx_default_tpl'] = 'Шаблон по-умолчанию';
$_lang['setting_zoomx_default_tpl_desc'] = 'Используется для вывода ошибок, для которых не определён свой шаблон.';
$_lang['setting_zoomx_theme'] = 'Тема';
$_lang['setting_zoomx_theme_desc'] = 'Имя папки в каталоге шаблонов. Позволяет управлять темами сайта.';
$_lang['setting_zoomx_template_dir'] = 'Путь к шаблонам';
$_lang['setting_zoomx_template_dir_desc'] = 'Полный путь к файлам шаблонов.';
$_lang['setting_zoomx_routing_mode'] = 'Режим маршрутизации';
$_lang['setting_zoomx_routing_mode_desc'] = '0 - маршрутизация выключена; 1 - смешанный режим (если роут не найден, поиском займётся MODX); 2 - монопольный режим (если роут не найден, то ошибка 404).';
$_lang['setting_zoomx_parser_class'] = 'Класс парсера';
$_lang['setting_zoomx_parser_class_desc'] = 'Укажите класс парсера ZoomX. Он должен имплементировать интерфейс Zoomx\ParserInterface. По-умолчанию, ZoomSmarty.';
$_lang['setting_zoomx_include_modx'] = 'Разрешить $modx и $zoomx в шаблонах';
$_lang['setting_zoomx_include_modx_desc'] = 'В шаблонах будут доступны объекты $modx и $zoomx (объект сервиса ZoomX).';

$_lang['setting_zoomx_smarty_cache_dir'] = 'Путь к файлам кэша';
$_lang['setting_zoomx_smarty_cache_dir_desc'] = 'Путь к файлам кэшированных шаблонов относительно директории /core/cache/.';
$_lang['setting_zoomx_smarty_compile_dir'] = 'Путь к компилированным файлам';
$_lang['setting_zoomx_smarty_compile_dir_desc'] = 'Путь к компилированным файлам шаблонов относительно директории /core/cache/.';
$_lang['setting_zoomx_smarty_config_dir'] = 'Путь к файлам конфигов';
$_lang['setting_zoomx_smarty_config_dir_desc'] = 'Полный путь к файлам конфигов.';
$_lang['setting_zoomx_smarty_custom_plugin_dir'] = 'Путь к плагинам Smarty';
$_lang['setting_zoomx_smarty_custom_plugin_dir_desc'] = 'Полный путь к пользовательским плагинам Smarty.';
$_lang['setting_zoomx_modx_tag_syntax'] = 'Синтаксис MODX тегов';
$_lang['setting_zoomx_modx_tag_syntax_desc'] = "Позволяет использовать синтаксис в стиле MODX тегов - {'*pagetitle'}, {'++setting'}, {'~5'} и {'%lexicon'}. Негативно сказывается на производительности.";
$_lang['setting_zoomx_http_method_override'] = 'Переписать HTTP метод';
$_lang['setting_zoomx_http_method_override_desc'] = 'Позволяет указать HTTP методы "PATCH", "PUT" и "DELETE" в элементе формы с именем "_method".';
$_lang['setting_zoomx_autoload_resource'] = 'Автоматическая загрузка ресурса';
$_lang['setting_zoomx_autoload_resource_desc'] = 'Отключает поиск и загрузку ресурса по URI. Может применяться для виртуальных страниц.';
$_lang['setting_zoomx_zoomx_template_extension'] = 'Расширение для файлов шаблонов';
$_lang['setting_zoomx_zoomx_template_extension_desc'] = 'Расширение для файлов шаблонов. По-умолчанию, "tpl".';
$_lang['setting_zoomx_file_snippets_path'] = 'Путь к файловым сниппетам';
$_lang['setting_zoomx_file_snippets_path_desc'] = 'Абсолютный путь к файловым сниппетам. Можно указывать несколько путей, разделяя их ";".';
$_lang['setting_zoomx_include_request_info'] = 'Добавлять информацию о запросе';
$_lang['setting_zoomx_include_request_info_desc'] = 'Добавляет в ответ информацию о запросе в API режиме.';
$_lang['setting_zoomx_enable_exception_handler'] = 'Включить обработчик исключений';
$_lang['setting_zoomx_enable_exception_handler_desc'] = 'Включает собственный обработчик исключений для строгого режима роутинга.';
$_lang['setting_zoomx_show_error_details'] = 'Показывать полное описание ошибки';
$_lang['setting_zoomx_show_error_details_desc'] = 'Показывает полные данные об исключении или ошибке.';
$_lang['setting_zoomx_autodetect_content_type'] = 'Автоматически определять тип контента';
$_lang['setting_zoomx_autodetect_content_type_desc'] = 'Включает автоматическое определение Content-Type в режиме отключённой загрузки ресурса.';
$_lang['setting_zoomx_use_zoomx_parser_as_default'] = 'Использовать ZoomX шаблонизатор';
$_lang['setting_zoomx_use_zoomx_parser_as_default_desc'] = 'Использовать выбранный шаблонизатор ZoomX в качестве основного шаблонизатора MODX.';
$_lang['setting_zoomx_enable_pdotools_adapter'] = 'Включить pdoTools adapter.';
$_lang['setting_zoomx_enable_pdotools_adapter_desc'] = 'Заменяет шаблонизатор Fenom на шаблонизатор, указанный в ZoomX, для парсинга чанков в сниппетах pdoTools.';
$_lang['setting_zoomx_smarty_security_enable'] = 'Включить режим безопасности';
$_lang['setting_zoomx_smarty_security_enable_desc'] = 'Включает режим для управления безопасностью Smarty, которая определяется в классе безопасности.';
$_lang['setting_zoomx_smarty_security_class'] = 'Класс безопасности';
$_lang['setting_zoomx_smarty_security_class_desc'] = 'Класс, в котором определяются настройки безопасности.';
$_lang['setting_zoomx_cache_routes'] = 'Кэшировать роуты';
$_lang['setting_zoomx_cache_routes_desc'] = 'Включает кэширование роутов. Внимание! Включайте только если в роутах используются контроллеры.';
$_lang['setting_zoomx_controller_namespace'] = 'Пространство имён контроллеров';
$_lang['setting_zoomx_short_name_controllers_desc'] = 'Разрешает указывать имена контроллеров без пространства имён. Указанное пространство будет добавлено автоматически.';
$_lang['setting_zoomx_cache_event_map'] = 'Кэшировать карту событий';
$_lang['setting_zoomx_cache_event_map_desc'] = 'В целях оптимизации кэширует карту событий файловых плагинов. При разработке лучше отключить.';
$_lang['setting_zoomx_config_path'] = 'Путь к конфигам';
$_lang['setting_zoomx_config_path_desc'] = 'Путь к конфигурационным файлам роутов, исключений и файловых элементов. По-умолчанию, {core_path}config/.';