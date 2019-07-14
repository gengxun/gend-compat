<?php

/**
 * @version $Id: Mysqli.php 1878 2013-11-12 05:28:15Z liushuai $
 * */
class Gend_Db_Mysql extends Gend
{

    protected $_db = null; //连接db对象
    protected $_instant_name = "";
    protected $_cfg = null; //连接配置信息
    static $in = array();

    public static function Factory($r, $db = '')
    {

        self::$in[$db][$r] = new self($r, $db);
        return self::$in[$db][$r];
    }

    //初始化Mysql对象并进行连接服务器尝试
    public function __construct($r, $db)
    {
        $dblist = Gend_Di::factory()->get('db');
        if (empty($dblist)) {
            Gend_Di::factory()->set("debug_error", Gend_Exception::Factory("dbconfig  is not set")->ShowTry(1, 1));
            return;
        }
        $db = empty($db) ? array_keys($dblist)[0] : $db;
        $this->_instant_name = $db;
        $this->_cfg = $dblist[$db][$r];
        $this->_cfg['port'] = empty($this->_cfg['port']) ? 3306 : $this->_cfg['port'];
        $this->_db = mysqli_init();
        $this->_db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 30);

        //record connect time
        $starttime = microtime(true);

        $this->_db->real_connect($this->_cfg['host'], $this->_cfg['user'], $this->_cfg['pwd'], $this->_cfg['name'],
            $this->_cfg['port']);

        //prepare info of error
        $eagleeye_param = array(
            "x_name" => "mysql.connect",
            "x_host" => $this->_cfg['host'],
            "x_module" => "php_mysql_connect",
            "x_duration" => round(microtime(true) - $starttime, 4),
            "x_instance_name" => $r,
            "x_file" => __FILE__,
            "x_line" => __LINE__,
        );

        if (mysqli_connect_errno()) {
            //error
            $eagleeye_param["x_name"] = "mysql.connect.error";
            $eagleeye_param["x_code"] = mysqli_connect_errno();
            $eagleeye_param["x_msg"] = mysqli_connect_error();

//            Gend_EagleEye::baseLog($eagleeye_param);
            Gend_Guolog::factory('mysql')->error($eagleeye_param, __FILE__, __LINE__);
            self::showError("Connect failed: " . mysqli_connect_error());

            throw new Exception(mysqli_connect_error(), -mysqli_connect_errno()); //新加
        }
        //record log
//        Gend_EagleEye::baseLog($eagleeye_param);
        Gend_Guolog::factory('mysql')->info($eagleeye_param, __FILE__, __LINE__);

