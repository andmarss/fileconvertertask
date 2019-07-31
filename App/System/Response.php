<?php


namespace App\System;

use App\System\File;
use App\System\Template\View;
use App\System\Workers\File as FileWorker;

/**
 * Class Response
 * @package App\System
 *
 * @property int $status
 * @property mixed $content
 */

class Response
{
    /**
     * Тело ответа
     *
     * @var mixed $content
     */
    public $content;
    /**
     * HTTP статус-код
     *
     * @var int $status
     */
    public $status;
    /**
     * HTTP статус-коды
     *
     * @var array $statuses
     */
    private $statuses = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded'
    ];
    /**
     * @var Header $header
     */
    protected $header;
    /**
     * @var string DISPOSITION_ATTACHMENT
     */
    const DISPOSITION_ATTACHMENT = 'attachment';
    /**
     * @var string DISPOSITION_INLINE
     */
    const DISPOSITION_INLINE = 'inline';

    public function __construct(?string $content = '', $status = 200, $headers = [])
    {
        $this->content = $content;
        $this->status = $status;

        $this->header = new Header($headers);
    }

    /**
     * @param string $name
     * @param string|null $value
     * @return Response
     */
    public function header(string $name, ?string $value): Response
    {
        $this->header->set($name, $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
       return (string) $this->content;
    }

    /**
     * @param array|null $data
     * @param int $status
     * @param array $headers
     * @return Response
     */
    public function json(?array $data, int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json';

        return new static(json_encode($data), $status, $headers);
    }

    /**
     * @param string $path
     * @param array $data
     * @return View
     */
    public function view(string $path, array $data = []): View
    {
        return view($path, $data);
    }

    /**
     * @return string
     */
    public function send(): string
    {
        return $this->sendHeaders()->getContent();
    }

    /**
     * @return Response
     */
    protected function sendHeaders(): Response
    {
        if(headers_sent()) return $this;

        foreach ($this->header->all() as $key => $values) {
            foreach ($values as $value) {
                header($key . ': ' . $value, true);
            }
        }

        header(sprintf('HTTP/%s %s %s', '1.1', $this->status, $this->statuses[$this->status]), true, $this->status);

        return $this;
    }

    /**
     * @param string $path
     * @param string|null $name
     * @param array $headers
     * @throws \Exception
     */
    public function download(string $path, ?string $name = null, array $headers = []): void
    {
        if(is_null($name)) $name = basename($path);

        $file = new FileWorker($path);

        /**
         * @var array $headers
         */
        $headers = array_merge([
            'Content-Description'       => 'File Transfer',
            'Content-Type'              => File::mime($file->extension()),
            'Content-Transfer-Encoding' => 'Binary',
            'Expires'                   => 0,
            'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma'                    => 'public',
            'Content-Length'            => $file->size(),
            'Content-Disposition'       => sprintf('%s; filename=%s', static::DISPOSITION_ATTACHMENT, $name)
        ], $headers);

        $response = new static(File::get(str_replace(File::root(), '', $file->path())), 200, $headers);

        $response->send();

        readfile($path);
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return (new static())->{$method}(...$arguments);
    }

    /**
     * @return string
     */
    public function __toString()
    {
       return $this->send();
    }
}