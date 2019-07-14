<?php
/**
 * Class Gend_cmdFunc
 * cli 命令行下及swoole下公用函数
 */

class Gend_CliFunc
{

	/**
	 * tal自定义服务进程名设置，默认将当前进程名称改为tal_$prefix:typenam如tal_baseserver:master
	 * @param $prefix
	 * @param $typeName
	 */
	public static function setProcessName($prefix, $typeName)
	{

        if (empty($_SERVER['SSH_AUTH_SOCK']) || stripos($_SERVER['SSH_AUTH_SOCK'], 'apple') === false) {

             swoole_set_process_name("tal_" . $prefix . ":" . $typeName);
        }
	}

	/**
	 * 通过shell命令获取当前ip列表，并找出a\b\c类ip地址。
	 * 用于自动识别当前服务器ip地址
	 * 建议使用root权限服务使用
	 * @param string $localIP
	 * @return string
	 */
	public static function getLocalIp($localIP = "0.0.0.0")
	{
		$serverIps = swoole_get_local_ip();
		$patternArray = array(
			'10\.',
			'172\.1[6-9]\.',
			'172\.2[0-9]\.',
			'172\.31\.',
			'192\.168\.'
		);

		foreach ($serverIps as $serverIp) {
			// 匹配内网IP
			if (preg_match('#^' . implode('|', $patternArray) . '#', $serverIp)) {
				return trim($serverIp);
			}
		}
		//can't found ok use first
		return $localIP;
	}

	/**
	 * 简易加盐，数据签名
	 * @param $param
	 * @param $salt
	 * @return string
	 */
	public static function generalToken($param, $salt)
	{
		unset($param["token"]);
		ksort($param);
		$sumstring = http_build_query($param);
		return md5($sumstring . $salt);
	}

	/**
	 * 加盐数据签名验证合法性
	 * @param $param
	 * @param $salt
	 * @return bool
	 */
	public static function checkToken($param, $salt)
	{
		if (!isset($param["token"]) && strlen(trim($param["token"])) == 0) {
			return false;
		}

		$token = $param["token"];
		unset($param["token"]);

		ksort($param);
		$sumstring = http_build_query($param);
		$paramtoken = md5($sumstring . $salt);
		if ($token !== $paramtoken) {
			return false;
		}
		return true;
	}

	/**
	 * shell命令检测进程pid是否存在
	 * @param $pid
	 * @return int
	 */
	public static function ifrun($pid)
	{
		$cmd = 'ps axu|grep "\b' . $pid . '\b"|grep -v "grep"|wc -l';
		exec("$cmd", $ret);

		$ret = trim($ret[0]);

		if ($ret > 0) {
			return intval($ret);
		} else {
			return 0;
		}
	}

    /**
     * shell命令检测进程名是否存在
     *
     * @param $pName
     * @param $includeSelf true：当前进程是pName  false：当前进程不是pName
     * @return bool
     */
    public static function ifrunByName($pName,$includeSelf = true)
    {
        if(is_array($pName)) {
            foreach($pName as &$part) {
                $part = 'grep "'.$part.'"';
            }
            $cmd = implode('|', $pName);
        } else {
            $cmd = 'grep "'.$pName.'"';
        }
        #$cmd = 'ps axu|'.$cmd.'|grep -v "grep"|wc -l';
        $cmd = 'ps axu|'.$cmd.'|grep -v "grep"|grep -v "/bin/sh -c"|wc -l';
        exec("$cmd", $ret);
        $ret = trim($ret[0]);

        return ($includeSelf ? $ret > 1 : $ret > 0);
    }

	/**
	 * 简单的封装 json_encode 构建一个 http 返回 msg
     * @param $code
     * @param $msg
     * @param $data
     * @param $version
	 * */
    public static function buildResponseResult($code, $msg, $data = array(), $version = "1.0")
    {
        $result = array(
            "code" => $code,
            "msg" => $msg,
            "version" => $version,
            "data" => $data,
            "timestamp" => time(),
        );
        return json_encode($result);
    }

    /*
     * 封装一个日志格式化函数
     * @param $tag  日志tag
     * @param $file  所在文件
     * @param $line  日志行数
     * @param $msg   日志消息
     * */
    public static function formatLog($tag, $file, $line, $msg)
    {

        if (is_array($msg)) {
            $msg = json_encode($msg);
        }

        //t type ,p path,l line, m msg,g tag,e time,c cost
        $log = array(
            date("Y-m-d H:i:s"),
            getmypid(),
            $tag,
            $msg,
            $file,
            $line,
        );

        return $log;
    }

    /**
     * 运行时间计算
     * @return mixed
     */
    public static function getMicroTime() {
        list($micro, $sec) = explode(" ", microtime());
        return $sec+$micro;
    }
}