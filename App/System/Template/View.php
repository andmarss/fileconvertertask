<?php

namespace App\System\Template;

use App\System\Session;

class View
{
    /**
     * @var array $data
     */
    protected $data = [];
    /**
     * @var string $path
     */
    protected $path;
    /**
     * @var Template $tpl
     */
    protected $tpl;

    public function __construct(string $path, array $data = [])
    {
        $this->path = $path;
        $this->data = $data;

        $this->tpl = new Template();
    }

    /**
     * @param array $data
     * @return $this
     *
     * мерджит новые данные с уже имеющимися
     */

    public function with(array $data = []): View
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * @return mixed
     *
     * Получить текущий путь файла
     */

    public function getPath(): string
    {
        return (string) $this->path;
    }

    /**
     * @param $path
     * @return $this
     *
     * Установить путь к файлу
     */

    public function setPath(string $path): View
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     *
     * Возвращает HTML-контент
     */

    public function get(): string
    {
        /**
         * @var array $data
         */
        $data = $this->data;

        if(Session::has('redirect')) {
            /**
             * @var array $redirect
             */
            $redirect = Session::get('redirect');

            Session::delete('redirect');

            $data = array_merge($data, $redirect);
        }

        if(Session::has('validator-errors')) {
            /**
             * @var array $errors
             */
            $errors = Session::get('validator-errors');

            Session::delete('validator-errors');

            $data = array_merge($data, ['errors' => $errors]);
        }

        extract($data, EXTR_SKIP);

        ob_start();

        try {
            require $this->path;
        } catch (\Exception $e) {
            return ob_get_clean();
        }

        return ob_get_clean();
    }

    /**
     * Получаем путь к исходному файлу, и данными, которые были переданы
     * Парсим, превращая в валидный PHP код
     * Выводим HTML контент
     *
     * @return string
     * @throws \Exception
     */

    public function render(): string
    {
        $this->path = $this->tpl->parse($this->path);

        return $this->get();
    }

    /**
     * @param string $view
     * @param array $data
     * @return static
     *
     * Статический метод для создания экземпляра
     */

    public static function make(string $view, array $data = []): View
    {
        return new static($view, $data);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function __toString()
    {
        return $this->render();
    }
}