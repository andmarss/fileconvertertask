<?php

use App\System\Request;

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
