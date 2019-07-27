<?php


namespace App\System;


class Session
{
    /**
     * @var Session $instance
     */
    protected static $instance;

    /**
     * Добавляет элемент в сессию
     *
     * @param string|null $key
     * @param $value
     * @return mixed
     */
    public function put(?string $key, $value)
    {
       if($key && !is_null($value)) {
           $_SESSION[$key] = $value;

           return $_SESSION[$key];
       }
    }

    /**
     * Проверяет, имеется ли элемент с переданным ключом в сессии
     *
     * @param string|null $key
     * @return bool
     */
    public function exists(?string $key): bool
    {
       return isset($_SESSION[$key]);
    }
    /**
     * Проверяет, имеется ли элемент с переданным ключом в сессии (дубль)
     *
     * @param string|null $key
     * @return bool
     */
    public function has(?string $key): bool
    {
       return $this->exists($key);
    }
    /**
     * Если было передано два значения
     * Сохраняет в сессию по имени name значение value
     * Если передан одно name - возвращает значение из сессии, после чего удаляет его
     *
     * @param $key
     * @param string $value
     * @return mixed|Session
     */
    public function flash(?string $key, ?string $value = '')
    {
       if($this->exists($key)) {
           $value = $this->get($key);

           $this->delete($key);

           return $value;
       } else {
           $this->put($key, $value);
       }

       return $this;
    }

    /**
     * Получить значение по ключу, или null, если значения в сессии нет
     *
     * @param string|null $key
     * @return mixed|null
     */
    public function get(?string $key)
    {
       return $this->exists($key) ? $_SESSION[$key] : null;
    }

    /**
     * @param $key
     * @return bool
     */
    public function delete($key): bool
    {
       if($this->exists($key)) {
           unset($_SESSION[$key]);
       }

       return false;
    }

    /**
     * @param $method
     * @param $args
     * @return Session
     */
    public static function __callStatic($method, $args): Session
    {
        if(!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}