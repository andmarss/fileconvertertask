<?php


namespace App\System;


class UploadedFile
{
    /**
     * @var object $file
     */
    protected $file;
    /**
     * @var UploadedFile $instance
     */
    protected static $instance;

    public function __construct(array $file)
    {
        $this->file = (object) $file;
    }

    /**
     * @return string
     *
     * Возвращает имя файла
     */

    public function getClientOriginalName()
    {
        return basename($this->file->name);
    }

    /**
     * @return mixed
     *
     * Возвращает формат файла
     */

    public function getClientOriginalExtension()
    {
        return pathinfo($this->file->name, PATHINFO_EXTENSION);
    }

    /**
     * @return mixed
     *
     * Возвращает тип файла
     */

    protected function getType()
    {
        return $this->file->type;
    }

    /**
     * @return mixed
     *
     * Возвращает размер файла (в байтах)
     */

    protected function getSize()
    {
        return $this->file->size;
    }

    /**
     * @param string $directory
     * @param null $name
     * @return bool
     * @throws \Exception
     *
     * Размещает файл в указанной директории
     */

    protected function move(string $directory, $name = null)
    {
        /**
         * @var string $uploaddir
         */
        $uploaddir = $this->contentDir() . trim( preg_replace('/\//', DIRECTORY_SEPARATOR, $directory), '/') . DIRECTORY_SEPARATOR;

        if(!file_exists($uploaddir) && !is_dir($uploaddir)) {
            mkdir($uploaddir, 0755, true);
        }
        /**
         * @var string $uploadfile
         */
        $uploadfile = $uploaddir . basename(!is_null($name) ? $name : $this->file->name);

        $moved = move_uploaded_file($this->file->tmp_name, $uploadfile);

        if (!$moved) {
            throw new \Exception('Не получилось загрузить файл ' . !is_null($name) ? $name : $this->file->name);
        }

        return $moved;
    }

    /**
     * @return array
     *
     * Возвращает массив, содержащий данные по длине и ширине переданного изображения
     */

    protected function size()
    {
        if(isset($this->file)) {
            [$width, $height] = getimagesize($this->file->tmp_name);

            return [$width, $height];
        } else {
            return [null, null];
        }
    }

    /**
     * @return null|string
     *
     * Возвращаен формат файла
     */

    protected function extension()
    {
        return $this->file ? strtolower(pathinfo($this->file->name, PATHINFO_EXTENSION)) : null;
    }

    /**
     * @return string
     *
     * Возвращает полный путь к папке content
     */

    protected function contentDir()
    {
        return $this->pathToProjectRootWithSystemPath() . $this->contentDirWithoutRootSystem();
    }

    /**
     * @return string
     *
     * Возвращает путь к папке content внутри проекта
     */

    protected function contentDirWithoutRootSystem()
    {
        return DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     *
     * Возвращает полный путь к корню проекта
     */

    protected function pathToProjectRootWithSystemPath()
    {
        return dirname(dirname(__DIR__));
    }

    /**
     * @return string|null
     *
     * Возвращает тип файла
     */

    protected function type()
    {
        return $this->file ? $this->file->type : null;
    }

    public static function __callStatic($method, $args)
    {
        static::$instance = new static([]);

        return static::$instance->$method(...$args);
    }

    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return $this->{$method}(...$args);
        }
    }
}