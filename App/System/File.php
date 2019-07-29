<?php


namespace App\System;


class File
{
    /**
     * @param string $path
     * @return bool
     *
     * Проверка существования файла
     */

    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * @param string $path
     * @return bool|string
     *
     * Загрузить контент из файла, если существует файл
     */

    public static function get(string $path): string
    {
        return static::exists($path) ? file_get_contents($path) : '';
    }

    /**
     * Записать данные в файл
     *
     * @param string $path
     * @param string|null $data
     * @return bool|int
     */

    public static function put(string $path, ?string $data)
    {
        return file_put_contents($path, $data, LOCK_EX);
    }

    /**
     * Записать данные в конец файла (не перезатирать)
     *
     * @param string $path
     * @param string|null $data
     * @return bool|int
     */

    public static function append(string $path, ?string $data = '')
    {
        return file_put_contents($path, $data, LOCK_EX | FILE_APPEND);
    }

    /**
     * Удалить файл
     *
     * @param string $path
     */

    public static function delete(string $path)
    {
        if(static::exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Получить формат файла
     *
     * @param string $path
     * @return mixed
     */

    public static function extension(string $path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Получить тип
     *
     * @param string $path
     * @return false|string
     */

    public static function type(string $path)
    {
        return filetype($path);
    }

    /**
     * Получить размер файла (в байтах)
     *
     * @param string $path
     * @return false|int
     */

    public static function size(string $path)
    {
        return filesize($path);
    }

    /**
     * @param string $path
     * @return false|int
     */

    public static function modified(string $path)
    {
        return filemtime($path);
    }

    /**
     * @param string $path
     * @param string $target
     * @return bool
     */
    public static function copy(string $path, string $target): bool
    {
        return copy($path, $target);
    }

    public static function mime(string $extension, string $default = 'application/octet-stream'): string
    {
        /**
         * @var array $mimes
         */
        $mimes = Config::get($extension);

        if(!array_key_exists($extension, $mimes)) return $default;

        return is_array($mimes[$extension]) ? current($mimes[$extension]) : $mimes[$extension];
    }

    /**
     * Путь к корню директории проекта
     */

    public static function root(): string
    {
        return dirname(dirname(__DIR__));
    }

    /**
     * Путь к папке views
     */

    public static function viewPath(): string
    {
        return static::root() . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
    }

    /**
     * Путь к папке storage/cache
     */

    public static function cachePath(): string
    {
        return static::root() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
    }

    /**
     * @param $path
     * @return string
     *
     * Возвращает не полный путь к файлу внутри папки cache
     */

    public static function getNotFullCompiledPath($path): string
    {
        return 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . md5($path) . '.php';
    }

    /**
     * @param $path
     * @return string
     *
     * Получить полный путь к скомилированному файлу
     */

    public static function getCompiledPath($path): string
    {
        return static::cachePath() . md5($path) . '.php';
    }

    /**
     * @param $path
     * @return string
     *
     * Получить полный путь к файлу
     */

    public static function getViewPath($path): string
    {
        return static::viewPath() . $path;
    }

    /**
     * @param $path
     * @return bool
     *
     * Проверяет, изменился ли исходный файл по отношению к скомпилированному
     */

    public static function isExpired($path): bool
    {
        $compiled = static::getCompiledPath($path);

        if (!File::exists($compiled) )
        {
            return true;
        }

        return static::modified( $path ) >= static::modified( $compiled );
    }
}