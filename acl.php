<?php

class Gend_Acl extends Gend
{

    private $_module = array(); //被注册的模块
    private $_routeobj = null; //路由器对象
    private $_route = array(); //被路由的模块
    private static $in; //内置全局状态变量

    /**
     * 工厂模式: 激活并返回对象
     *
     * @return object
     * */

    public static function Factory()
    {
        if (!isset(self::$in)) {
            self::$in = new self();
        }
        return self::$in;
    }

    /**
     * 获取URL
     *
     * @param int $tp 获取集合还是一个
     * */
    public function getParam($url = array())
    {
        $uri = '';
        if (!empty($_SERVER['request_uri'])) {
            $uri = str_replace('.html', '', $_SERVER['request_uri']);
        } elseif (!empty($_SERVER['REQUEST_URI'])) {
            $uri = str_replace('.html', '', $_SERVER['REQUEST_URI']);
        }
        $app = strtok($uri, '?');
        $app = explode('/', $app);
        $app = GendArray::getFilterArray($app);
        $this->url = $app;
    }

    public function run($app = array(), $moddir = '')
    {
        $swoole = Gend_Di::factory()->get('http_response');
        $router = Gend_Di::factory()->get('router');

        if (!empty($swoole->server)) {
            $_SERVER = array_merge($_SERVER, $swoole->server);
        }

        self::getParam();
        $dmethod = '';
        $baseurl = defined('SYS_CONTROLLER') ? SYS_CONTROLLER : '';
        $hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $app = !empty($app) ? $app : $this->url;
        $app = GendArray::getFilterArray($app);
        if (isset($router[$hostname])) {
            $baseurl .= $router[$hostname];
        }
        if (!empty($moddir)) {
            $baseurl .= $moddir . '/';
        }
        $baseurl = str_replace('//', '/', $baseurl);
        if (empty($app)) {
            $dmethod = 'index';
            $pathfile = strtolower($baseurl) . '/index.php';
            if (is_file($pathfile)) {
                require_once($pathfile);
            }
        } else {
            $app = array_values($app);
            $pathtotal = count($app);
            $pathfile = $baseurl . join('/', $app) . '.php';
            $pathfile = strtolower($pathfile);
            $isfile = 0;
            foreach ($app as $key => $path) {
                if (is_file($pathfile)) {
                    $isfile = 1;
                    require_once($pathfile);
                    $dmethod = !empty($dmethod) ? $dmethod : 'index';
                    break;
                } else {
                    $pathfile = strtolower(dirname($pathfile)) . ".php";
                    $dmethod = $app[$pathtotal - ($key + 1)];
                }
            }
            $pathfile = ($isfile == 0) ? $baseurl . join('/', $app) . '.php' : $pathfile; //用来处理debug信息的规范性
        }

        $controller = str_ireplace(SYS_CONTROLLER, '', str_replace('.php', '', $pathfile)); //找到要拼接控制器的字符
        $controller = ucwords(str_replace("/", " ", $controller)); //每级 首字母大写
        $controller = "Controller" . str_replace(" ", "", $controller); //拼接控制器类名
        if (empty($controller)) {
            Gend_Di::factory()->set("debug_error", Gend_Exception::Factory('not fount file:' . $pathfile)->ShowTry(1, 1));
            if (defined('DEBUG') && DEBUG == 1) {
                Gend_Debug::Factory()->dump();
            }
            return false;
        }else if (!@class_exists($controller, false)) {
            Gend_Di::factory()->set("debug_error",
                Gend_Exception::Factory("class:" . $controller . ' not find')->ShowTry(1, 1));
            if (defined('DEBUG') && DEBUG == 1) {
                Gend_Debug::Factory()->dump();
            }
            return false;
        } else {
            $obj = new $controller();
            $methods = get_class_methods($obj);
            foreach ($methods as &$v) {
                $v = strtolower($v);
            }
            $dmethod = strtolower($dmethod);
            if (in_array('init', $methods)) {

                call_user_func_array(array($obj, 'Init'), array());
            }
            if (!empty($dmethod) && in_array($dmethod, $methods)) {
                call_user_func_array(array($obj, $dmethod), array());
            } else {
                Gend_Di::factory()->set("debug_error",
                    Gend_Exception::Factory("file:" . $pathfile . ' not find method:' . $dmethod)->ShowTry(1, 1));
                if (defined('DEBUG') && DEBUG == 1) {
                    Gend_Debug::Factory()->dump();
                }
                return false;
            }
        }
        if (defined('DEBUG') && DEBUG == 1) {
            Gend_Debug::Factory()->dump();
        }
    }

