<?php
namespace Swoole\Coroutine\Http;

/**
 * @since 2.0.10
 */
class Client
{

    public $type;
    public $errCode;
    public $statusCode;
    public $host;
    public $port;
    public $requestMethod;
    public $requestHeaders;
    public $requestBody;
    public $uploadFiles;
    public $headers;
    public $cookies;
    public $body;

    /**
     * @return mixed
     */
    public function __construct(){}

    /**
     * @return mixed
     */
    public function __destruct(){}

    /**
     * @return mixed
     */
    public function set(){}

    /**
     * @return mixed
     */
    public function setMethod(){}

    /**
     * @return mixed
     */
    public function setHeaders(){}

    /**
     * @return mixed
     */
    public function setCookies(){}

    /**
     * @return mixed
     */
    public function setData(){}

    /**
     * @return mixed
     */
    public function execute(){}

    /**
     * @return mixed
     */
    public function get(){}

    /**
     * @return mixed
     */
    public function post(){}

    /**
     * @return mixed
     */
    public function addFile(){}

    /**
     * @return mixed
     */
    public function isConnected(){}

    /**
     * @return mixed
     */
    public function close(){}

    /**
     * @return mixed
     */
    public function setDefer(){}

    /**
     * @return mixed
     */
    public function getDefer(){}

    /**
     * @return mixed
     */
    public function recv(){}

    /**
     * @return mixed
     */
    public function __sleep(){}

    /**
     * @return mixed
     */
    public function __wakeup(){}


}
