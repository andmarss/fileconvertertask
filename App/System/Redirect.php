<?php


namespace App\System;


class Redirect
{
    protected $path;
    protected $data;

    public function __construct(string $path = '', $data = [])
    {
        Session::flash('old', Request::{Request::method()}());

        if($path) {
            $this->path = $path;
            $this->to($path, $data);
        }

        return $this;
    }

    /**
     * @param string $path
     * @param array $data
     *
     * На какую страницу будет переведен пользователь
     */

    public function to(string $path, array $data = []): void
    {
        if($data) {
            Session::put('redirect', $data);
        }
        /**
         * @var string $url
         */
        $url = domain() . '/' . trim( parse_url($path, PHP_URL_PATH), '/' );

        header("Location: ${url}");
        exit();
    }

    /**
     * @param array $data
     *
     * Возвращает пользователя на страницу, с которой был произведен запрос
     */

    public function back(array $data = []): void
    {
        if($data) {
            Session::put('redirect', $data);
        }
        /**
         * @var string $url
         */
        $url = !is_null($_SERVER['HTTP_REFERER']) ? domain() . '/' . trim( parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH), '/' ) : '/';
        header("Location: ${url}");
        exit();
    }

    /**
     * @param $name
     * @param array $params
     * @throws \Exception
     *
     * Перенаправлят пользователя по имени маршрута
     */

    public function route(string $name, array $params = []): void
    {
        $url = Router::convertUri($name, $params);

        $this->to($url);
    }
}