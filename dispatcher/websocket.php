<?php

/**
 * Class standard
 *
 * @property  swoole_websocket_server _webserver
 */
class Gend_Displatcher_Websocket extends Gend_Dispatcher_BaseInterface
{
	protected $_config = null;

	/**
	 * 当WebSocket客户端与服务器建立连接并完成握手后会回调此函数。
	 * @param swoole_websocket_server $svr
	 * @param swoole_http_request $req
	 */
	public function onOpen(swoole_websocket_server $svr, swoole_http_request $req)
	{
		//prepare eagle eye
		$traceid    = "";
		$rpcid      = "";
		if (isset($req->header["tal_trace_id"])) {
			$traceid = $req->header["tal_trace_id"];
		}

		if (isset($req->header["tal_rpc_id"])) {
			$rpcid = $req->header["tal_rpc_id"];
		}

		if (isset($req->header["tal_x_version"])) {
			$client_version = $req->header["tal_x_version"];
		}

		//eagle eye request start init
		Gend_EagleEye::requestStart($traceid, $rpcid);

		//prepare user info
		//$host = parse_url($req->header['host']);
		$user = array();

		$user['fd']             = $req->fd;
		$user['server_ip']      = Gend_EagleEye::getServerIp();
		$user['server_port']    = $req->server['server_port'];
		$user['user_ip']        = $req->server['remote_addr'];
		$user['user_port']      = $req->server['remote_port'];
		$user['tal_trace_id']   = isset($req->header["tal_trace_id"]) ? $req->header['tal_trace_id'] : Gend_EagleEye::getTraceId();
		$user['domain']         = parse_url($req->header["host"])['host'];
		$strurl                 = str_replace(array('.php', '.html', '.shtml'), '', $req->server['request_uri']);
		$arr                    = explode('/', $strurl);
		$module                 = !empty($arr[1]) ? $arr[1] : 'index';
		$action                 = !empty($arr[2]) ? $arr[2] : 'index';
		$user['uri']            = $module . "/" . $action;

        $redis                  = Gend_Cache::Factory(1);
        $redis->set("socket_fd_" . Gend_EagleEye::getServerIp() . "_" . $user['server_port'] . "_" . $req->fd, $user);

		$eagleEyeLog = array(
			"x_name"        => "websocket.server.connect",
			"x_client_ip"   => $user['user_ip'],
			"x_action"      => $user['domain'] . "/" . $user['uri'],
			"x_param"       => '',
		);
		Gend_EagleEye::baseLog($eagleEyeLog);
	}

	/**
	 * 当服务器收到来自客户端的数据帧时会回调此函数。
	 * @param swoole_server $server
	 * @param swoole_websocket_frame $frame
	 */
	public function onMessage(swoole_server $server, swoole_websocket_frame $frame)
	{
        Gend_Di::factory()->set('swoole_frame',$frame);
        Gend_Di::factory()->set('socket_server',$server);
		$port = $server->connection_info($frame->fd);
		$port = $port["server_port"];
		$user = Gend_Cache::factory(1)->get("socket_fd_" . Gend_EagleEye::getServerIp() . "_" . $port . "_" . $frame->fd);
		$data['event_name'] = "pull";
		$data['fd']     = $frame->fd;
		$data['finish'] = $frame->finish;
		$data['rev']   = $frame->data;
        Gend_Di::factory()->set('fduser',$user);
        Gend_Di::factory()->set('fdrev',$data);
		$decodeData = json_decode($frame->data, true);
		$traceId = "";
		$rpcId   = "";
		if (isset($decodeData["tal_trace_id"])) {
			$traceId = $decodeData["tal_trace_id"];
		}

		if (isset($decodeData["tal_rpc_id"])) {
			$rpcId = $decodeData["tal_rpc_id"];
		}

		//eagle eye request start init
		Gend_EagleEye::requestStart($traceId, $rpcId);
		//record this request
		Gend_EagleEye::setRequestLogInfo("client_ip", $user['user_ip']);
		Gend_EagleEye::setRequestLogInfo("action", $user['uri']);

		$eagleData          = $data;
        $eagleData["user"]  = $user;
		Gend_EagleEye::setRequestLogInfo("param", json_encode($eagleData));
        $msg['rev'] = $data;
		try {
            ob_start();//打开缓冲区
            Gend_Acl::Factory()->runSocket($user['uri'], $msg);
            $string = ob_get_contents();//获取缓冲区内容
            ob_end_clean();//清空并关闭缓冲区
            $server->push($frame->fd, $string);
		} catch (Exception $e) {
			Gend_EagleEye::setRequestLogInfo("msg", $e->getMessage());
			Gend_EagleEye::setRequestLogInfo("code", $e->getCode());
			$item = array('errcode' => $e->getCode(), 'errmsg' => $e->getMessage(), 'version' => '1.0',
						  'res' => array(), 'state' => -1);
            $server->push($frame->fd, json_encode($item));
		}
		Gend_EagleEye::requestFinished();

	}

