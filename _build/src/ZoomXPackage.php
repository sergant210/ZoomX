<?php

class ZoomXPackage
{
    /** @var modX $modx */
    public $modx;
    /** @var array $config */
    public $config = [];

    /** @var modPackageBuilder $builder */
    public $builder;
    /** @var modCategory $vehicle */
    public $category;
    public $category_attributes = [];

    /** @var modDbRegister $queueManager */
    public $queueManager;
    /** @var int */
    protected $time;
    /** @var int */
    protected $_idx = 1;

    protected $logger;


    /**
     * ZoomXPackage constructor.
     *
     * @param modX $modx
     * @param array $config
     */
    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;
        $root = dirname(__FILE__, 3) . '/';
        $assets = $root . 'assets/components/' . $config['name_lower'] . '/';
        $core = $root . 'core/components/' . $config['name_lower'] . '/';

        $this->config = array_merge([
            'log_level' => modX::LOG_LEVEL_INFO,
            'log_target' => XPDO_CLI_MODE ? 'ECHO' : 'HTML',

            'root' => $root,
            'build' => $root . '_build/',
            'elements' => $root . '_build/elements/',
            'resolvers' => $root . '_build/resolvers/',

            'assets' => $assets,
            'core' => $core,
        ], $config);
        $modx->setLogLevel($this->config['log_level']);
        $modx->setLogTarget($this->config['log_target']);

        $this->time = $modx->startTime;

        if (PHP_SAPI === 'cli') {
            include dirname(__DIR__) . '/src/CliLogger.php';
            $this->logger = new CliLogger($modx);
        } else {
            $this->initRegistry();
            include dirname(__DIR__) . '/src/QueueLogger.php';
            $this->logger = new QueueLogger($modx, $this);
        }

