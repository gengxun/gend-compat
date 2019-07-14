<?php

/**
 * 路由器 核心加载器
 * 模块需要通过该对象进行激活
 * @Author  kevin <kevin@100tal.com>
 * @version $Id: Core.php  $
 * */
!defined('FD_DS') && define('FD_DS', DIRECTORY_SEPARATOR);
!defined('FD_ROOT') && define('FD_ROOT', dirname(__FILE__) . FD_DS);
!defined('FD_SERVICES') && define('FD_SERVICES', dirname(FD_ROOT) . FD_DS . 'services' . FD_DS);
!defined('FD_PLUGIN') && define('FD_PLUGIN', dirname(FD_ROOT) . FD_DS . 'plugin' . FD_DS);
!defined('FD_LIBMODEL') && define('FD_LIBMODEL', dirname(FD_ROOT) . FD_DS . 'model' . FD_DS);
!defined('FD_AUTOLOAD') && define('FD_AUTOLOAD', 'Gend_AutoLoad');
!defined('FD_FETALERROR') && define('FD_FETALERROR', 'Gend_FetalError');

abstract class Gend
{

    public $version = '1.0';

    /**
     * 初始化模板
     */
    public function tplInit()
    {
        $this->_tpl = new smarty();
        if (!file_exists($this->cfg['sys_webphp'])) {
            mkdir($this->cfg['sys_webphp'], 0777, true);
        }
        if (!file_exists($this->cfg['sys_cache'] . 'smarty/')) {
            mkdir($this->cfg['sys_cache'] . 'smarty/', 0777, true);
        }
        if (!file_exists($this->cfg['sys_cache'] . 'smartyconf/')) {
            mkdir($this->cfg['sys_cache'] . 'smartyconf/', 0777, true);
        }
        $this->_tpl->setTemplateDir($this->cfg['sys_view']);
        $this->_tpl->setCompileDir($this->cfg['sys_webphp']);
        $this->_tpl->setCacheDir($this->cfg['sys_cache'] . 'smarty/');
        $this->_tpl->setConfigDir($this->cfg['sys_cache'] . 'smartyconf/');
    }

    /**
     * 魔法方法: 动态载入全局变量 当变量不存在时试图创建之
     *
     * @param  string $k 变量名称
     * @return variable  变量内容
     * */
    public function &__get($k)
    {
        !isset($GLOBALS['_' . $k]) && self::__set($k);
        return $GLOBALS['_' . $k];
    }

    /**
     * 魔法方法: 动态创建全局变量 被成功创建的变量保存在GLOBALS中
     *
     * Example : $this->var1=123 对象中var1不存在时自动创建到$GLOBALS['_var1']中
     * @param string $k 变量名称
     * @param string $v 变量值
     * */
    public function __set($k, $v = null)
    {
        if (!isset($GLOBALS['_' . $k])) {//初始化系统变量
            if (isset($this->FD_REG_FUNC[$k])) {
                $v = $this->FD_REG_FUNC[$k]();
            } else {
                $GLOBALS['_' . $k] = &$v;
            }
        }
        $GLOBALS['_' . $k] = $v; //设置临时变量
    }

    /**
     * 魔法方法: 检测被动态创建的变量也可以是全局变量GLOBALS
     *
     * Example : isset($this->var1) = isset($GLOBALS['_var1'])
     * @param string $k 变量名称
     * @return string
     * */
    public function __isset($k)
    {
        return isset($GLOBALS['_' . $k]);
    }

    /**
     * 魔法方法: 释放变量资源
     *
     * Example : unset($this->var1) = unset($GLOBALS['_var1'])
     * @param string $k 变量名称
     * */
    public function __unset($k)
    {
        unset($GLOBALS['_' . $k]);
    }

    /**
     * @param $fd       push数据的对象
     * @param $string   push的数据内容
     */
    public function pushMsg($fd, $string)
    {
        if (!empty($this->_socketserver)) {
            ob_start(); //打开缓冲区
            if (is_array($string) || is_object($string) || is_resource($string)) {
                print_r($string);
            } else {
                echo $string;
            }
            $string = ob_get_contents(); //获取缓冲区内容
            ob_end_clean(); //清空并关闭缓冲区
            $this->_socketserver->push($fd, $string);
        } else {
            //die("socket mesg error");
        }
    }

