<?php

namespace App\System;

/**
 * Class Router
 * @package App\System
 */

class Router
{
    /**
     * @var Router $instance
     */
    protected static $instance;
    /**
     * @var array $routes
     */
    protected $routes = [
        'GET' => [],
        'POST' => []
    ];
    /**
     * @var array $names
     */
    protected static $names = [];
    /**
     * @var array $request
     */
    protected $request = [];
    /**
     * @var string $uri
     */
    protected $uri = '';

    /**
     * @param string $file
     * @return Router
     */
    public static function load(string $file): Router
    {
        /**
         * @var Router $router
         */
        $router = static::getInstance();

        if(file_exists($file)) {
            require_once $file;
        }

        return $router;
    }

    /**
     * @param string $uri
     * @param string|null $controller
     * @return Router
     *
     * Записывает uri GET-запросов, и привязывает их к контроллерам
     */
    public function get(string $uri, ?string $controller = null): Router
    {
        /**
         * @var bool $is_pattern
         */
        $is_pattern = $this->checkLinkIsPattern($uri);

        $this->uri = $uri;

        if($is_pattern) {
            $this->routes['GET']['patterns'][] = $uri;
        }

        $this->routes['GET'][$uri] = $controller;

        return $this;
    }

    public function post(string $uri, ?string $controller = null): Router
    {
        /**
         * @var bool $is_pattern
         */
        $is_pattern = $this->checkLinkIsPattern($uri);

        $this->uri = $uri;

        if($is_pattern) {
            $this->routes['POST']['patterns'][] = $uri;
        }

        $this->routes['POST'][$uri] = $controller;

        return $this;
    }

    /**
     * @param string $uri
     * @param string $method
     * @return mixed
     * @throws Exception
     */
    public function direct(string $uri, string $method)
    {
        /**
         * @var string $uri
         */
        $uri = ($uri === '') ? '/' : '/' . $uri;
        /**
         * @var array|bool $parameters
         * @var string|null $pattern
         */
        [$parameters, $pattern] = $this->checkRouteExistInPatterns($uri, $method);
        // если в качестве обработчика была установлена обычная callback функция
        if(is_callable($this->routes[$method][$uri])) {
            return $this->call($this->routes[$method][$uri], []);
        } elseif ($parameters && is_callable($this->routes[$method][$pattern])) {
            // если ссылка была шаблоном, и в качестве обработчика была установлена обычная callback функция
            return $this->call($this->routes[$method][$pattern], $parameters);
        } elseif ($parameters && is_string($this->routes[$method][$pattern])) {
            // если ссылка была шаблоном, и в качестве обработчика была установлена строка с разделителем @
            return $this->callAction(
                ...array_merge(explode('@', $this->routes[$method][$pattern]), $parameters)
            );
        } elseif(!$parameters && is_string($this->routes[$method][$pattern])) {
            // если ссылка - не шаблон, и в качестве обработчика была установлена строка с разделителем @
            return $this->callAction(
                ...array_merge(explode('@', $this->routes[$method][$pattern]), [])
            );
        }

        throw new \Exception('Для этого URI не указан маршрут.');
    }

    /**
     * @param string $controller
     * @param string $action
     * @param null ...$params
     * @return Router
     * @throws \Exception
     */
    protected function callAction(string $controller, string $action, ...$params): Router
    {
        $controller = "App\\Controllers\\{$controller}";
        $controller = new $controller;

        if(!method_exists($controller, $action)){
            throw new \Exception(
                "Экшн $action отсутствует в контроллере $controller"
            );
        }

        return $this->call([$controller, $action], $params);
    }

    protected function call($callable, $params): Router
    {
        if(Request::method() === Request::GET) {
            echo $callable(Request::get(), ...$params);
        } elseif (Request::method() === Request::POST) {
            echo $callable(Request::post(), ...$params);
        }

        return $this;
    }

    /**
     * @param string $uri
     * @param string $method
     * @return array|bool
     *
     * Проверяет, есть ли подходящий шаблон для uri
     */
    protected function checkRouteExistInPatterns(string $uri, string $method)
    {
        if(array_key_exists('patterns', $this->routes[$method])) {
            $patterns = $this->routes[$method]['patterns'];

            foreach ($patterns as $pattern) {
                /**
                 * @var array|bool $parameters
                 */
                $parameters = $this->match($pattern, $uri);

                if($parameters) {
                    return [$parameters, $pattern];
                }

                continue;
            }

            return false;
        }

        return false;
    }

    /**
     * @param string $pattern
     * @param string $uri
     * @return array|bool
     *
     * Превращает паттер в uri
     * Если паттер и uri не совпадают - возвращает false
     */
    protected function match(string $pattern, string $uri)
    {
        $pattern = preg_replace('/\{[^\{\}]+\}/', '(.+)', $pattern); // убираем фигурные скобки, заменяем их круглыми
        $pattern = trim($pattern, '/'); // убираем по бокам слеши
        $pattern = preg_replace('/\/+/', '\/', $pattern); // а так же все лишние слеши

        preg_match_all('/' . $pattern . '/', $uri, $m); // применяем паттерн, получаем id, который был передан в маршрут

        if($m && isset($m[0])) {
            return array_slice($m, 1);
        } else {
            return false;
        }
    }

    /**
     * @param string $uri
     * @return bool
     *
     * Проверяет, есть ли у uri паттерн, по которому этот uri должен отработать
     */
    protected function checkLinkIsPattern(string $uri): bool
    {
        return (bool) preg_match('/\{([^\{|\}]+)\}/', $uri);
    }

    /**
     * @return Router
     */
    protected static function getInstance(): Router
    {
        if(!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}