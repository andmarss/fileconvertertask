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
     * @var array $where
     */
    protected $where = [];

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
     * @return Router
     * @throws \Exception
     */
    public function direct(string $uri, string $method)
    {
        /**
         * @var string $uri
         */
        $uri = ($uri === '') ? '/' : '/' . $uri;
        /**
         * @var array $parameters
         */
        $parameters = [];
        /**
         * @var array|bool $parameters
         * @var string|null $pattern
         */
        $pattern = $this->checkRouteExistInPatterns($uri, $method);

        if($pattern) {
            $parameters = $this->getParams($pattern, $uri);
        }

        // если в качестве обработчика была установлена обычная callback функция
        if(is_callable($this->routes[$method][$uri])) {
            return $this->call($this->routes[$method][$uri], []);
        } elseif ($pattern && $parameters && is_callable($this->routes[$method][$pattern])) {
            // если ссылка была шаблоном, и в качестве обработчика была установлена обычная callback функция
            return $this->call($this->routes[$method][$pattern], $parameters);
        } elseif ($pattern && $parameters && is_string($this->routes[$method][$pattern])) {
            // если ссылка была шаблоном, и в качестве обработчика была установлена строка с разделителем @
            [$controller, $action] = explode('@', $this->routes[$method][$pattern]);
            return $this->callAction(
                $controller, $action, $parameters
            );
        } elseif(!$pattern && !$parameters && is_string($this->routes[$method][$uri])) {
            // если ссылка - не шаблон, и в качестве обработчика была установлена строка с разделителем @
            [$controller, $action] = explode('@', $this->routes[$method][$uri]);

            return $this->callAction(
                $controller, $action, []
            );
        }

        throw new \Exception('Для этого URI не указан маршрут.');
    }

    /**
     * @param string $name
     * @return Router
     * @throws \Exception
     */
    public function name(string $name): Router
    {
        if(array_key_exists($name, static::$names)) {
            throw new \Exception("Имя маршрута \"$name\" уже объявлено. Выберите другое имя.");
        }

        static::$names[$name] = $this->uri;

        return $this;
    }

    /**
     * @param string $controller
     * @param string $action
     * @param null ...$params
     * @return Router
     * @throws \Exception
     */
    protected function callAction(string $controller, string $action, array $params = []): Router
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

    protected function call($callable, array $params = []): Router
    {
        $request = Request::{strtolower(Request::method())}();

        if($params) {
            foreach ($params as $key => $value) {
                $request->{$key} = $value;
            }
        }

        echo $callable($request, ...array_values($params));

        return $this;
    }

    /**
     * @param string $uri
     * @param string $method
     * @return array|bool
     *
     * Проверяет, есть ли подходящий шаблон для uri
     */
    protected function checkRouteExistInPatterns(string $uri, string $method): string
    {
        if(array_key_exists('patterns', $this->routes[$method])) {
            $patterns = $this->routes[$method]['patterns'];

            foreach ($patterns as $pattern) {
                /**
                 * @var array|bool $parameters
                 */
                $parameters = $this->match($pattern, $uri);

                if($parameters) {
                    return $pattern;
                }

                continue;
            }

            return '';
        }

        return '';
    }

    /**
     * Применить к маршруту с динамическими параметрами
     * условие в виде регулярного выражения
     *
     * @param array $condition
     * @return Router
     */
    public function where(array $condition): Router
    {
        if(count($condition) > 0) {
            if(!isset($this->where[$this->uri])) $this->where[$this->uri] = [];

            foreach ($condition as $param => $pattern) {
                $this->where[$this->uri][$param] = $pattern;
            }
        }

        return $this;
    }

    /**
     * @param string $pattern
     * @param string $uri
     * @return array
     */
    protected function getParams(string $pattern, string $uri): array
    {
        /**
         * @var array $keys
         */
        $keys = $this->getParamsKeys($pattern);
        /**
         * @var array $values
         */
        $values = $this->getParamsValues($pattern, $uri);
        /**
         * @var array $params
         */
        $params = [];

        foreach ($keys as $index => $key){
            $params[$key] = $values[$index];
        }

        return $params;
    }

    /**
     * @param string $pattern
     * @return array
     */
    protected function getParamsKeys(string $pattern): array
    {
        /**
         * @var string $pattern
         */
        $pattern = preg_replace('/^\/+|\/+$/', '', $pattern);
        $pattern = $pattern === '' ? '/' : $pattern;

        if(preg_match_all('/\{([^}])+\}/', $pattern, $m) && isset($m[0])) {
            return array_map(function (string $match){
                return preg_replace('/\{|\}/', '', $match);
            }, $m[0]);
        }

        return [];
    }

    /**
     * @param string $pattern
     * @param string $uri
     * @return array
     */
    protected function getParamsValues(string $pattern, string $uri): array
    {
        /**
         * @var array $where
         */
        $where = isset($this->where[$pattern]) ? $this->where[$pattern] : [];
        // если есть условия
        if($where && count($where) > 0) {
            foreach ($where as $param => $wherePattern) {
                if(preg_match("/\{[$param]+\}/", $pattern)) {
                    $pattern = preg_replace("/\{[$param]+\}/", "($wherePattern)", $pattern);
                } else {
                    continue;
                }
            }

            $pattern = preg_replace("/\/+/", '\\/', $pattern);

            if(preg_match_all("/\{[^\{\}]+\}/", $pattern)) {
                $pattern = preg_replace('/\{[^\{\}]+\}/', "([^/]+)", $pattern);
            }

            if(preg_match_all("/$pattern/", $uri, $m)) {
                return array_slice(array_map(function(array $match){
                    if(isset($match[0])) {
                        if(strpos($match[0], '/') !== 0) {
                            return  '/' . $match[0];
                        } else {
                            return $match[0];
                        }
                    } else {
                        return [];
                    }
                }, $m), 1);
            }
        } else {
            if(preg_match_all("/\{[^\{\}]+\}/", $pattern)) {
                $pattern = preg_replace('/\{[^\{\}]+\}/', "([^/]+)", $pattern);
            }

            if(preg_match_all("/$pattern/", $uri, $m)) {
                return array_slice(array_map(function(array $match){
                    if(isset($match[0])) {
                        if(strpos($match[0], '/') !== 0) {
                            return  '/' . $match[0];
                        } else {
                            return $match[0];
                        }
                    } else {
                        return [];
                    }
                }, $m), 1);
            }
        }

        return [];
    }

    /**
     * @param string $name
     * @param array $data
     * @throws \Exception
     * @return string
     */
    protected function convertUri(string $name, array $data = []): string
    {
        if(!array_key_exists($name, static::$names)) {
            throw new \Exception("Имя маршрута \"$name\" не объявлено");
            return '';
        }
        /**
         * @var string $name
         */
        $uri = static::$names[$name];
        /**
         * @var bool $isPattern
         */
        $isPattern = $this->checkLinkIsPattern($uri);

        if($isPattern) {
            preg_match_all('/\{([^\{|\}]+)\}/', $uri, $m);

            if ($m && isset($m[0]) && count($m[0]) > 0 && (count($m[0]) === count($data))) {

                $uri = preg_replace('/\{[^\{\}]+\}/', '%s', $uri);
                /**
                 * @var string $route
                 */
                $route = sprintf($uri, ...array_values($data));

                return $route;
            } else {
                throw new \Exception("Паттерн маршрута не совпадает с переданными параметрами");
            }
        } else {
            return $uri;
        }
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
            return count(
                array_filter(array_map(function (array $match){
                    return (bool) $match[0];
                }, array_slice($m, 1)), function (bool $match){
                    return $match;
                })
            ) > 0;
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

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        /**
         * @var Router $instance
         */
        $instance = static::getInstance();

        if(method_exists($instance, $method)) {
            return $instance->{$method}(...$arguments);
        }
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        /**
         * @var Router $instance
         */
        $instance = static::getInstance();

        if(method_exists($instance, $method)) {
            return $instance->{$method}(...$arguments);
        }
    }
}