    /**
     * 内部调用
     */
    public function runController($url, $data)
    {
        $baseurl = SYS_CONTROLLER;
        if (strpos($url, '/') === 0) {
            $url = substr($url, 1);
        }
        $app = explode('/', $url);
        if (count($app) >= 3) {
            if (!empty($app[0])) {
                $baseurl .= $app[0] . '/';
            }
            if (!empty($app[1])) {
                $file = $app[1];
            } else {
                $file = 'index';
            }
            $dmethod = !empty($app[2]) ? $app[2] : 'index';
        } else {
            if (!empty($app[0])) {
                $file = $app[0];
            } else {
                $file = 'index';
            }
            $dmethod = !empty($app[1]) ? $app[1] : 'index';
        }
        $filename = strtolower($baseurl . $file . '.php');
        if (!empty($app) && file_exists($filename)) {
            require_once($filename);
            $controller = 'Controller' . ucfirst($file);
            $obj = new $controller();
        } else {
            return 404;
        }
        $methods = get_class_methods($obj);
        foreach ($methods as &$v) {
            $v = strtolower($v);
        }
        $dmethod = strtolower($dmethod);
        if (in_array('init', $methods)) {
            return call_user_func_array(array($obj, 'Init'), is_array($data) ? $data : array($data));
        }
        if (!empty($dmethod) && in_array($dmethod, $methods)) {
            return call_user_func_array(array($obj, $dmethod), is_array($data) ? $data : array($data));
        } elseif (in_array('index', $methods)) {
            return call_user_func_array(array($obj, 'index'), is_array($data) ? $data : array($data));
        } else {
            return 404;
        }
    }

    /**
     * socket内部调用入口文件
     * @param string  $url
     * @param $data
     *
     * @throws Exception
     */
    public function runSocket($url, $data)
    {
        $baseurl = SYS_SOCKET;
        if (strpos($url, '/') === 0) {
            $url = substr($url, 1);
        }
        Gend_Func::load('GendArray');
        $app = explode('/', $url);
        $app = GendArray::getFilterArray($app);
        if (count($app) >= 3) {
            if (!empty($app[0])) {
                $baseurl .= $app[0] . '/';
            }
            $file = $app[1];
            $dmethod = $app[2];
        } else if (count($app) == 2) {
            $file = $app[0];
            $dmethod = $app[1];
        } else {
            $dmethod = 'index';
            $file = !empty($app[0]) ? $app[0] : 'index';
        }
        $classname = 'Socket' . ucfirst($file);
        $filename = strtolower($baseurl . $file . '.php');
        if (!file_exists($filename)) {
            throw new Exception($filename . ' 文件不存在', 404);
        }
        require_once($filename);
        if (!class_exists($classname, false)) {
            throw new Exception("文件:{$filename} 中的类{$classname}不存在", 404);
        }
        $obj = new $classname();
        $methods = get_class_methods($obj);
        foreach ($methods as &$v) {
            $v = strtolower($v);
        }
        $dmethod = strtolower($dmethod);
        if (in_array('init', $methods)) {
            return call_user_func_array(array($obj, 'Init'), is_array($data) ? $data : array($data));
        }
        if (!empty($dmethod) && in_array($dmethod, $methods)) {
            return call_user_func_array(array($obj, $dmethod), is_array($data) ? $data : array($data));
        } elseif (in_array('index', $methods)) {
            return call_user_func_array(array($obj, 'index'), is_array($data) ? $data : array($data));
        } else {
            throw new Exception("socket model not found", 404);
        }
    }

    /**
     * 执行异步路由
     * @param  string $uri       请求路由地址
     * @param  string $moddir      路由起始地址
     * @param  array $data        传递数据
     * @return mixed
     */
    public function runTask($uri, $moddir = '', $data = array())
    {
        //确定路由目录
        if (!empty($moddir)) {
            $baseurl = $moddir;
            $first = '';
        } else {
            $baseurl = SYS_CONTROLLER;
            $first = 'Controller';
        }
        if (strpos($uri, '/') === 0) {
            $url = substr($uri, 1);
        }
        $app = explode('/', $uri);
        if (count($app) >= 3) {
            if (!empty($app[0])) {
                $baseurl .= $app[0] . '/';
            }
            if (!empty($app[1])) {
                $file = $app[1];
            } else {
                $file = 'index';
            }
            $dmethod = !empty($app[2]) ? $app[2] : 'index';
        } else {
            if (!empty($app[0])) {
                $file = $app[0];
            } else {
                $file = 'index';
            }
            $dmethod = !empty($app[1]) ? $app[1] : 'index';
        }

        $filename = strtolower($baseurl . $file . '.php');
        if (!empty($app) && file_exists($filename)) {
            require_once($filename);
            $controller = $first . ucfirst($file);
            $obj = new $controller();
        } else {
            return 404;
        }
        $methods = get_class_methods($obj);
        foreach ($methods as &$v) {
            $v = strtolower($v);
        }
        $dmethod = strtolower($dmethod);

        if (!empty($dmethod) && in_array($dmethod, $methods)) {
            return call_user_func_array(array($obj, $dmethod), array($data));
        } elseif (in_array('index', $methods)) {
            return call_user_func_array(array($obj, 'index'), array($data));
        } else {
            return 404;
        }
    }

}
