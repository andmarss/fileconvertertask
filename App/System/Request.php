<?php

namespace App\System;

class Request
{
    public const GET = 'GET';
    public const POST = 'POST';

    /**
     * @var Request $instance
     */
    protected static $instance;
    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * @return string
     * Возвращает чистый uri (убирает боковые слеши)
     */
    public function uri()
    {
        return trim(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'
        );
    }
    /**
     * @return string
     * Возвращает полный uri, включая доменное имя
     */
    public function fullUriWithQuery()
    {
        return domain() . $this->uri();
    }

    /**
     * @return string
     *
     * Возвращает метод щапроса (GET или POST)
     */
    public function method(): string
    {
       return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @param $key
     * @return mixed
     */

    public function __get($key)
    {
        if(isset($this->data[$key])){
            return $this->data[$key];
        }
    }

    /**
     * @param $key
     * @param $value
     */

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * @param $name
     * @return bool
     */

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * @param string $name
     * @return mixed
     *
     * Возвращает обхект get запроса, включающий в себя все свойства get запроса
     */

    public function get(?string $name = null)
    {
        if($name && isset($_GET[$name])) {
            return $_GET[$name];
        } else {
            foreach ($_GET as $key => $value) {
                static::getInstance()->{$key} = $value;
            }

            return static::getInstance();
        }
    }

    /**
     * @param string $name
     * @return mixed
     *
     * Возвращает обхект post запроса, включающий в себя все свойства post запроса
     */
    public function post(?string $name = null)
    {
        if($name && isset($_POST[$name])) {
            return $_POST[$name];
        } else {
            foreach ($_POST as $key => $value) {
                static::getInstance()->{$key} = $value;
            }

            return static::getInstance();
        }
    }

    /**
     * @return Request
     */
    protected static function getInstance(): Request
    {
        if(!static::$instance) {
            static::$instance = new Request();
        }

        return static::$instance;
    }

    public static function __callStatic($method, $arguments)
    {
        return static::getInstance()->{$method}(...$arguments);
    }
}