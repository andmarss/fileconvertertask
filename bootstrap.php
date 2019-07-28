<?php

use App\System\Request;
use App\System\Template\View;
use App\System\Router;

/**
 * @return string
 *
 * возвращает доменное имя приложения, включая протокол
 */
function domain(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];

    return $protocol . $domainName;
}
/**
 * @return Request
 *
 * Функция для работы с объектом запроса
 */
function request()
{
    return (new Request())->{Request::method()}();
}

/**
 * Отобразить шаблон
 *
 * @param $name
 * @param array $data
 * @return View
 */
function view($name, $data = []): View
{
    if(strpos($name, '.view.php')){
        return ( new View("views/{$name}", $data) );
    } else {
        return ( new View("views/{$name}.view.php", $data) );
    }
}

/**
 * Получить ссылку по алиасу
 *
 * @param string $name
 * @param array $data
 * @return string
 * @throws Exception
 */
function route(string $name, array $data = []): string
{
    return domain() . Router::convertUri($name, $data);
}

/**
 * Путь к статичным данным
 *
 * @param $path
 * @return string
 */
function asset($path): string
{
    return domain(). '/content/' . $path;
}

function response()
{
    return new \App\System\Response();
}