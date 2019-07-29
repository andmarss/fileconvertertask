<?php

namespace App\System\Workers;

use \SplFileInfo;
use \SplFileObject;
use App\System\File as FileInfo;

class File
{
    /**
     * @var SplFileInfo $fileInfo
     */
    protected $fileInfo;
    /**
     * @var SplFileObject $fileObject
     */
    protected $fileObject;
    /**
     * @var Directory $dir
     */
    protected $dir;

    public function __construct(string $path)
    {
        /**
         * @var string $path
         */
        $path = preg_replace('/\/+/', DIRECTORY_SEPARATOR, $path);

        if(!file_exists($path)) {
            throw new \Exception("File \"$path\" does not exist");
        }

        $this->fileInfo = new SplFileInfo($path);
        $this->fileObject = new SplFileObject($path);
        $this->dir = new Directory($this->fileInfo->getPath());
    }

    /**
     * @param bool $withoutFile
     * @return string
     */
    public function path(bool $withoutFile = false): string
    {
       return $withoutFile ? $this->fileInfo->getPath() : $this->fileInfo->getRealPath();
    }

    /**
     * @return string
     */
    public function read(): string
    {
        if($this->fileInfo->isReadable() && $this->fileInfo->isWritable()) {
            return FileInfo::get($this->path());
        }

        return '';
    }

    /**
     * @param string $find
     * @param string $replace
     * @return File
     */
    public function replace(string $find, string $replace): File
    {
        if($this->fileInfo->isReadable() && $this->fileInfo->isWritable()) {
            /**
             * @var string $content
             */
            $content = $this->read();

            $content = str_replace($find, $replace, $content);

            $this->write($content);
        }

        return $this;
    }

    /**
     * @param string $content
     * @param bool $append
     * @return bool
     */
    public function write(string $content, bool $append = false): bool
    {
       if($this->fileInfo->isWritable()) {
            try {
                if($append) {
                    FileInfo::append($this->path(), $content);
                } else {
                    FileInfo::put($this->path(), $content);
                }
            } catch (\Exception $e) {
                die(var_dump($e->getMessage()));

                return false;
            }

            return true;
       } else {
           return false;
       }
    }

    /**
     * @return string
     */
    public function extension(): string
    {
       return mb_strtolower($this->fileInfo->getExtension());
    }
}