        $this->_db->query("SET character_set_connection={$this->_cfg['lang']},character_set_results={$this->_cfg['lang']},character_set_client=binary,sql_mode='';");
    }

    public function setTimeout($timeout = 1)
    {
        $this->_db->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
    }

    /**
     * 返回数据表对象
     */
    public function getDb()
    {
        return $this->_db;
    }

    /**
     * 选中并打开数据库
     *
     * @param string $name 重新选择数据库,为空时选择默认数据库
     * */
    public function useDb($name = null)
    {
        null === $name && $name = $this->_cfg['name'];
        $this->_db->select_db($name) or $this->showError("Can't use {$name}");
    }

    /**
     * 获取记录集合,当记录行为一个字段时输出变量结果 当记录行为多个字段时输出一维数组结果变量
     *
     * @param  string  $sql 标准查询SQL语句
     * @param  integer $r   是否合并数组
     * @return string|array
     * */
    public function get($sql, $r = null)
    {
        $rs = self::fetch(self::query($sql, $r));
        if (!empty($r) && !empty($rs)) {
            $rs = join(',', $rs);
        }
        return $rs;
    }

    /**
     * 返回查询记录的数组结果集
     *
     * @param  string  $sql 标准SQL语句
     * @return array
     * */
    public function getall($sql)
    {
        $item = array();
        $q = self::query($sql);
        while ($rs = self::fetch($q)) {
            $item[] = $rs;
        }
        return $item;
    }

    /**
     * 获取插入的自增ID
     *
     * @return integer
     * */
    public function getId()
    {
        return $this->_db->insert_id;
    }

    /**
     * 发送查询
     *
     * @param  string  $sql 标准SQL语句
     * @return bool|mysqli_result
     * */
    public function query($sql)
    {
        //$q= $this->_db->query($sql) or self::showMsg("Query to [{$sql}] ");
        //free the result
        if (empty($this->_db)) {
            return false;
        }
        while (mysqli_more_results($this->_db) && mysqli_next_result($this->_db)) {

            $dummyResult = mysqli_use_result($this->_db);

            if ($dummyResult instanceof mysqli_result) {
                mysqli_free_result($this->_db);
            }
        }

        //record query
        $startTime = microtime(true);
        $query = $this->_db->query($sql);
        $costTime = round(microtime(true) - $startTime, 4);
        //prepare info of error
        $eagleeyeParam = array(
            "x_name" => "mysql.request",
            "x_host" => $this->_cfg['host'],
            "x_module" => "php_mysql_query",
            "x_duration" => $costTime,
            "x_instance_name" => $this->_instant_name,
            "x_file" => __FILE__,
            "x_line" => __LINE__,
            "x_action" => $sql,
        );

        if (defined('DEBUG') && DEBUG == 1) {
            $stime = $etime = 0;
            $m = explode(' ', microtime());
            $_SERVER['REQUEST_TIME'] = !empty($_SERVER['request_time']) ? $_SERVER['request_time'] : $_SERVER['REQUEST_TIME'];
            $stime = number_format(($m[1] + $m[0] - $_SERVER['REQUEST_TIME']), 8) * 1000;
            $query = $this->_db->query($sql);
            $m = explode(' ', microtime());
            $etime = number_format(($m[1] + $m[0] - $_SERVER['REQUEST_TIME']), 8) * 1000;
            $sqltime = round(($etime - $stime), 8);
            $info = $this->_db->info;
            $explain = array();
            if ($query && preg_match("/^(select )/i", $sql)) {
                $key = md5($sql);
                $qs = $this->_db->query('EXPLAIN ' . $sql);
                while ($rs = self::fetch($qs)) {
                    $explain[] = $rs;
                }
                if (!empty($explain)) {
                    $this->cfg['dbdebug'][$key]['sql'] = $sql;
                    $this->cfg['dbdebug'][$key]['info'] = $info;
                    $this->cfg['dbdebug'][$key]['explain'] = $explain;
                    $this->cfg['dbdebug'][$key]['time'] = $sqltime;
                }
            }
            return $query;
        } elseif (!$query) {
            //error
            $eagleeyeParam["x_name"] = "mysql.request.error";
            $eagleeyeParam["x_code"] = mysqli_connect_errno();
            $eagleeyeParam["x_msg"] = mysqli_connect_error();
            //$eagleeye_param["x_backtrace"] = implode("\n",debug_backtrace());
//            Gend_EagleEye::baseLog($eagleeyeParam);
            Gend_Guolog::factory('mysql')->error($eagleeyeParam, __FILE__, __LINE__);
            self::showError("Query to [{$sql}] ");
            ;
        }
//        Gend_EagleEye::baseLog($eagleeyeParam);
        Gend_Guolog::factory('mysql')->info($eagleeyeParam, __FILE__, __LINE__);
        return $query;
    }

    /**
     * 返回字段名为索引的数组集合
     *
     * @param  results $q 查询指针
     * @return array
     * */
    public function fetch($q)
    {
        return $q->fetch_assoc();
    }

    /**
     * 格式化MYSQL查询字符串
     *
     * @param  string $str 待处理的字符串
     * @return string
     * */
    public function escape($str)
    {
        return $this->_db->real_escape_string($str);
    }

    /**
     * 关闭当前数据库连接
     *
     * @return bool
     * */
    public function close()
    {
        return $this->_db->close();
    }

    /**
     * 取得数据库中所有表名称
     *
     * @param  string $db 数据库名,默认为当前数据库
     * @return array
     * */
    public function getTB($db = null)
    {
        $item = array();
        $q = self::query('SHOW TABLES ' . (null == $db ? null : 'FROM ' . $db));
        while ($rs = self::fetchs($q))
            $item[] = $rs[0];
        return $item;
    }

    /**
     * 根据已知的表复制一张新表,如有自增ID时自增ID重置为零
     * 注意: 仅复制表结构包括索引配置,而不复制记录
     *
     * @param  string  $souTable 源表名
     * @param  string  $temTable 目标表名
     * @param  boolean $isdel    是否在处理前检查并删除目标表
     * @return boolean
     * */
    public function copyTB($souTable, $temTable, $isdel = false)
    {
        $isdel && self::query("DROP TABLE IF EXISTS `{$temTable}`"); //如果表存在则直接删除
        $temTable_sql = self::sqlTB($souTable);
        $temTable_sql = str_replace('CREATE TABLE `' . $souTable . '`',
            'CREATE TABLE IF NOT EXISTS `' . $temTable . '`', $temTable_sql);

        $this->_cfg['lang'] != 'utf-8' && $this->_cfg['lang'] != 'utf8' && $temTable_sql = iconv($this->_cfg['lang'],
            'utf-8', $temTable_sql);

        $result = self::query($temTable_sql); //创建复制表
        stripos($temTable_sql, 'AUTO_INCREMENT') && self::query("ALTER TABLE `{$temTable}` AUTO_INCREMENT =1"); //更新复制表自增ID
        return $result;
    }

    /**
     * 获取表中所有字段及属性
     *
     * @param  string $tb 表名
     * @return array
     * */
    public function getFD($tb)
    {
        $item = array();
        $q = self::query("SHOW FULL FIELDS FROM {$tb}"); //DESCRIBE users
        while ($rs = self::fetch($q))
            $item[] = $rs;
        return $item;
    }

    /**
     * 生成表的标准Create创建SQL语句
     *
     * @param  string $tb 表名
     * @return string
     * */
    public function sqlTB($tb)
    {
        $q = self::query("SHOW CREATE TABLE {$tb}");
        $rs = self::fetchs($q);
        return $rs[1];
    }

    /**
     * 如果表存在则删除
     *
     * @param  string $tables 表名称
     * @return boolean
     * */
    public function delTB($tables)
    {
        return self::query("DROP TABLE IF EXISTS `{$tables}`");
    }

    /**
     * 整理优化表
     * 注意: 多个表采用多个参数进行传入
     *
     * Example: setTB('table0','table1','tables2',...)
     * @param string 表名称可以是多个
     * @return boolean
     * */
    public function setTB()
    {
        $args = func_get_args();
        foreach ($args as &$v)
            self::query("OPTIMIZE TABLE {$v};");
    }

    /**
     * 生成REPLACE|UPDATE|INSERT等标准SQL语句
     *
     * @param  string $arr    操纵数据库的数组源
     * @param  string $dbname 数据表名
     * @param  string $type   SQL类型 UPDATE|INSERT|REPLACE|IFUPDATE
     * @param  string $where  where条件
     * @return string         一个标准的SQL语句
     * */
    public function subSQL($arr, $dbname, $type = 'update', $where = NULL, $duplicate = array())
    {
        $tem = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $k => &$v) {
                $v = self::escape($v);
                if (preg_match("/`/i", $v)) {
                    $tem[$k] = "`{$k}`={$v}";
                } else {
                    $tem[$k] = "`{$k}`='{$v}'";
                }
            }
        }
        switch (strtolower($type)) {
            case 'insert'://插入
                $sql = "INSERT INTO {$dbname} SET " . join(',', $tem);
                break;
            case 'replace'://替换
                $sql = "REPLACE INTO {$dbname} SET " . join(',', $tem);
                break;
            case 'update'://更新
                $sql = "UPDATE {$dbname} SET " . join(',', $tem) . " WHERE {$where}";
                break;
            case 'ifupdate'://存在则更新记录
                $tem = join(',', $tem);
                if (!empty($duplicate)) {
                    foreach ($duplicate as $ks => &$vs) {
                        $ifitem[$ks] = "`{$ks}`={$vs}";
                    }
                    $ifitem = join(',', $ifitem);
                    $sql = "INSERT INTO {$dbname} SET {$tem} ON DUPLICATE KEY UPDATE {$ifitem}";
                } else {
                    $sql = "INSERT INTO {$dbname} SET {$tem} ON DUPLICATE KEY UPDATE {$tem}";
                }
                break;
            case 'delete'://存在则更新记录
                $sql = "delete FROM {$dbname} WHERE  {$where}";
                break;
            default:
                $sql = null;
                break;
        }
        return $sql;
    }

    /**
     * 生成REPLACE|UPDATE|INSERT等标准SQL语句 同subsql函数相似但该函数会直接执行不返回SQL
     *
     * @param  string $arr 操纵数据库的数组源
     * @param  string $dbname 数据表名
     * @param  string $type SQL类型 UPDATE|INSERT|REPLACE|IFUPDATE
     * @param  string $where where条件
     * @return boolean
     * */
    public function doQuery($arr, $dbname, $type = 'update', $where = NULL)
    {
        $sql = self::subSQL($arr, $dbname, $type, $where);
        return self::query($sql);
    }

    /**
     * 返回键名为序列的数组集合
     *
     * @param  resource $q 资源标识指针
     * @return array
     * */
    public function fetchs($q)
    {
        return $q->fetch_row();
    }

    /**
     * 取得结果集中行的数目
     *
     * @param  resource $q 资源标识指针
     * @return array
     * */
    public function reRows($q)
    {
        return $q->num_rows;
    }

    /**
     * 取得被INSERT、UPDATE、DELETE查询所影响的记录行数
     *
     * @return int
     * */
    public function afrows()
    {
        return $this->_db->affected_rows;
    }

    /**
     * 释放结果集缓存
     *
     * @param  resource $q 资源标识指针
     * @return boolean
     * */
    public function refree($q)
    {
        return $q->free_result();
    }

    /**
     * 启动事务处理
     * @return unknown_type
     */
    function start()
    {
        $this->_db->query('START TRANSACTION');
    }

    /**
     * 提交事务处理
     * @return unknown_type
     */
    function commit()
    {
        $this->_db->query('COMMIT');
    }

    /**
     * 事务回滚
     * @return unknown_type
     */
    function back()
    {
        $this->_db->query('ROLLBACK');
    }

    /**
     * 设置异常消息 可以通过try块中捕捉该消息
     *
     * @param  string $str debug错误信息
     * @return void
     * */
    public function showError($str)
    {
        if (defined('DEBUG') && DEBUG == 1) {
            Gend_Di::factory()->set("debug_error",
                Gend_Exception::Factory('mysql-connet:' . $this->_db->error)->ShowTry(1, 1));
        } elseif (defined('DBLOG')) {
            $_tmp = '';
            isset($_SERVER['SERVER_ADDR']) && $_tmp .= '[' . $_SERVER['SERVER_ADDR'] . ']';
            isset($_SERVER['REQUEST_URI']) && $_tmp .= '[' . $_SERVER['REQUEST_URI'] . ']';
            $_tmp && $_tmp .= "\n";
            Gend_Log::write(date("Y-m-d H:i:s > ") . $_tmp . $str . $this->_db->error . "\n\n", 'mysql-errr.log');
        }
        return false;
    }

    public function getErrorInfo()
    {
        return array(
            "msg" => $this->_db->error,
            "code" => $this->_db->errno,
        );
    }

}
