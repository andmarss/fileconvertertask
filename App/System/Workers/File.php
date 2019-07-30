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

    /**
     * @param bool $withoutExtension
     * @return string
     */
    public function name(bool $withoutExtension = false): string
    {
       return $withoutExtension
           ? preg_replace('/\.$/', '', $this->fileInfo->getBasename($this->extension()))
           : $this->fileInfo->getBasename();
    }

    /**
     * @return bool
     */
    public function hasCyrillicCharacters(): bool
    {
        return (bool) preg_match('/[А-Яа-яЁё]/u', $this->name());
    }

    /**
     * @param string $to
     * @return File
     * @throws \Exception
     */
    public function rename(string $to): File
    {
        $pathname = $this->path();
        $pathWithoutFile = $this->path(true);
        $newName = $pathWithoutFile . DIRECTORY_SEPARATOR . $to;

        unset($this->fileInfo);
        unset($this->fileObject);

        $result = rename($pathname, $newName);

        return new static($newName);
    }

    /**
     * @param string $content
     * @return bool
     */
    public function contentExist(string $content): bool
    {
       if($this->fileInfo->isReadable() && $this->fileInfo->isWritable()) {
           $fileContent = $this->read();

           return (bool) preg_match_all("/$content/", $fileContent);
       }

       return false;
    }

    /**
     * @return Directory
     */
    public function directory(): Directory
    {
        return $this->dir;
    }
    /**
     * @param string $string
     * @return string
     */
    public function reconvertUtf8(string $string): string
    {
        if(mb_strtolower(
                mb_detect_encoding($string)
            ) === 'utf-8') {
            $string = iconv('UTF-8', 'cp437//IGNORE', $string);
            $string = iconv('cp437', 'cp865//IGNORE', $string);
            $string = iconv('cp866','UTF-8//IGNORE',$string);

            return $string;
        }

        return '';
    }
}