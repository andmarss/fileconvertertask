<?php

namespace App\System\Template;

class Section
{
    protected static $sections = [];

    protected static $last = [];

    /**
     * @param $section
     * @param string $content
     *
     * Начало секции, или конкатенация её контента
     */

    public static function start($section, $content = '')
    {
        if($content === '') {
            ob_start();

            static::$last[] = $section;
        } else {
            static::append($section, $content);
        }
    }

    /**
     * @param $section
     * @param $content
     *
     * Добавление к секции контента
     */

    public static function append($section, $content): void
    {
        if (isset(static::$sections[$section]))
        {
            static::$sections[$section] .= $content;
        }
        else
        {
            static::$sections[$section] = $content;
        }
    }

    /**
     * @param $section
     * @param $content
     *
     * Внедрение секции, аналог section::start
     */

    public static function inject($section, $content)
    {
        static::start($section, $content);
    }

    /**
     * @return mixed
     *
     * Получение контента последней секции
     * Остановка записи буфера
     */

    public static function stop()
    {
        static::extend($last = array_pop(static::$last), ob_get_clean());

        return $last;
    }

    /**
     * Через переменную @parent в соответствующей секции можно получить контент родителя
     * //parent
     * @section('a')
     *      <a href="#"></a>
     * @endsection
     *
     * //child
     * @section('a')
     *      <b>@parent</b> => <b><a href="#"></a></b>
     * @endsection
     *
     * @param $section
     * @param $content
     * @return void
     */
    protected static function extend($section, $content): void
    {
        if (isset(static::$sections[$section]))
        {
            static::$sections[$section] = str_replace('@parent', $content, static::$sections[$section]);
        }
        else
        {
            static::$sections[$section] = $content;
        }
    }

    /**
     * @param $section
     * @return mixed|string
     *
     * Выводит контент секции по имени секции
     */

    public static function yield($section): string
    {
        return (isset(static::$sections[$section])) ? static::$sections[$section] : '';
    }

    /**
     * @return mixed|string
     *
     * Возвращает контент последней активной секции
     */

    public static function yield_section()
    {
        return static::yield( static::stop() );
    }
}