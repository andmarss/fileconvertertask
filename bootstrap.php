<?php

session_start();

use App\System\{Request,Router,Response,Redirect,Template\View, Collection};

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

/**
 * @param string $content
 * @param int $status
 * @param array $headers
 * @return Response
 */
function response(string $content = '', int $status = 200, array $headers = []): Response
{
    return new Response($content, $status, $headers);
}

/**
 * @param string $name
 * @return string
 */
function old(string $name): string
{
    return Request::old($name);
}

/**
 * @param string $path
 * @param array $data
 * @return Redirect
 */
function redirect(string $path = '', array $data = []): Redirect
{
    return (new Redirect($path, $data));
}

/**
 * Вернуть путь к папке content
 *
 * @param string $path
 * @return string
 */
function content_path(string $path = ''): string
{
    if($path) {
        $path = preg_replace('/\/+/', DIRECTORY_SEPARATOR, $path);
    }

    return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . $path;
}

/**
 * @param string $name
 * @return string
 */
function slug(string $name): string
{
    if($name) {
        /**
         * @var array $cyrillic
         */
        $cyrillic = [
            'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
            'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
            'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
            'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'
        ];
        /**
         * @var array $latin
         */
        $latin = [
            'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p',
            'r','s','t','u','f','h','ts','ch','sh','sht','a','i','\'','e','yu','ya',
            'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P',
            'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','\'','e','Yu','Ya'
        ];

        return strtolower(preg_replace('/[\s]+/', '-', str_replace($cyrillic, $latin, $name)));
    }

    return '';
}

/**
 * @param array $collection
 * @return Collection
 */
function collect(array $collection): Collection
{
    return Collection::make($collection);
}