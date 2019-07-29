<?php


namespace App\System;


class Cookie
{
    protected static $instance;

    /**
     * @param string $name
     * @return bool
     *
     * Проверяет, есть ли в cookies элемент с переданым ключом
     */

    protected function exists(string $name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * @param string $name
     * @return bool
     *
     * Проверяет, есть ли в cookies элемент с переданым ключом
     */

    protected function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * @param string $name
     * @return mixed
     *
     * Возвращает элемент по ключу
     */

    protected function get(string $name)
    {
        return $this->has($name) ? $_COOKIE[$name] : false;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @return bool
     *
     * Сохраняет элемент по ключу
     */

    protected function put(string $name, ?string $value, int $expire = 86400): bool
    {
        if(setcookie($name, $value, time() + $expire, '/')) {
            return true;
        }

        return false;
    }

    /**
     * @param $name
     * @return bool
     *
     * Удаляет элемент по ключу
     */

    protected function delete(string $name): bool
    {
        if($this->has($name)) {
            return $this->put($name, '', -1);
        }

        return false;
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        if(!is_object(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance->$method(...$args);
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        if (method_exists($this, $method)) {
            return $this->{$method}(...$args);
        }
    }
}