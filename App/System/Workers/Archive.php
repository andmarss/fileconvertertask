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

    public function __construct(string $path)
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

        $this->archive->extractTo($path);

        $this->archive->close();

        return $this;
    }
}