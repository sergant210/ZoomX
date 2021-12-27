<?php

namespace Zoomx\Commands;


class CommandManager
{
    public static $commands = [
        'cache' => Cache::class,
    ];

    public static function execute(array $params)
    {
        $command = self::createCommand(array_shift($params));
        if (is_callable($command)) {
            return call_user_func($command, $params);
        }

        return 'No command is found.';
    }

    public static function addCommand(array $command)
    {
        self::$commands = array_merge(self::$commands, $command);
    }

    protected static function createCommand($data)
    {
        [$alias, $method] = explode(':', $data);
        if (isset(self::$commands[$alias]) && class_exists(self::$commands[$alias])) {
            return [new self::$commands[$alias](zoomx('modx')), $method];
        }

        return null;
    }
}