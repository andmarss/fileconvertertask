<?php


namespace App\System;


class Config
{
    protected static $config;

    /**
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset(static::$config[$key]);
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return mixed|null
     */
    public static function get(string $key, ?string $default = null)
    {
        if(is_null(static::$config)) {
            static::load();
        }

        if(!static::$config || !static::has($key)) return $default;

        return static::$config[$key];
    }

    /**
     * Загрузить файл конфигурации
     */
    protected static function load(): void
    {
        $file = File::root() . DIRECTORY_SEPARATOR . 'config.php';

        if(file_exists($file)) {
            static::$config = (require $file)['mimes'];
        } else {
            static::$config = [];
        }
    }
}