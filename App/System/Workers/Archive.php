<?php


namespace App\System\Workers;

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

        for ($i = 0; $i < $this->archive->numFiles; $i++) {
            $filename = $this->archive->getNameIndex($i);

            if($this->isUtf8($filename)) {
                $name = $this->reconvertUtf8(
                    $filename
                );

                $this->archive->renameName($filename, $name);

                $this->archive->extractTo($path, $name);
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
}