        $this->initialize();
    }

    protected function initRegistry()
    {
        $registry = $this->modx->getService('registry', 'registry.modRegistry');
        $this->queueManager = $registry->getRegister('package_builder', 'registry.modDbRegister');
        $this->queueManager->subscribe('/messages/');
    }
    /**
     * Initialize package builder
     */
    protected function initialize()
    {
        $this->builder = $this->modx->getService('transport.modPackageBuilder');
        $this->builder->createPackage($this->config['name_lower'], $this->config['version'], $this->config['release']);
        $this->info('Инициализация транспортного пакета.');
        $this->builder->registerNamespace($this->config['name_lower'], false, true, '{core_path}components/' . $this->config['name_lower'] . '/');
        $this->info('Пространство имён зарегистрировано.');

        $this->category = $this->modx->newObject('modCategory');
        $this->category->set('category', $this->config['name']);
        $this->category_attributes = [
            xPDOTransport::UNIQUE_KEY => 'category',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [],
        ];
        $this->info('Категория создана.');
    }

    public function process()
    {
        $this->model();
        $this->assets();

        // Add elements
        $elements = scandir($this->config['elements']);
        foreach ($elements as $element) {
            if (in_array($element[0], ['_', '.'])) {
                continue;
            }
            $name = preg_replace('#\.php$#', '', $element);
            if (method_exists($this, $name)) {
                $this->{$name}();
            }
        }

        // Create main vehicle
        /** @var modTransportVehicle $vehicle */
        $vehicle = $this->builder->createVehicle($this->category, $this->category_attributes);

        // Files resolvers
        $vehicle->resolve('file', [
            'source' => $this->config['core'],
            'target' => "return MODX_CORE_PATH . 'components/';",
        ]);
//        $vehicle->resolve('file', [
//            'source' => $this->config['assets'],
//            'target' => "return MODX_ASSETS_PATH . 'components/';",
//        ]);
        $this->resolvers($vehicle);

        $this->builder->putVehicle($vehicle);

        $this->builder->setPackageAttributes([
            'changelog' => file_get_contents($this->config['core'] . 'docs/changelog.txt'),
            'license' => file_get_contents($this->config['core'] . 'docs/license.txt'),
            'readme' => file_get_contents($this->config['core'] . 'docs/readme.txt'),
        ]);
        $this->info('Добавлены атрибуты пакета.');

        $this->builder->pack();
        $this->info('Упаковка транспортного пакета в zip файл.');

        if (!empty($this->config['install'])) {
            $this->install();
        }
    }

    /**
     * Update the model
     */
    protected function model()
    {
        $model_file = $this->config['core'] . 'model/schema/' . $this->config['name_lower'] . '.mysql.schema.xml';
        if (!file_exists($model_file) || empty(file_get_contents($model_file))) {
            return;
        }
        /** @var xPDOCacheManager $cache */
        if ($cache = $this->modx->getCacheManager()) {
            $cache->deleteTree(
                $this->config['core'] . 'model/' . $this->config['name_lower'] . '/mysql',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
        }

        /** @var xPDOManager $manager */
        $manager = $this->modx->getManager();
        /** @var xPDOGenerator $generator */
        $generator = $manager->getGenerator();
        $generator->parseSchema(
            $this->config['core'] . 'model/schema/' . $this->config['name_lower'] . '.mysql.schema.xml',
            $this->config['core'] . 'model/'
        );
        $this->info('Модель обновлена.');
    }


    /**
     * Install nodejs and update assets
     */
    protected function assets()
    {
        $output = [];
        if (!file_exists($this->config['build'] . 'node_modules')) {
            putenv('PATH=' . trim(shell_exec('echo $PATH')) . ':' . dirname(MODX_BASE_PATH) . '/');
            if (file_exists($this->config['build'] . 'package.json')) {
                $this->info('Установка или обновление зависимостей nodejs.');
                $output = [
                    shell_exec('cd ' . $this->config['build'] . ' && npm config set scripts-prepend-node-path true && npm install'),
                ];
            }
            if (file_exists($this->config['build'] . 'gulpfile.js')) {
                $output = array_merge($output, [
                    shell_exec('cd ' . $this->config['build'] . ' && npm link gulp'),
                    shell_exec('cd ' . $this->config['build'] . ' && gulp copy'),
                ]);
            }
            if ($output) {
                $this->info(implode("\n", array_map('trim', $output)));
            }
        }
        if (file_exists($this->config['build'] . 'gulpfile.js')) {
            $output = shell_exec('cd ' . $this->config['build'] . ' && gulp default 2>&1');
            $this->info('Скомпилированы скрипты и стили ' . trim($output));
        }
    }


    /**
     * Add settings
     */
    protected function settings()
    {
        /** @noinspection PhpIncludeInspection */
        $settings = include($this->config['elements'] . 'settings.php');
        if (!is_array($settings)) {
            $this->error('Системные настройки не заданы.');

            return;
        }
        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'key',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['settings']),
            xPDOTransport::RELATED_OBJECTS => false,
        ];
        foreach ($settings as $name => $data) {
            /** @var modSystemSetting $setting */
            $setting = $this->modx->newObject('modSystemSetting');
            $setting->fromArray(array_merge([
                'key' => $name,
                'namespace' => $this->config['name_lower'],
            ], $data), '', true, true);
            $vehicle = $this->builder->createVehicle($setting, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->info('Упаковано системных настроек: ' . count($settings) . '.');
    }


    /**
     * Add menus
     */
    protected function menus()
    {
        /** @noinspection PhpIncludeInspection */
        $menus = include($this->config['elements'] . 'menus.php');
        if (!is_array($menus)) {
            $this->error('Меню не задано.');

            return;
        }
        $attributes = [
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['menus']),
            xPDOTransport::UNIQUE_KEY => 'text',
            xPDOTransport::RELATED_OBJECTS => true,
        ];
        if (is_array($menus)) {
            foreach ($menus as $name => $data) {
                /** @var modMenu $menu */
                $menu = $this->modx->newObject('modMenu');
                $menu->fromArray(array_merge([
                    'text' => $name,
                    'parent' => 'components',
                    'namespace' => $this->config['name_lower'],
                    'icon' => '',
                    'menuindex' => 0,
                    'params' => '',
                    'handler' => '',
                ], $data), '', true, true);
                $vehicle = $this->builder->createVehicle($menu, $attributes);
                $this->builder->putVehicle($vehicle);
            }
        }
        $this->info('Упаковано меню: ' . count($menus) . '.');
    }


    /**
     * Add Dashboard Widgets
     */
    protected function widgets()
    {
        /** @noinspection PhpIncludeInspection */
        $widgets = include($this->config['elements'] . 'widgets.php');
        if (!is_array($widgets)) {
            $this->error('Виджеты не заданы.');

            return;
        }
        $attributes = [
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['widgets']),
            xPDOTransport::UNIQUE_KEY => 'name',
        ];
        foreach ($widgets as $name => $data) {
            /** @var modDashboardWidget $widget */
            $widget = $this->modx->newObject('modDashboardWidget');
            $widget->fromArray(array_merge([
                'name' => $name,
                'namespace' => 'core',
                'lexicon' => 'core:dashboards',
            ], $data), '', true, true);
            $vehicle = $this->builder->createVehicle($widget, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->info('Упаковано виджетов: ' . count($widgets) . '.');
    }


    /**
     * Add resources
     */
    protected function resources()
    {
        /** @noinspection PhpIncludeInspection */
        $resources = include($this->config['elements'] . 'resources.php');
        if (!is_array($resources)) {
            $this->error('Ресурсы не заданы.');

            return;
        }
        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'id',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['resources']),
            xPDOTransport::RELATED_OBJECTS => false,
        ];
        $objects = [];
        foreach ($resources as $context => $items) {
            $menuindex = 0;
            foreach ($items as $alias => $item) {
                if (!isset($item['id'])) {
                    $item['id'] = $this->_idx++;
                }
                $item['alias'] = $alias;
                $item['context_key'] = $context;
                $item['menuindex'] = $menuindex++;
                $objects = array_merge(
                    $objects,
                    $this->_addResource($item, $alias)
                );
            }
        }

        /** @var modResource $resource */
        foreach ($objects as $resource) {
            $vehicle = $this->builder->createVehicle($resource, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->info('Упаковано ресурсов: ' . count($objects) . '.');
    }


    /**
     * Add plugins
     */
    protected function plugins()
    {
        /** @noinspection PhpIncludeInspection */
        $plugins = include($this->config['elements'] . 'plugins.php');
        if (!is_array($plugins)) {
            $this->error('Плагины не заданы.');

            return;
        }
        $this->category_attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Plugins'] = [
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['plugins']),
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'PluginEvents' => [
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
                ],
            ],
        ];
        $objects = [];
        foreach ($plugins as $name => $data) {
            /** @var modPlugin $plugin */
            $plugin = $this->modx->newObject('modPlugin');
            $plugin->fromArray(array_merge([
                'name' => $name,
                'category' => 0,
                'description' => @$data['description'],
                'plugincode' => $this::_getContent($this->config['core'] . 'elements/plugins/' . $data['file'] . '.php'),
                'static' => !empty($this->config['static']['plugins']),
                'source' => 1,
                'static_file' => 'core/components/' . $this->config['name_lower'] . '/elements/plugins/' . $data['file'] . '.php',
            ], $data), '', true, true);

            $events = [];
            if (!empty($data['events'])) {
                foreach ($data['events'] as $event_name => $event_data) {
                    /** @var modPluginEvent $event */
                    $event = $this->modx->newObject('modPluginEvent');
                    $event->fromArray(array_merge([
                        'event' => $event_name,
                        'priority' => 0,
                        'propertyset' => 0,
                    ], $event_data), '', true, true);
                    $events[] = $event;
                }
            }
            if (!empty($events)) {
                $plugin->addMany($events);
            }
            $objects[] = $plugin;
        }
        $this->category->addMany($objects);
        $this->info('Упаковано плагинов: ' . count($objects) . '.');
    }


    /**
     * Add snippets
     */
    protected function snippets()
    {
        /** @noinspection PhpIncludeInspection */
        $snippets = include($this->config['elements'] . 'snippets.php');
        if (!is_array($snippets)) {
            $this->error('Сниппеты не заданы.');

            return;
        }
        $this->category_attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Snippets'] = [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['snippets']),
            xPDOTransport::UNIQUE_KEY => 'name',
        ];
        $objects = [];
        foreach ($snippets as $name => $data) {
            /** @var modSnippet[] $objects */
            $objects[$name] = $this->modx->newObject('modSnippet');
            $objects[$name]->fromArray(array_merge([
                'id' => 0,
                'name' => $name,
                'description' => @$data['description'],
                'snippet' => $this::_getContent($this->config['core'] . 'elements/snippets/' . $data['file'] . '.php'),
                'static' => !empty($this->config['static']['snippets']),
                'source' => 1,
                'static_file' => 'core/components/' . $this->config['name_lower'] . '/elements/snippets/' . $data['file'] . '.php',
            ], $data), '', true, true);
            $properties = [];
            foreach (@$data['properties'] as $k => $v) {
                $properties[] = array_merge([
                    'name' => $k,
                    'desc' => $this->config['name_lower'] . '_prop_' . $k,
                    'lexicon' => $this->config['name_lower'] . ':properties',
                ], $v);
            }
            $objects[$name]->setProperties($properties);
        }
        $this->category->addMany($objects);
        $this->info('Упаковано сниппетов: ' . count($objects) . '.');
    }


    /**
     * Add chunks
     */
    protected function chunks()
    {
        /** @noinspection PhpIncludeInspection */
        $chunks = include($this->config['elements'] . 'chunks.php');
        if (!is_array($chunks)) {
            $this->error('Чанки не заданы.');

            return;
        }
        $this->category_attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Chunks'] = [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['chunks']),
            xPDOTransport::UNIQUE_KEY => 'name',
        ];
        $objects = [];
        foreach ($chunks as $name => $data) {
            /** @var modChunk[] $objects */
            $objects[$name] = $this->modx->newObject('modChunk');
            $objects[$name]->fromArray(array_merge([
                'id' => 0,
                'name' => $name,
                'description' => @$data['description'],
                'snippet' => $this::_getContent($this->config['core'] . 'elements/chunks/' . $data['file'] . '.tpl'),
                'static' => !empty($this->config['static']['chunks']),
                'source' => 1,
                'static_file' => 'core/components/' . $this->config['name_lower'] . '/elements/chunks/' . $data['file'] . '.tpl',
            ], $data), '', true, true);
            $objects[$name]->setProperties(@$data['properties']);
        }
        $this->category->addMany($objects);
        $this->info('Упаковано чанков: ' . count($objects) . '.');
    }


    /**
     * Add templates
     */
    protected function templates()
    {
        /** @noinspection PhpIncludeInspection */
        $templates = include($this->config['elements'] . 'templates.php');
        if (!is_array($templates)) {
            $this->error('Шаблоны ресурсов не заданы.');

            return;
        }
        $this->category_attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Templates'] = [
            xPDOTransport::UNIQUE_KEY => 'templatename',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['templates']),
            xPDOTransport::RELATED_OBJECTS => false,
        ];
        $objects = [];
        foreach ($templates as $name => $data) {
            /** @var modTemplate[] $objects */
            $objects[$name] = $this->modx->newObject('modTemplate');
            $objects[$name]->fromArray(array_merge([
                'templatename' => $name,
                'description' => $data['description'],
                'content' => $this::_getContent($this->config['core'] . 'elements/templates/' . $data['file'] . '.tpl'),
                'static' => !empty($this->config['static']['templates']),
                'source' => 1,
                'static_file' => 'core/components/' . $this->config['name_lower'] . '/elements/templates/' . $data['file'] . '.tpl',
            ], $data), '', true, true);
        }
        $this->category->addMany($objects);
        $this->info('Упаковано шаблонов ресурсов: ' . count($objects) . '.');
    }


    /**
     * Add access policy
     */
    protected function policies()
    {
        /** @noinspection PhpIncludeInspection */
        $policies = include($this->config['elements'] . 'policies.php');
        if (!is_array($policies)) {
            $this->error('Политики доступа не заданы.');
            return;
        }
        $attributes = [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UNIQUE_KEY => ['name'],
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['policies']),
        ];
        foreach ($policies as $name => $data) {
            if (isset($data['data'])) {
                $data['data'] = json_encode($data['data']);
            }
            /** @var $policy modAccessPolicy */
            $policy = $this->modx->newObject('modAccessPolicy');
            $policy->fromArray(array_merge(['name' => $name, 'lexicon' => $this->config['name_lower'] . ':permissions',], $data), '', true, true);
            $vehicle = $this->builder->createVehicle($policy, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->info('Упаковано политик доступа: ' . count($policies) . '.');
    }


    /**
     * Add policy templates
     */
    protected function policy_templates()
    {
        /** @noinspection PhpIncludeInspection */
        $policy_templates = include($this->config['elements'] . 'policy_templates.php');
        if (!is_array($policy_templates)) {
            $this->error('Шаблоны политик доступа не заданы.');
            return;
        }
        $attributes = [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UNIQUE_KEY => array('name'),
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['policy_templates']),
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array(
                'Permissions' => array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['permission']),
                    xPDOTransport::UNIQUE_KEY => array('template', 'name'),
                ),
            ),
        ];
        foreach ($policy_templates as $name => $data) {
            $permissions = array();
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                foreach ($data['permissions'] as $name2 => $data2) {
                    /** @var $permission modAccessPermission */
                    $permission = $this->modx->newObject('modAccessPermission');
                    $permission->fromArray(array_merge(['name' => $name2, 'description' => $name2, 'value' => true,], $data2), '', true, true);
                    $permissions[] = $permission;
                }
            }
            /** @var $permission modAccessPolicyTemplate */
            $permission = $this->modx->newObject('modAccessPolicyTemplate');
            $permission->fromArray(array_merge(['name' => $name, 'lexicon' => $this->config['name_lower'] . ':permissions',], $data), '', true, true);
            if (!empty($permissions)) {
                $permission->addMany($permissions);
            }
            $vehicle = $this->builder->createVehicle($permission, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->info('Упаковано шаблонов политик доступа:' . count($policy_templates) . '.');
    }


    /**
     * @param $filename
     *
     * @return string
     */
    static public function _getContent($filename)
    {
        if (file_exists($filename)) {
            $file = trim(file_get_contents($filename));

            return preg_match('#\<\?php(.*)#is', $file, $data)
                ? rtrim(rtrim(trim(@$data[1]), '?>'))
                : $file;
        }

        return '';
    }


    /**
     * @param array $data
     * @param string $uri
     * @param int $parent
     *
     * @return array
     */
    protected function _addResource(array $data, $uri, $parent = 0)
    {
        $file = $data['context_key'] . '/' . $uri;
        /** @var modResource $resource */
        $resource = $this->modx->newObject('modResource');
        $resource->fromArray(array_merge([
            'parent' => $parent,
            'published' => true,
            'deleted' => false,
            'hidemenu' => false,
            'createdon' => time(),
            'template' => 1,
            'isfolder' => !empty($data['isfolder']) || !empty($data['resources']),
            'uri' => $uri,
            'uri_override' => false,
            'richtext' => false,
            'searchable' => true,
            'content' => $this::_getContent($this->config['core'] . 'elements/resources/' . $file . '.tpl'),
        ], $data), '', true, true);

        if (!empty($data['groups'])) {
            foreach ($data['groups'] as $group) {
                $resource->joinGroup($group);
            }
        }
        $resources[] = $resource;

        if (!empty($data['resources'])) {
            $menuindex = 0;
            foreach ($data['resources'] as $alias => $item) {
                if (!isset($item['id'])) {
                    $item['id'] = $this->_idx++;
                }
                $item['alias'] = $alias;
                $item['context_key'] = $data['context_key'];
                $item['menuindex'] = $menuindex++;
                $resources = array_merge(
                    $resources,
                    $this->_addResource($item, $uri . '/' . $alias, $data['id'])
                );
            }
        }

        return $resources;
    }

    /**
     * @param \modTransportVehicle $vehicle
     */
    protected function resolvers(modTransportVehicle $vehicle)
    {
        // Add resolvers into vehicle
        foreach (glob($this->config['resolvers'] . '[!_.]*.php') as $resolver) {
            if ($vehicle->resolve('php', ['source' => $resolver])) {
                $this->info('Добавлен резолвер "' . preg_replace('#\.php$#', '', basename($resolver)) . '".');
            }
        }
    }

    /**
     *  Install package
     */
    protected function install()
    {
        $signature = $this->builder->getSignature();
        $sig = explode('-', $signature);
        $versionSignature = explode('.', $sig[1]);
        $this->info('Установка транспортного пакета');
        /** @var modTransportPackage $package */
        if (!$package = $this->modx->getObject('transport.modTransportPackage', ['signature' => $signature])) {
            $package = $this->modx->newObject('transport.modTransportPackage');
            $package->set('signature', $signature);
            $package->fromArray([
                'created' => date('Y-m-d h:i:s'),
                'updated' => null,
                'state' => 1,
                'workspace' => 1,
                'provider' => 0,
                'source' => $signature . '.transport.zip',
                'package_name' => $this->config['name'],
                'version_major' => $versionSignature[0],
                'version_minor' => !empty($versionSignature[1]) ? $versionSignature[1] : 0,
                'version_patch' => !empty($versionSignature[2]) ? $versionSignature[2] : 0,
            ]);
            if (!empty($sig[2])) {
                $r = preg_split('#([0-9]+)#', $sig[2], -1, PREG_SPLIT_DELIM_CAPTURE);
                if (is_array($r) && !empty($r)) {
                    $package->set('release', $r[0]);
                    $package->set('release_index', (isset($r[1]) ? $r[1] : '0'));
                } else {
                    $package->set('release', $sig[2]);
                }
            }
            $package->save();
        }
        if ($package->install()) {
            $this->info('Транспортный пакет установлен.');
            $this->modx->runProcessor('system/clearcache');
        }
    }

    public function getTime()
    {
        $time = microtime(true);
        $diff = $time - $this->time - 0.2; // usleep(200)
        $this->time = $time;

        return number_format($diff, 4);
    }

    protected function info($message = '')
    {
        $this->logger->info($message);
    }

    protected function error($message = '')
    {
        $this->logger->error($message);
    }
}