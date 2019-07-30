<?php


namespace App\System\Workers;

use App\System\Collection;

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
     * @param string $mask
     * @param bool $searchInnerDirectories
     * @return Collection
     * @throws \Exception
     */
    public function files($mask = '*', bool $searchInnerDirectories = true): Collection
    {
        /**
         * @var array $result
         */
        $result = [];
        /**
         * @var array $directories
         */
        $directories = $this->directories();

        if(is_array($mask)) {

            $mask = '{' . implode(',', $mask) . '}';

            /**
             * @var array $files
             */
            $files = glob($this->dirInfo->getRealPath() . DIRECTORY_SEPARATOR. $mask, GLOB_BRACE);

            if(count($files) > 0) {

                foreach ($files as $file) {
                    if(!is_file($file)) continue;
                    /**
                     * @var File $file
                     */
                    $file = new File($file);

                    $result[] = $file;
                }

            }

        } elseif (is_string($mask)) {
            if(preg_match('/^\{|\}$/', $mask)) {
                /**
                 * @var array $files
                 */
                $files = glob($this->dirInfo->getRealPath() . DIRECTORY_SEPARATOR . $mask, GLOB_BRACE);
            } else {
                /**
                 * @var array $files
                 */
                $files = glob($this->dirInfo->getRealPath() . DIRECTORY_SEPARATOR . $mask);
            }

            if(count($files) > 0) {

                foreach ($files as $file) {
                    if(!is_file($file)) continue;
                    /**
                     * @var File $file
                     */
                    $file = new File($file);

                    $result[] = $file;
                }

            }
        }

        if($searchInnerDirectories) {
            foreach ($directories as $directory) {
                $innerFiles = $directory->files($mask, false)->all();

                if(count($innerFiles) > 0) {
                    $result = array_merge($result, $innerFiles);
                }
            }
        }
        /**
         * @var Collection $files
         */
        $this->files = collect($result);

        return $this->files;
    }
    /**
     * @param \Closure $closure
     * @return Directory
     */
    public function each(\Closure $closure): Directory
    {
       if($this->files){
           foreach ($this->files as $index => &$file) {
               $closure($file, $file->directory(), $index);
           }
       }

       return $this;
    }

    /**
     * @param bool $preserve
     * @return Directory
     */
    public function delete(bool $preserve = false): Directory
    {
        if($this->isDir()) {

            foreach ($this->scan() as $item) {
                if($item->isDir()) {
                    (new Directory($item->getRealPath()))->delete();
                } else {
                    @unlink($item->getRealPath());
                }
            }

            if(!$preserve) @rmdir($this->dirInfo->getRealPath());
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isDir(): bool
    {
       return is_dir($this->dirInfo->getRealPath());
    }

    /**
     * @param string $path
     * @return Directory
     */
    public static function open(string $path): Directory
    {
        return new static($path);
    }

    /**
     * @return string
     */
    public function path(): string
    {
       return $this->dirInfo->getRealPath() . DIRECTORY_SEPARATOR;
    }

    /**
     * @return int
     */
    public function count(): int
    {
       return count($this->files);
    }

    public function all(): array
    {
       return $this->files;
    }
    /**
     * @return array
     */
    public function directories(): array
    {
        /**
         * @var array $result
         */
        $result = [];
        /**
         * @var array $directories
         */
        $directories = glob($this->dirInfo->getRealPath() . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

        if($directories) {
            foreach ($directories as $directory) {
                /**
                 * @var Directory $directory
                 */
                $directory = new Directory($directory);
                /**
                 * @var array $innerDirectories
                 */
                $innerDirectories = $directory->directories();

                if($innerDirectories) {
                    $result[] = $directory;

                    $result = array_merge($result, $innerDirectories);
                } else {
                    $result[] = $directory;
                }

                continue;
            }
        }

        return $result;
    }

    /**
     * @return \FilesystemIterator
     */
    public function scan(): \FilesystemIterator
    {
        return (new \FilesystemIterator($this->dirInfo->getPath()));
    }
}