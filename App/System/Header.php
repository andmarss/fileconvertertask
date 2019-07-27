<?php


namespace App\System;


class Header
{
    protected $headers;
    protected $cacheControl;

    public function __construct(array $headers = [])
    {
        $this->cacheControl = [];
        $this->headers = [];

        foreach ($headers as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Получить значение заголовка по имени
     *
     * @param string $key
     * @param null $default
     * @param bool $first
     * @return array|mixed|null
     */
    public function get(string $key, $default = null, bool $first = true)
    {
        $key = str_replace('_', '-', strtolower($key));

        if (!array_key_exists($key, $this->headers)) {
            if (null === $default) {
                return $first ? null : array();
            }

            return $first ? $default : array($default);
        }

        if ($first) {
            return count($this->headers[$key]) ? $this->headers[$key][0] : $default;
        }

        return $this->headers[$key];
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
       return array_key_exists(str_replace('_', '-', strtolower($key)), $this->headers);
    }

    /**
     * Поставить значение заголовка по имени
     *
     * @param string $key
     * @param $values
     * @param bool $replace
     */
    public function set(string $key, $values, bool $replace = true)
    {
        $key = str_replace('_', '-', strtolower($key));

        $values = array_values((array) $values);

        if ($replace || !isset($this->headers[$key])) {
            $this->headers[$key] = $values;
        } else {
            $this->headers[$key] = array_merge($this->headers[$key], $values);
        }

        if ('cache-control' === $key) {
            $this->cacheControl = $this->parseCacheControl($values[0]);
        }
    }

    /**
     * @param string $header значение Cache-Control HTTP заголовка
     * @return array
     */
    protected function parseCacheControl(string $header)
    {
        $cacheControl = [];
        preg_match_all('#([a-zA-Z][a-zA-Z_-]*)\s*(?:=(?:"([^"]*)"|([^ \t",;]*)))?#', $header, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $cacheControl[strtolower($match[1])] = isset($match[3]) ? $match[3] : (isset($match[2]) ? $match[2] : true);
        }

        return $cacheControl;
    }

    /**
     * @return string
     */
    protected function getCacheControlHeader(): string
    {
        $parts = [];

        ksort($this->cacheControl);

        foreach ($this->cacheControl as $key => $value) {
            if (true === $value) {
                $parts[] = $key;
            } else {
                if (preg_match('#[^a-zA-Z0-9._-]#', $value)) {
                    $value = '"'.$value.'"';
                }

                $parts[] = "$key=$value";
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Добавить новые заголовки
     *
     * @param array $headers
     */
    public function add(array $headers)
    {
       foreach ($headers as $key => $value) {
           $this->set($key, $value);
       }
    }

    /**
     * @return array
     */
    public function all(): array
    {
       return $this->headers;
    }

    public function __toString()
    {
       if(!$this->headers) return '';
        /**
         * @var int $max
         */
        $max = max(array_map('strlen', array_keys($this->headers))) + 1;
        /**
         * @var string $content
         */
        $content = '';
        ksort($this->headers);

        foreach ($this->headers as $name => $values) {
            /**
             * @var string $name
             */
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $name.':', $value);
            }
        }

        return $content;
    }
}