	public function onClose(swoole_server $server, $fd, $reactorId)
	{

		//ignore http request process
		$info = $server->connection_info($fd);
		if ($info["websocket_status"] === 0) {
			return;
		}
		$redis      = Gend_Cache::factory(1);
		$serverIp   = Gend_EagleEye::getServerIp();

		$eagleEyeLog = array(
			"x_name" => "websocket.server.close",
		);

		$port = $server->connection_info($fd);
		$port = $port["server_port"];
		if ($port == 0) {
			return;
		}

		//get redis info
		$user = $redis->get("socket_fd_" . $serverIp . "_" . $port . "_" . $fd);
		if (isset($user["uri"]) && $user["uri"] != "") {
			//eagle eye request start init
			Gend_EagleEye::requestStart($user["tal_trace_id"]);

			$data['event_name'] = "close";
			$data['fd'] = $fd;

			$GLOBALS['_user'] = $user;
			$GLOBALS['_sdata'] = $data;

			//eagle eye log
			$eagleEyeLog["x_param"] = json_encode(
				array(
					"user" => $user,
					"param" => $data
				)
			);
			//invoke the api
			try {
				$msg['data'] = $data;
				Gend_Acl::Factory()->runSocket($user['uri'], $msg);
			} catch (Exception $e) {
				$eagleEyeLog["x_msg"] = $e->getMessage();
				$eagleEyeLog["x_code"] = $e->getCode();
			}

			//remove the fd record on redis
			$redis->del("socket_fd_" . $serverIp . "_" . $port . "_" . $fd);
		} else {
			Gend_EagleEye::requestStart();
			$eagleEyeLog["x_msg"] = "no fd=$fd info found on redis";
			$eagleEyeLog["x_code"] = "-1";

		}
		Gend_EagleEye::baseLog($eagleEyeLog);
	}

    /**
     * 处理 http_server 服务器的 request 请求
     * @param $request
     * @param $response
     * @tutorial 获得 REQUEST_URI 并解析，通过 Gend_Acl 路由到指定的 controller
     */
    public function onRequest($request, $response)
    {
        Gend_Func::Init('GendString','GendTimer','GendArray','GendHttp');
        Gend_Di::factory()->reSet('http_response',$response);
        Gend_Di::factory()->reSet('http_request',$request);
        $stattime               = GendTimer::getTime();
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
        $_domain        = explode('.',$host['host']);
        if(count($_domain)>=3){
            unset($_domain[0]);
        }
        $_domain    =   join('.',$_domain);
        $_configname=   'cfg_'.$_domain.'.php';
        if(file_exists(SYS_ROOTDIR.'conf/'.$_configname)){
            require_once(SYS_ROOTDIR.'conf/'.$_configname);
        }
        if(file_exists(SYS_ROOTDIR.'conf/db.php')){
            require_once(SYS_ROOTDIR.'conf/db.php');
        }

        $_SERVER['HTTP_HOST']   = $host['host'];
        $_SERVER["REMOTE_ADDR"] = !empty($request->server["remote_addr"])?$request->server["remote_addr"]:'';
        $response->header("Content-Type", "text/html; charset=utf-8;");
        $response->header("X-Powered-By", "php-server");
        if ($_SERVER['request_uri']) {
            $strurl = strtok($_SERVER['request_uri'], '?');
        } elseif (!empty($_SERVER['REQUEST_URI'])) {
            $strurl = strtok($_SERVER['REQUEST_URI'], '?');
        }
        $querystring = !empty($_SERVER['query_string'])?$_SERVER['query_string']:'';
        $strurl = str_replace(array('.php', '.html', '.shtml'), array('', '', ''), $strurl);
        $module = explode('/', $strurl);
        $ip     = GendHttp::getIp();
        $state  = 1;

        //prepare the traceid
        $traceid    = "";
        $rpcid      = "";
        $client_version = "";

        if (isset($request->header["tal_trace_id"])) {
            $traceid = $request->header["tal_trace_id"];
        }

        if (isset($request->header["tal_rpc_id"])) {
            $rpcid = $request->header["tal_rpc_id"];
        }

        if (isset($request->header["tal_x_version"])) {
            $client_version = $request->header["tal_x_version"];
        }

        //eagle eye request start init
        Gend_EagleEye::requestStart($traceid, $rpcid);
        $traceid = Gend_EagleEye::getTraceId();
        $rpcid = Gend_EagleEye::getReciveRpcId();

        //set response header contain trace id and rpc id
        $response->header["tal_trace_id"] = $traceid;
        $response->header["tal_rpc_id"] = $rpcid;

        //record this request
        Gend_EagleEye::setRequestLogInfo("client_ip", $ip);
        Gend_EagleEye::setRequestLogInfo("action", $_SERVER['HTTP_HOST']  . $strurl);
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