<?php

/**
 * Class standard
 *
 * @property  swoole_http_server _webserver
 */
class Gend_Displatcher_Http extends Gend_Dispatcher_BaseInterface
{
	protected $_config = null;

    /**
     * 处理 http_server 服务器的 request 请求
     * @param $request
     * @param $response
     * @tutorial 获得 REQUEST_URI 并解析，通过 Gend_Acl 路由到指定的 controller
     */
    public function onRequest($request, $response)
    {

        Gend_Di::factory()->reSet('http_response',$response);
        Gend_Di::factory()->reSet('http_request',$request);
        $GLOBALS['_GET']        = !empty($request->get) ? $request->get : array();
        $GLOBALS['_POST']       = !empty($request->post) ? $request->post : array();
        $GLOBALS['_COOKIE']     = !empty($request->cookie) ? $request->cookie : array();
        $GLOBALS['_HTTPDATA']   = !empty($request->data) ? $request->data : array();
        $GLOBALS['_FD']         = !empty($request->fd) ? $request->fd : '';
        $GLOBALS['_HEADER']     = !empty($request->header) ? $request->header : array();
        if (!empty($request->server)) {
            $_SERVER = array_merge($_SERVER, $request->server);
        }
        $host           = parse_url($request->header['host']);
        $_SERVER['HTTP_HOST']   = !empty($host['path'])?$host['path']:$host['host'];
        $_SERVER["REMOTE_ADDR"] = !empty($request->server["remote_addr"])?$request->server["remote_addr"]:'';
        $response->header("Content-Type", "text/html; charset=utf-8;");
        $response->header("X-Powered-By", "php-server");

        if (!isset($_SERVER['request_uri'])) {
            $response->end("请求非法");
            return;
        }

        $strurl = strtok($_SERVER['request_uri'], '?');
        $strurl = str_replace(array('.php', '.html', '.shtml'), '', $strurl);
        $module = explode('/', $strurl);

        //prepare the traceid
        $traceid    = "";
        $rpcid      = "";

        if (isset($request->header["tal_trace_id"])) {
            $traceid = $request->header["tal_trace_id"];
        }

        if (isset($request->header["tal_rpc_id"])) {
            $rpcid = $request->header["tal_rpc_id"];
        }

        //eagle eye request start init
        Gend_EagleEye::requestStart($traceid, $rpcid);
        $traceid = Gend_EagleEye::getTraceId();
        $rpcid = Gend_EagleEye::getReciveRpcId();

        //set response header contain trace id and rpc id
        $response->header["tal_trace_id"] = $traceid;
        $response->header["tal_rpc_id"] = $rpcid;

        //record this request
        Gend_EagleEye::setRequestLogInfo("client_ip", GendHttp::getIp());
        Gend_EagleEye::setRequestLogInfo("action", $_SERVER['HTTP_HOST']. $strurl);
        Gend_EagleEye::setRequestLogInfo("param", json_encode(array("post" => $_POST, "get" => $_GET)));
        Gend_EagleEye::setRequestLogInfo("source", isset($request->header["referer"])?$request->header["referer"]:'');
        Gend_EagleEye::setRequestLogInfo("user_agent", isset($request->header["user-agent"])?$request->header["user-agent"]:'');

        ob_start();//打开缓冲区
        $state = Gend_Acl::Factory()->run($module);
        $string = ob_get_contents();//获取缓冲区内容
        ob_end_clean();//清空并关闭缓冲区

        //eagle eye request record finished
        Gend_EagleEye::setRequestLogInfo("response", $string);
        Gend_EagleEye::setRequestLogInfo("response_length", strlen($string));
        Gend_EagleEye::requestFinished();

        //gzip
        if (!empty($request->header["Accept-Encoding"]) && stristr($request->header["Accept-Encoding"], "gzip")) {
            $response->gzip(4);
        }
        //send result
        $response->end($string);
    }

}