    public function showMsg($res, $state = 0, $msg = '', $errno = 0, $jsontype = 0)
    {
        //定制错误信息
        if ($errno) {

        }
        //$agent = Gend_Func::isMobile();
        // 测试一些post提交
        if (isset($_GET['gp']) && $_GET['gp'] == 1) {
            $_POST = $_GET;
        }
        if (!empty($jsontype) || (empty($uuid) && empty($agent))) {
            $item = array('errcode' => (int) $errno, 'errmsg' => $msg, 'version' => '1.0', 'res' => null, 'state' => (int) $state);
            if (is_array($res) && !empty($res)) {
                $item['res'] = $res;
                if (!empty($msg)) {
                    $item['errmsg'] = $msg;
                } else {
                    $item['errmsg'] = '操作成功';
                }
            } elseif (is_string($res)) {
                $item['errmsg'] = ($state == -10000) ? '' : $res;
                $item['res'] = null;
            }
            // 是否需要送出get
            if (isset($_GET['isget']) && $_GET['isget'] == 1) {
                $item['get'] = !empty($_GET) ? $_GET : NULL;
            }
            if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                $item['get'] = !empty($_GET) ? $_GET : NULL;
                $item['post'] = !empty($_POST) ? $_POST : NULL;
                $item['cookie'] = !empty($_COOKIE) ? $_COOKIE : NULL;
                $item['fd'] = !empty($_FD) ? $_FD : NULL;
                $item['httpdata'] = !empty($_HTTPDATA) ? $_HTTPDATA : NULL;
                $item['header'] = !empty($_HEADER) ? $_HEADER : NULL;
                header("Content-type: text/html; charset=utf-8");
                echo "<pre>";
                print_r($item);
                if ($errno > 0) {
                    return;
                }
            } else {
                echo json_encode($item, JSON_UNESCAPED_UNICODE);
            }
            return;
        } else {
            // 构造数据
            $item = array('errcode' => (int) $errno, 'errmsg' => $msg, 'version' => '1.0', 'res' => null, 'state' => (int) $state);
            if (is_array($res) && !empty($res)) {
                $item['res'] = $res;
            } elseif (is_string($res)) {

                $item['errmsg'] = ($state == -10000) ? '' : $res;
                $item['res'] = null;
            }
            if (!empty($this->cfg['runtype'])) {
                $item['runtype'] = $this->cfg['runtype'];
            }
            // 是否需要送出get
            if (isset($_GET['isget']) && $_GET['isget'] == 1) {
                $item['get'] = !empty($_GET) ? $_GET : NULL;
            }
            if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                $item['get'] = !empty($_GET) ? $_GET : NULL;
                $item['post'] = !empty($_POST) ? $_POST : NULL;
                echo "<pre>";
                print_r($item);
                if ($errno > 0)
                    return;
            }else {
                //编码
                $item = json_encode($item, JSON_UNESCAPED_UNICODE);
                if ($errno > 0) {
                    die("{$item}");
                } else {
                    echo "{$item}";
                }
            }
        }
    }

    /**
     * 输出json格式文件
     * @param $arr
     * @return string
     */
    public function showJson($arr)
    {
        if (!empty($arr)) {
            $str = Gend_Func::getJson($arr);
        }
        echo $str;
    }

    /**
     * 接口统一返回数据格式化
     * @param int status 状态码
     * @param array data 响应数据
     */
    public function formatData($status = 100, $data = '')
    {
        header("Set-Cookie: hidden=value; httpOnly");
        header("Content-type:text/json");
        if (empty($data) && $status != 100) {
            $conf = Services_Common_Errorcode::$errorcode[$status];
            $data = $conf['error_msg'];
        }

        echo json_encode(array('status' => $status, 'data' => $data), JSON_UNESCAPED_UNICODE);
        $this->doExit();
    }

    /**
     * 自定义exit方法
     *  php-fpm 使用exit退出；swoole使用throw抛出异常退出。
     * @param int $exitCode 退出码
     * @param string $msg  字符串
     * @throws Gend_Exception
     */
    public function doExit($exitCode = 0, $msg = '')
    {
        $env = Gend_Di::factory()->get('env');
        if ($env == 'swoole') {
            throw new Gend_Exception($msg);
        } else {
            exit($exitCode);
        }
    }

}

/**
 * 魔法函数: 自动加载对象文件
 * */
