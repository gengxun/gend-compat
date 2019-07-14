<?php

/**
 * @author kevin
 */
class Gend_Controller extends Gend
{

    public $_tpl = '';
    public $reg  = '';

    /**
     * 获取原始HTTP请求体，并按照json格式解析返回
     * @param bool $assoc 是否返回数组格式
     * @return array|object
     * @throws Gend_Exception
     */
    protected function getJsonRawBody($assoc = true)
    {
        $jsonBody = file_get_contents('php://input');
        $json     = json_decode($jsonBody, $assoc);
        if ($json === null) {
            throw new Gend_Exception('json decode failed, ' . json_last_error_msg() . $jsonBody);
        }
        return $json;
    }

    /**
     * 获取x-www-form-urlencoded表单请求的data域数据，并按照json格式解析
     * @param type $assoc
     * @return type
     * @throws Gend_Exception
     */
    public function getJsonFormData($assoc = true)
    {
        return $this->getJsonFormField('data', $assoc);
    }

    /**
     * 获取x-www-form-urlencoded表单请求的$field域数据，并按照json格式解析
     * @param type $assoc
     * @return type
     * @throws Gend_Exception
     */
    public function getJsonFormField($field, $assoc = true)
    {
        $jsonBody = stripslashes(Gend_Func::doPost($field));
        if (empty($jsonBody)) {
            return null;
        }
        $json = json_decode($jsonBody, $assoc);
        if ($json === null) {
            throw new Gend_Exception('json decode failed, ' . json_last_error_msg() . $jsonBody);
        }
        return $json;
    }


    public function showMsg($res, $state = 0, $msg = '', $errno = 0, $jsontype = 0)
    {
        //定制错误信息
        if ($errno) {
            
        }
        $agent = Gend_Func::isMobile();
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
                $item['res']    = null;
            }
            // 是否需要送出get
            if (isset($_GET['isget']) && $_GET['isget'] == 1) {
                $item['get'] = !empty($_GET) ? $_GET : NULL;
            }
            if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                $item['get']      = !empty($_GET) ? $_GET : NULL;
                $item['post']     = !empty($_POST) ? $_POST : NULL;
                $item['cookie']   = !empty($_COOKIE) ? $_COOKIE : NULL;
                $item['fd']       = !empty($_FD) ? $_FD : NULL;
                $item['httpdata'] = !empty($_HTTPDATA) ? $_HTTPDATA : NULL;
                $item['header']   = !empty($_HEADER) ? $_HEADER : NULL;
                header("Content-type: text/html; charset=utf-8");
                echo "<pre>";
                print_r($item);
                if ($errno > 0) {
                    return;
                }
            } else {
                header("Content-type: application/json; charset=utf-8");
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
                $item['res']    = null;
            }
            if (!empty($this->cfg['runtype'])) {
                $item['runtype'] = $this->cfg['runtype'];
            }
            // 是否需要送出get
            if (isset($_GET['isget']) && $_GET['isget'] == 1) {
                $item['get'] = !empty($_GET) ? $_GET : NULL;
            }
            if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                $item['get']  = !empty($_GET) ? $_GET : NULL;
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
                    return;
                }
            }
        }
        Gend_Log::write($item);
    }

    /**
     * 重写模板代理
     * 和Gend::showView()一样的功能
     *
     * @param string $tplVar  模板标识
     * @return void
     * */
    protected function showView($tplVar)
    {
        $this->_tpl->display($tplVar . '.tpl');
    }

    /**
     * 注册变量到模板
     * 注意: 在函数体内的变量实现完全拷贝,会重复占用内存以及CPU资源
     * 建议使用refVar引用传递
     *
     * @param string  $strVar 变量指针
     * @param string  $tplVar 模板中的变量名称
     * @param integer $tp     是否引用注册
     * */
    protected function regVar($strVar, $tplVar = 'tmy')
    {
        $this->_tpl->assign($tplVar, $strVar);
    }

    /**
     * 
     * 注意: 想客户端发送消息
     * 建议使用refVar引用传递
     *
     * @param string  $strVar 变量指针
     * @param string  $tplVar 模板中的变量名称
     * @param integer $tp     是否引用注册
     * */
    protected function showPush($fd, $msg = 0)
    {
        if (!extension_loaded('swoole') || empty($this->_server)) {
            die('请安装swoole扩展');
        }
        $this->_server->push($fd, $msg);
        return;
    }

    /**
     * 直接送出结果
     *
     * @param  array $data 资源对象
     * @return array
     * */
    public function showData($data)
    {
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<pre>";
            print_r($data);
        } else {
            //编码
            $item = json_encode($data);
            // 是否为jsonp访问
            if (isset($_GET['callback']) && !empty($_GET['callback']) && preg_match('/^([a-zA-Z0-9_]+)$/i', $_GET['callback'])) {
                $item = "{$_GET['callback']}($item)";
            }
            echo "{$item}";
        }
    }

}
