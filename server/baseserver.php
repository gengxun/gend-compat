<?php
/**
 * 服务端基础服务类
 * 根据配置启动服务端口，不同端口不同dispatcher处理请求
 * Class Gend_Server_BaseServer
 *
 * @property swoole_websocket_server _server
 * @property LiveRoom _mainDispatcher
 * @property LiveRoom _subDispatcher
 * @property Gend_MemoryTable _monitorTable
 * TODO $this->_server 是一个 GLOBAL 变量
 */

class Gend_Server_BaseServer extends Gend
{
	private $_config;

	private $_subserver = array();

	private $_mainDispatcher = null;

	private $_subDispatcher = array();

	public $_monitorTable = null;

	/**
	 * Gend_Server_BaseServer constructor.
	 * 用于服务启动配置
	 * @param $config
	 */
	public function __construct($config)
	{
        Gend_Di::factory()->set('swoole_config',$config);
		$this->_config = $config;
	}

	/**
	 * 启动服务
	 */
	public function start()
	{
		//create server
		$class_name = $this->_config["server"]["class"];
		$class_obj = $this->_config["server"]["classname"];
		$this->_server = new $class_name($this->_config["server"]["host"],
			$this->_config["server"]["port"], SWOOLE_PROCESS, $this->_config["server"]["socket"]);

		//set the swoole config
		$this->_server->set($this->_config["swoole"]);
		Gend_Log::write(json_encode($this->_config["swoole"]));

		//load server dispatcher
		$dispatcherPath = SYS_ROOTDIR. $this->_config["server"]["dispatcher"];
		if (!file_exists($dispatcherPath)) {
			die("baseserver->Config->server->dispatcher was not found!");
		}
		require_once($dispatcherPath);

		$dispatcherClassName = basename($dispatcherPath, ".php");

		if (!class_exists($class_obj)) {
			die("baseserver->Config->server->dispatcher class was not found!");
		}

		$this->_mainDispatcher = new $class_obj($this, $this->_server);

		//bind event with main dispatcher
		$this->_server->on('Start', array($this->_mainDispatcher, 'onStart'));
		$this->_server->on('Shutdown', array($this->_mainDispatcher, 'onShutdown'));

		$this->_server->on('WorkerStart', array($this->_mainDispatcher, 'onWorkerStart'));
		$this->_server->on('WorkerError', array($this->_mainDispatcher, 'onWorkerError'));
		$this->_server->on('WorkerStop', array($this->_mainDispatcher, 'onWorkerStop'));

		$this->_server->on('ManagerStart', array($this->_mainDispatcher, 'onManagerStart'));
		$this->_server->on('ManagerStop', array($this->_mainDispatcher, 'onManagerStop'));

		$this->_server->on('Task', array($this->_mainDispatcher, 'onTask'));
		$this->_server->on('Finish', array($this->_mainDispatcher, 'onFinish'));

		$this->_server->on('Close', array($this->_mainDispatcher, 'onClose'));

		//tcp
		if ($this->_config["server"]["class"] == "swoole_server") {
			$this->_server->on('Connect', array($this->_mainDispatcher, 'onConnect'));
			$this->_server->on('Receive', array($this->_mainDispatcher, 'onReceive'));
		}

		//websocket
		if ($this->_config["server"]["class"] == "swoole_websocket_server") {
			$this->_server->on('Open', array($this->_mainDispatcher, 'onOpen'));
			$this->_server->on('Message', array($this->_mainDispatcher, 'onMessage'));
			$this->_server->on('Request', array($this->_mainDispatcher, 'onRequest'));
		}

		//http
		if ($this->_config["server"]["class"] == "swoole_http_server") {
			$this->_server->on('Request', array($this->_mainDispatcher, 'onRequest'));
		}

		//udp
		if ($this->_config["server"]["socket"] == "SWOOLE_SOCK_UDP") {
			$this->_server->on('Packet', array($this->_mainDispatcher, 'onPacket'));
			$this->_server->on('Receive', array($this->_mainDispatcher, 'onReceive'));
		}

		if (is_array($this->_config["listen"]) && count($this->_config["listen"]) > 0) {

			//create new listen with dispatcher
			foreach ($this->_config["listen"] as $key => $config) {
				$this->_subserver[$key] = $this->_server->addListener($config["host"], $config["port"], $config["socket"]);

				//load listen dispatcher
				$dispatcherPath = SYS_ROOTDIR. $config["dispatcher"];
				$classname = $config['classname'];

				if (!file_exists($dispatcherPath)) {
					die("baseserver->Config->listen->" . $key . "->dispatcher was not found!");
				}
				require_once($dispatcherPath);

				if (!class_exists($classname)) {
					die("baseserver->Config->listen->" . $key . "->dispatcher class was not found!");
				}
				$this->_subDispatcher[$key] = new $classname($this, $this->_subserver[$key], $config);

				//bind event with listen dispatcher
				//websocket
				if (isset($config["protocol"]["open_websocket_protocol"]) && $config["protocol"]["open_websocket_protocol"] && $config["socket"] == SWOOLE_SOCK_TCP) {
					$this->_subserver[$key]->on('Open', array($this->_subDispatcher[$key], 'onOpen'));
					$this->_subserver[$key]->on('Message', array($this->_subDispatcher[$key], 'onMessage'));
					$this->_subserver[$key]->on('Request', array($this->_subDispatcher[$key], 'onRequest'));
					$this->_subserver[$key]->on('Close', array($this->_subDispatcher[$key], 'onClose'));
					continue;
				}

				//http
				if (isset($config["protocol"]["open_http_protocol"]) && $config["protocol"]["open_http_protocol"] && $config["socket"] == SWOOLE_SOCK_TCP) {
					$this->_subserver[$key]->on('Request', array($this->_subDispatcher[$key], 'onRequest'));
					continue;
				}

				//tcp
				if ($config["socket"] == SWOOLE_SOCK_TCP) {
					$this->_subserver[$key]->on('Connect', array($this->_subDispatcher[$key], 'onConnect'));
					$this->_subserver[$key]->on('Receive', array($$this->_subDispatcher[$key], 'onReceive'));
					$this->_subserver[$key]->on('Close', array($this->_subDispatcher[$key], 'onClose'));
				}

				//udp
				if ($config["socket"] == SWOOLE_SOCK_UDP) {
					$this->_subserver[$key]->on('Packet', array($this->_subDispatcher[$key], 'onPacket'));
					$this->_subserver[$key]->on('Receive', array($this->_subDispatcher[$key], 'onReceive'));
				}

			}
		}

        //log agent
        Gend_EagleEye::setVersion("baseserver_php_0.3");

        //设置日志存储路径
        Gend_LogAgent::setLogPath($this->_config["server"]["logpath"]);

        //设置输出日志级别
        Gend_Log::setLogLevel($this->_config["server"]["loglevel"]);

        //设置channel异步方式写入日志
        Gend_LogAgent::setDumpLogMode(2);//swoole channel combine the log mode

        //性能监控服务暂时不开启
        Gend_EagleEye::disable();

        //log agent for dump log
        $this->_server->addProcess(new \swoole_process(function () {
            Gend_CliFunc::setProcessName($this->_config["server"]["server_name"], "log");
            Gend_LogAgent::threadDumpLog();
        }));

        Gend_Log::info("Server", __FILE__, __LINE__,
            "Server IP:" . $this->_config["server"]["host"] . " Port:" . $this->_config["server"]["port"] . " LocalIP:" . Gend_CliFunc::getLocalIp());

        $this->_server->start();

	}



	public function getConfig()
	{
		return $this->_config;
	}
}