function Gend_AutoLoad($class)
{

    if (file_exists(FD_PLUGIN . str_replace('_', FD_DS, $class) . '.php')) {
        $prefix = strtok($class, '_');
        $file = FD_PLUGIN . str_replace('_', FD_DS, $class);
    } else {
        $class = strtolower($class);
        $mods = explode('_', $class);
        $module = strtolower(array_pop($mods));
        $prefix = strtok($class, '_');
        if ($prefix == 'Gend') {
            $file = FD_ROOT . str_replace('_', FD_DS, substr($class, 5));
        } elseif ($prefix == 'smarty') {
            $file = FD_PLUGIN . 'smarty' . FD_DS . 'smarty';
        } elseif (in_array($module, array('read', 'write'))) {
            $file = FD_LIBMODEL . str_replace('_', FD_DS, $class);
        } elseif ($prefix == 'model') {
            $file = FD_LIBMODEL . str_replace(array('_', 'model'), array(FD_DS, ''), $class);
        } elseif ($prefix == 'conf') {
            $file = FD_CONF . str_replace(array('_', 'conf'), array(FD_DS, ''), $class);
        } elseif ($prefix == 'services') {
            $file = FD_SERVICES . str_replace(array('_', 'services'), array(FD_DS, ''), $class);
        } elseif ($prefix == 'plugin') {
            $file = FD_PLUGIN . str_replace(array('_', 'plugin'), array(FD_DS, ''), $class);
        }
    }
    if (!is_file($file . '.php')) {//捕捉异常
        $swoole = Gend_Di::factory()->get('http_response');
        if (!empty($swoole)) {
            ob_start(); //打开缓冲区
            Gend_Exception::Factory("$file'.php':Has Not Found Class $class")->ShowTry(1);
            $content = ob_get_contents(); //获取缓冲区内容
            $swoole->header("Content-Length", strlen($content));
            ob_end_clean(); //清空并关闭缓冲区
            $swoole->end($content);
        }
        return false;
    } else {
        include_once($file . '.php');
        return true;
    }
}

/**
 * 致命异常处理
 * */
function Gend_FetalError()
{
    $swoole = Gend_Di::factory()->get('http_response');
    $error = error_get_last();
    if (isset($error['type'])) {
        switch ($error['type']) {
            case E_ERROR :
            case E_PARSE :
            case E_CORE_ERROR :
            case E_COMPILE_ERROR :
                $message = $error['message'];
                $file = $error['file'];
                $line = $error['line'];
                $log = "$message ($file:$line)\nStack trace:\n";
                $trace = debug_backtrace();
                foreach ($trace as $i => $t) {
                    if (!isset($t['file'])) {
                        $t['file'] = 'unknown';
                    }
                    if (!isset($t['line'])) {
                        $t['line'] = 0;
                    }
                    if (!isset($t['function'])) {
                        $t['function'] = 'unknown';
                    }
                    $log .= "#$i {$t['file']}({$t['line']}): ";
                    if (isset($t['object']) && is_object($t['object'])) {
                        $log .= get_class($t['object']) . '->';
                    }
                    $log .= "{$t['function']}()\n";
                }
                if (isset($_SERVER['request_uri'])) {
                    $log .= '[QUERY] ' . $_SERVER['request_uri'];
                }
                if (!empty($swoole)) {
                    ob_start(); //打开缓冲区
                    echo $log;
                    $string = ob_get_contents(); //获取缓冲区内容
                    ob_end_clean(); //清空并关闭缓冲区
                    $swoole->header("Content-Length", strlen($string));
                    $swoole->end($string);
                    return;
                } else {
                    Gend_Guolog::factory('Gend')->error($log, $file, $line);
                }
                break;
            default:
                $message = $error['message'];
                $file = $error['file'];
                $line = $error['line'];
                $log = "$message ($file:$line)\nStack trace:\n";
                $trace = debug_backtrace();
                foreach ($trace as $i => $t) {
                    if (!isset($t['file'])) {
                        $t['file'] = 'unknown';
                    }
                    if (!isset($t['line'])) {
                        $t['line'] = 0;
                    }
                    if (!isset($t['function'])) {
                        $t['function'] = 'unknown';
                    }
                    $log .= "#$i {$t['file']}({$t['line']}): ";
                    if (isset($t['object']) && is_object($t['object'])) {
                        $log .= get_class($t['object']) . '->';
                    }
                    $log .= "{$t['function']}()\n";
                }
                if (isset($_SERVER['request_uri'])) {
                    $log .= '[QUERY] ' . $_SERVER['request_uri'];
                }
                if (!empty($swoole)) {
                    ob_start(); //打开缓冲区
                    echo $log;
                    $string = ob_get_contents(); //获取缓冲区内容
                    ob_end_clean(); //清空并关闭缓冲区
                    $swoole->header("Content-Length", strlen($string));
                    $swoole->end($string);
                    return;
                } else {
                    if (E_NOTICE == $error['type']) {
                        Gend_Guolog::factory('Gend')->notice($log, $file, $line);
                    } else {
                        Gend_Guolog::factory('Gend')->warning($log, $file, $line);
                    }
                }
                break;
        }

        Gend_Guolog::factory('Gend')->addData(['req' => $_REQUEST]);
    }
}

spl_autoload_register(FD_AUTOLOAD);
register_shutdown_function(FD_FETALERROR);
