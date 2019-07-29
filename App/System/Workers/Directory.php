<?php


namespace App\System\Workers;

class Directory
{
    /**
     * @var \SplFileInfo $dirInfo
     */
    protected $dirInfo;
    /**
     * @var array $files
     */
    protected $files = [];

    public function __construct(string $path)
    {
        /**
         * @var string $path
         */
        $path = preg_replace('/\/+/', DIRECTORY_SEPARATOR, $path);

        if(!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $this->dirInfo = new \DirectoryIterator($path);
    }

    /**
     * @param string|null $mask
     * @return Directory
     * @throws \Exception
     */
    public function files(?string $mask = '*'): Directory
    {
        /**
         * @var array $files
         */
        $files = glob($this->dirInfo->getRealPath() . DIRECTORY_SEPARATOR . $mask);
        /**
         * @var array $result
         */
        $result = [];

        foreach ($files as $file) {
            if(!is_file($file)) continue;
            /**
             * @var File $file
             */
            $file = new File($file);

            $result[] = $file;
        }

        $this->files = $result;

        return $this;
    }
    /**
     * @param \Closure $closure
     * @return Directory
     */
    public function each(\Closure $closure): Directory
    {
       if($this->files){
           foreach ($this->files as $index => $file) {
               $closure($file, $index);
           }
       }

       return $this;
    }

    /**
     * @return Directory
     */
    public function delete(): Directory
    {
        if(is_dir($this->dirInfo->getRealPath())) {
            @rmdir($this->dirInfo->getRealPath());
        }

        return $this;
    }

    public function create()
    {

    }
}