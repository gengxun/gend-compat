<?php
namespace Swoole\Http;

/**
 * @since 2.0.10
 */
class Response
{

    public $fd;
    public $header;
    public $cookie;
    public $trailer;

    /**
     * @return mixed
     */
    public function initHeader(){}

    /**
     * @param $name[required]
     * @param $value[optional]
     * @param $expires[optional]
     * @param $path[optional]
     * @param $domain[optional]
     * @param $secure[optional]
     * @param $httponly[optional]
     * @return mixed
     */
    public function cookie($name, $value=null, $expires=null, $path=null, $domain=null, $secure=null, $httponly=null){}

    /**
     * @param $name[required]
     * @param $value[optional]
     * @param $expires[optional]
     * @param $path[optional]
     * @param $domain[optional]
     * @param $secure[optional]
     * @param $httponly[optional]
     * @return mixed
     */
    public function rawcookie($name, $value=null, $expires=null, $path=null, $domain=null, $secure=null, $httponly=null){}

    /**
     * @param $http_code[required]
     * @return mixed
     */
    public function status($http_code){}

    /**
     * @param $compress_level[optional]
     * @return mixed
     */
    public function gzip($compress_level=null){}

    /**
     * @param $key[required]
     * @param $value[required]
     * @param $ucwords[optional]
     * @return mixed
     */
    public function header($key, $value, $ucwords=null){}

    /**
     * @param $content[required]
     * @return mixed
     */
    public function write($content){}

    /**
     * @param $content[optional]
     * @return mixed
     */
    public function end($content=null){}

    /**
     * @param $filename[required]
     * @param $offset[optional]
     * @param $length[optional]
     * @return mixed
     */
    public function sendfile($filename, $offset=null, $length=null){}

    /**
     * @return mixed
     */
    public function __sleep(){}

    /**
     * @return mixed
     */
    public function __wakeup(){}

    /**
     * @return mixed
     */
    public function __destruct(){}


}
