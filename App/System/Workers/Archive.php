<?php


namespace App\System\Workers;

/**
 * Class Archive
 * @package App\System\Workers
 */

class Archive
{
    /**
     * @var File $archiveInfo
     */
    protected $archiveInfo;
    /**
     * @var \ZipArchive|\RarArchive $archive
     */
    protected $archive;
    /**
     * @var string|null $password
     */
    protected $password;

    public function __construct(string $path, ?string $password = null)
    {
        /**
         * @var string $path
         */
        $path = preg_replace('/\/+/', DIRECTORY_SEPARATOR, $path);

        if(!file_exists($path)) {
            throw new \Exception("File \"$path\" does not exist");
        }

        $this->archiveInfo = new File($path);

        if($this->archiveInfo->extension() === 'zip') {
            $this->archive = new \ZipArchive();
        } elseif ($this->archiveInfo->extension() === 'rar') {
            $this->archive = new \RarArchive();
        } else {
            throw new \Exception('Неподдерживаемый тип архива: ' . $this->archiveInfo->extension());
        }

        if($password) {
            $this->password = $password;
        }
    }

    /**
     * @return string
     */
    public function extension(): string
    {
       return $this->archiveInfo->extension();
    }

    /**
     * Возвращает полный путь к файлу
     * Если передан не обязательный параметр $withoutFile
     * Вернет путь к родительской директории
     *
     * @param bool $withoutFile
     * @return string
     */
    public function path(bool $withoutFile = false): string
    {
       return $this->archiveInfo->path($withoutFile);
    }

    /**
     * @param string $path
     * @return Archive
     * @throws \Exception
     */
    public function unzip(string $path): Archive
    {
        /**
         * @var string $path
         */
        $path = preg_replace('/\/+/', DIRECTORY_SEPARATOR, $path);

        if(!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if(!$this->archive->open($this->path())) {
            throw new \Exception("Невозможно открыть архив " . $this->path());
        }

        if($this->password) {
            $this->archive->setPassword($this->password);
        }

        for ($i = 0; $i < $this->archive->numFiles; $i++) {
            $filename = $this->archive->getNameIndex($i);

            if($this->isUtf8($filename)) {
                // если есть слеши - то конвертировать имя не нужно
                if(preg_match('/\-+|\_+/', $filename)) {
                    $this->archive->extractTo($path, $filename);
                } else {
                    // иначе - конвертируем имя
                    $name = $this->reconvertUtf8(
                        $filename
                    );

                    $this->archive->renameName($filename, $name);

                    $this->archive->extractTo($path, $name);
                }
            } else {
                $this->archive->extractTo($path, $filename);
            }
        }

        @$this->archive->close();

        return $this;
    }

    /**
     * @param bool $withoutExtension
     * @return string
     */
    public function name(bool $withoutExtension = false): string
    {
       return $this->archiveInfo->name($withoutExtension);
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        $path = $this->path();
        unset($this->archive);
        unset($this->archiveInfo);

        return unlink($path);
    }
    /**
     * @param array $files
     * @return $this
     * @throws \Exception
     */
    public function zip(array $files): Archive
    {
        if(count($files) === 0) return $this;

        $extension = $this->archiveInfo->extension();
        $path = $this->path();

        $this->delete();

        if($extension === 'zip') {
            $this->archive = new \ZipArchive();
        } elseif ($extension === 'rar') {
            $this->archive = new \RarArchive();
        } else {
            throw new \Exception('Неподдерживаемый тип архива: ' . $extension);
        }

        if(!$this->archive->open($path, $this->archive::OVERWRITE | $this->archive::CREATE)) {
            throw new \Exception("Невозможно открыть архив: " . $path);
        }

        foreach ($files as $file) {
            $this->archive->addFile($file->path(), str_replace(
                static::archivesDirectoryPath(),
                '',
                $file->path()
            ));
        }

        @$this->archive->close();

        $this->archiveInfo = new File($path);

        return $this;
    }

    /**
     * @return int
     */
    public function size(): int
    {
       return $this->archiveInfo->size();
    }
    /**
     * Конвертирует строку так, что бы строка приобрела читаемый вид
     *
     * @param string|null $string
     * @return string
     */
    protected function reconvertUtf8(?string $string): string
    {
        if(is_string($string) && mb_strlen($string) && $this->isUtf8($string)) {
            $string = iconv('UTF-8', 'cp437//IGNORE', $string);
            $string = iconv('cp437', 'cp865//IGNORE', $string);
            $string = iconv('cp866','UTF-8//IGNORE',$string);

            return $string;
        } else {
            $string = iconv('UTF-8', 'cp437//IGNORE', $this->name());
            $string = iconv('cp437', 'cp865//IGNORE', $string);
            $string = iconv('cp866','UTF-8//IGNORE',$string);

            return $string;
        }
    }

    /**
     * Проверяет кодировку переданной строки
     *
     * @param string|null $string
     * @return bool
     */
    protected function isUtf8(?string $string): bool
    {
        return mb_strlen($string) > 0
            ? mb_strtolower(
                mb_detect_encoding($string)
            ) === 'utf-8'
            : mb_strtolower(
                mb_detect_encoding($this->archiveInfo->name())
            ) === 'utf-8';
    }

    /**
     * Возвращает путь к папке с архивами
     *
     * @return string
     */
    protected static function archivesDirectoryPath(): string
    {
        return \App\System\File::root()
            . DIRECTORY_SEPARATOR
            . 'content'
            . DIRECTORY_SEPARATOR
            . 'uploaded'
            . DIRECTORY_SEPARATOR
            . 'archives'
            . DIRECTORY_SEPARATOR;
    }
}