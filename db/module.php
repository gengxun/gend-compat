<?php

class Gend_Db_Module extends Gend
{

    protected $_tableName        = '';
    protected $_where            = '1';
    protected $_field            = '';
    protected $_order            = '';
    protected $_group            = '';
    protected $_collect_table    = '';
    protected $_collect_field    = '';
    protected $_collection_on    = '';
    protected $_collection_where = '';
    protected $_start            = 0;
    protected $_limit            = 0;
    protected $_checksafe        = 1;
    protected $_db               = NULL;
    protected $checkcmd          = array('SELECT', 'UPDATE', 'INSERT', 'REPLACE', 'DELETE');
    protected $config            = array(
        'status'    => 1,
        'dfunction' => array('load_file', 'hex', 'substring', 'if', 'ord', 'char'),
        'daction'   => array('intooutfile', 'intodumpfile', 'unionselect', '(select', 'unionall', 'uniondistinct', '@'),
        'dnote'     => array('/*', '*/', '#', '--', '"'),
        'dlikehex'  => 1,
        'afullnote' => 1
    );

    /**
     * @param $conditions
     * @param string $type
     */
    public function setConditions($conditions, $type = 'AND')
    {
        $where = '1';
        if (is_array($conditions) && !empty($conditions)) {
            foreach ($conditions as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $wheres = '';
                    foreach ($value as $ky => $val) {
                        if (is_numeric($val)) {
                            $val = intval($val);
                        }
                        $wheres = (is_string($key)) ? " {$ky} {$key} {$val} " : " {$ky}  {$val} ";
                        $where  .= !empty($where) ? " {$type} " . $wheres : $wheres;
                    }
                } else {
                    if (is_numeric($value)) {
                        $value = intval($value);
                    } elseif (is_string($value)) {
                        $value = $this->escape($value);
                    }
                    $vs    = is_numeric($value) ? $value : "'{$value}'";
                    $where .= !empty($where) ? " AND `{$key}` = {$vs}" : " {$type} `{$key}` = {$vs}";
                }
            }
            $this->_where = $where;
        } elseif (!empty($conditions) && is_string($conditions)) {
            $this->_where = $conditions;
        }
    }

    /**
     * 设置数据表
     */
    public function setTable($table)
    {
        $this->_tableName     = $table;
        $this->_where         = '1';
        $this->_order         = '';
        $this->_group         = '';
        $this->_start         = 0;
        $this->_limit         = 0;
        $this->_collect_table = '';
    }

    /**
     * 获取当前实例的表名字
     */
    public function getTable()
    {
        return $this->_tableName;
    }

    /**
     * 设置查询字段
     *
     * */
    public function setField($field = array())
    {
        if (empty($field)) {
            $this->_field = '*';
        } elseif (is_array($field)) {
            foreach ($field as &$val) {
                if (!strpos($val, 'AS')) {
                    $val = $val;
                }
            }
            $this->_field = join(',', $field);
        } elseif (!empty($field) && !is_array($field)) {
            $this->_field = $field;
        }
    }

    /**
     * 设置limit数据
     *
     * */
    public function setLimit($start = 0, $end = 20)
    {
        $this->_start = $start;
        $this->_limit = $end;
    }

    /**
     * 设置limit数据
     *
     * */
    public function setSqlSafe()
    {
        $this->_checksafe = 1;
    }

    /**
     * 设置查询条件
     * */
    public function setWhere($where = '')
    {
        $this->_where = empty($where) ? 1 : $where;
    }

    /**
     * 获取查询条件
     */
    public function getWhere()
    {
        return $this->_where;
    }

    /**
     * 设置排序
     * */
    public function setOrder($order = '')
    {
        $this->_order = $order;
    }

    /**
     * 设置排序
     * */
    public function setGroup($group = '')
    {
        $this->_group = $group;
    }

    /**
     * 设置关系表
     * */
    public function setRelationTable($table)
    {
        if (!empty($table)) {
            $this->_collect_table = $table;
        }
    }

    /**
     * 设置关联字段
     * */
    public function setRelationOn($on)
    {
        $arron = array();
        if (is_array($on) && !empty($on)) {
            foreach ($on as $key => $val) {
                $arron[] = "{$this->_tableName}.{$key} = {$this->_collect_table}" . '.' . $val;
            }
        }
        if (!empty($arron)) {
            $this->_collection_on = join(' AND ', $arron);
        }
    }

    /**
     * 设置显示字段
     * @param $field
     */
    public function setRelationField($field)
    {
        if (!empty($field) && is_array($field)) {
            foreach ($field as &$val) {
                if ($this->_collect_table) {
                    $val = "{$this->_collect_table}.{$val}";
                }
            }
            $this->_collect_field = ',' . join(',', $field);
        }
    }

    /**
     * @param $conditions
     * @param string $type
     */
    public function setRelationWhere($conditions, $type = 'AND')
    {
        $where = '';
        if (is_array($conditions) && !empty($conditions) && !empty($this->_collect_table)) {
            foreach ($conditions as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $wheres = '';
                    foreach ($value as $ky => $val) {
                        if (is_numeric($val)) {
                            $val = intval($val);
                        } elseif (is_string($val)) {
                            $val = $this->escape($val);
                        }
                        $wheres = (is_string($key)) ? " {$this->_collect_table}.{$ky} {$key} {$val} " : " {$this->_collect_table}.{$ky}  {$val} ";
                        $where  .= !empty($where) ? " {$type} " . $wheres : $wheres;
                    }
                } else {
                    if (is_numeric($value)) {
                        $value = intval($value);
                    } elseif (is_string($value)) {
                        $value = $this->escape($value);
                    }
                    $where .= !empty($where) ? " AND {$this->_collect_table}.`{$key}` = '{$value}'" : " {$type} {$this->_collect_table}.`{$key}` = '{$value}'";
                }
            }
            $this->_collection_where = $where;
        } elseif (!is_array($conditions) && !empty($conditions) && !empty($this->_collect_table)) {
            $this->_collection_where = ' AND ' . $this->escape($conditions);
        }
    }

    /**
     * 格式化MYSQL查询字符串
     *
     * @param  string $str 待处理的字符串
     * @return string
     * */
    public function escape($str)
    {
        return $this->_db->escape($str);
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
    public function subSQL($arr, $dbname, $type = 'update', $where = NUll, $duplicate = array())
    {
        $tem  = $vals = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $k => &$v) {
                if (is_array($v) && $type == 'insertall') {
                    if (empty($keys)) {
                        $keys = join(',', array_keys($v));
                    }
                    if (!empty($v)) {
                        $vals[] = "('" . join("','", $v) . "')";
                    }
                } else {
                    $k = $this->escape($k);
                    $v = $this->escape($v);
                    /**************************/
                    /*
                    if (preg_match("/`/i", $v)) {
                        $tem[$k] = "`{$k}`={$v}"; //忘记具体解决什么业务场景的需求了
                    } else {
                        $tem[$k] = "`{$k}`='{$v}'";
                    }
                    */
                    $tem[$k] = "`{$k}`='{$v}'";
                }
            }
        }
        switch (strtolower($type)) {
            case 'insertall'://批量插入
                if (!empty($keys) && !empty($vals)) {
                    $sql = "INSERT INTO {$dbname} ({$keys}) VALUES " . join(',', $vals);
                } else {
                    $sql = NULL;
                }
                break;
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
                    $sql    = "INSERT INTO {$dbname} SET {$tem} ON DUPLICATE KEY UPDATE {$ifitem}";
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
     * 格式化数据
     *
     * @param  string $arr    操纵数据库的数组源
     * @param  string $type   SQL类型 UPDATE|INSERT|REPLACE|IFUPDATE
     * @return array
     * */
    public function formatMysqlData($arr,$type='')
    {
        $tem  = $vals = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $k => &$v) {
                $k = $this->escape($k);
                $v = $this->escape($v);
                if (preg_match("/`/i", $v)) {
                    $tem[$k] = "`{$k}`={$v}";
                } else {
                    $tem[$k] = "`{$k}`='{$v}'";
                }
            }
        }
        return $tem;
    }


    /**
     * 生成Insert语句
     *
     * @param  string $arr    操纵数据库的数组源
     * @param  string $tablename 数据表名
     * @return string         一个标准的SQL语句
     * */
    public function insertSql($arr, $tablename)
    {
        $tem = $this->formatMysqlData($arr);
        $sql =  "INSERT INTO {$tablename} SET " . join(',', $tem);
        return $sql;
    }
    /**
     * 生成replaceSql
     * @param  string $arr    操纵数据库的数组源
     * @param  string $tablename 数据表名
     * @return string         一个标准的SQL语句
     */
    public function replaceSql($arr,$tablename)
    {
        $tem = $this->formatMysqlData($arr);
        $sql = "REPLACE INTO {$tablename} SET " . join(',', $tem);
        return $sql;
    }

    /**
     * 生成updateSql
     * @param  string $arr    操纵数据库的数组源
     * @param  string $tablename 数据表名
     * @param  string $where    条件
     * @return string         一个标准的SQL语句
     */
    public function updateSql($arr,$tablename,$where)
    {
        $tem = $this->formatMysqlData($arr);
        $sql = "UPDATE {$tablename} SET " . join(',', $tem) . " WHERE {$where}";
        return $sql;
    }

    /**
     * 生成ifupdateSql
     * @param  string $arr    操纵数据库的数组源
     * @param  string $tablename 数据表名
     * @return string         一个标准的SQL语句
     */
    public function ifUpdateSql($arr,$duplicate,$tablename)
    {
        $tem = $this->formatMysqlData($arr);
        $tem = join(',', $tem);
        if (!empty($duplicate)) {
            foreach ($duplicate as $ks => &$vs) {
                $ifitem[$ks] = "`{$ks}`={$vs}";
            }
            $ifitem = join(',', $ifitem);
            $sql    = "INSERT INTO {$tablename} SET {$tem} ON DUPLICATE KEY UPDATE {$ifitem}";
        } else {
            $sql = "INSERT INTO {$tablename} SET {$tem} ON DUPLICATE KEY UPDATE {$tem}";
        }
        return $sql;
    }



    /**
     * 生成deleteSql
     * @param  string $tablename    操纵数据库的数组源
     * @param  string $where        数据表名
     * @return string               一个标准的SQL语句
     */
    public function deleteSql($tablename,$where)
    {
        $sql = "delete FROM {$tablename} WHERE  {$where}";
        return $sql;
    }

    /**
     * 生成updateSql
     * @param  string $arr    操纵数据库的数组源
     * @param  string $tablename 数据表名
     * @param int $option 额外参数 $option  0: [默认]标准插入，1:IGNORE插入，3:REPLACE插入
     * @return string         一个标准的SQL语句
     */
    public function insertAllSql($arr,$tablename, $option = 0)
    {
        $keys = $vals = array();
        foreach ($arr as $k => &$v) {
            if (is_array($v)) {
                if (empty($keys)) {
                    $keys = join(',', array_keys($v));
                }
                if (!empty($v)) {
                    $vals[] = "('" . join("','", $v) . "')";
                }
            }
        }
        if (!empty($keys) && !empty($vals)) {
            $head = "INSERT INTO";
            if (1 == $option) {
                $head = "INSERT IGNORE INTO";
            } else if (2 == $option) {
                $head = "REPLACE INTO";
            }
            $sql = "{$head} {$tablename} ({$keys}) VALUES " . join(',', $vals);
        } else {
            $sql = NULL;
        }
        return $sql;

    }

    /**
     * 格式化条件
     * */
    public function formatSql($arr)
    {
        $condition = " 1 ";
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $k => $v) {
                if (!is_array($v)) {
                    $condition .= " AND {$k}='{$v}'";
                } elseif (count($v) == 2) {
                    $condition .= " AND {$k} {$v[0]} {$v[1]}";
                }
            }
        }
        return $condition;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        $sql = '';
        if (empty($this->_field)) {
            $sql = 'SELECT ' . ' * ';
        } else {
            $sql = 'SELECT ' . $this->_field;
        }
        if (!empty($this->_collect_field) && !empty($this->_collect_table)) {
            $sql .= $this->_collect_field;
        }

        $sql .= ' FROM ' . $this->_tableName;
        if (!empty($this->_collect_table) && !empty($this->_collection_on)) {
            $sql .= ' LEFT JOIN ' . $this->_collect_table;
            $sql .= ' ON ' . $this->_collection_on;
        }
        if (!empty($this->_where) && !empty($this->_collection_where)) {
            $sql .= ' WHERE ' . $this->_where . ' ' . $this->_collection_where;
        } elseif (!empty($this->_where)) {
            $sql .= ' WHERE ' . $this->_where;
        } elseif (!empty($this->_collection_where)) {
            $sql .= ' WHERE ' . $this->_collection_where;
        }

        if (!empty($this->_group)) {
            $sql .= ' GROUP BY ' . $this->_group;
        }

        if (!empty($this->_order)) {
            $sql .= ' ORDER BY ' . $this->_order;
        }
        if (!empty($this->_limit)) {
            $start = !empty($this->_start) ? $this->_start : 0;
            $sql   .= ' LIMIT ' . $start . ',' . $this->_limit;
        }
        return $sql;
    }

    /**
     * @return string
     */
    public function getSqlSum()
    {
        $sql = 'SELECT COUNT(*) AS total ';

        $sql .= ' FROM ' . $this->_tableName;
        if (!empty($this->_collect_table) && !empty($this->_collection_on)) {
            $sql .= ' LEFT JOIN ' . $this->_collect_table;
            $sql .= ' ON ' . $this->_collection_on;
        }
        if (!empty($this->_where) && !empty($this->_collection_where)) {
            $sql .= ' WHERE ' . $this->_where . ' ' . $this->_collection_where;
        } elseif (!empty($this->_where)) {
            $sql .= ' WHERE ' . $this->_where;
        } elseif (!empty($this->_collection_where)) {
            $sql .= ' WHERE ' . $this->_collection_where;
        }
        if (!empty($this->_group)) {
            $sql .= ' GROUP BY ' . $this->_group;
        }
        return $sql;
    }

    /**
     * @return mixed
     */
    public function getLastId()
    {
        return $this->_db->getId();
    }

    /**
     * @return mixed
     */
    public function getOne()
    {
        $sql = $this->getSql();
        return $this->_db->get($sql);
    }

    /**
     * @param string $sql;
     * @param int $r;
     * @return mixed
     */
    public function get($sql, $r = 0)
    {
        return $r > 0 ? $this->_db->get($sql, 1) : $this->_db->get($sql);
    }

    /**
     * 获取查询的数据的总数
     * @return array();
     * */
    public function getSum()
    {
        $sql = $this->getSqlSum();
        return $this->_db->get($sql, 1);
    }

    /**
     * 获取列表
     */
    public function getList()
    {
        $list  = array();
        $query = $this->query();
        if (!empty($query)) {
            while ($rs = $this->fetch(($query))) {
                $list[] = $rs;
            }
        }
        return $list;
    }

    /**
     * @param $sql
     * @return source  mysql query
     */
    public function query($sql = '')
    {

        $sql     = empty($sql) ? $this->getSql() : $sql;
        $sqlsafe = $this->checkquery($sql);

        if ($sqlsafe < 1 && empty($this->_checksafe)) {
            $_tmp = '';
            $str  = "SqlSafe failed: ";
            isset($_SERVER['SERVER_ADDR']) && $_tmp .= '[' . $_SERVER['SERVER_ADDR'] . ']';
            isset($_SERVER['REQUEST_URI']) && $_tmp .= '[' . $_SERVER['REQUEST_URI'] . ']';
            $_tmp && $_tmp .= "\n";
            file_put_contents(DBLOG, date("Y-m-d H:i:s > ") . $_tmp . $str . $this->_db->error . "\n\n", FILE_APPEND);
            return false;
        }
        if (defined('DEBUG') && DEBUG == 1) {
            $stime                   = $etime                   = 0;
            $m                       = explode(' ', microtime());
            $_SERVER['REQUEST_TIME'] = !empty($_SERVER['request_time']) ? $_SERVER['request_time'] : $_SERVER['REQUEST_TIME'];
            $stime                   = number_format(($m[1] + $m[0] - $_SERVER['REQUEST_TIME']), 8) * 1000;
            $query                   = $this->_db->query($sql);
            $m                       = explode(' ', microtime());
            $etime                   = number_format(($m[1] + $m[0] - $_SERVER['REQUEST_TIME']), 8) * 1000;
            $sqltime                 = round(($etime - $stime), 8);
            $info                    = $this->_db->info;
            $explain                 = array();
            if ($query && preg_match("/^(select )/i", $sql)) {
                $key = md5($sql);
                $qs = $this->_db->query('EXPLAIN ' . $sql);
                while ($rs = self::fetch($qs)) {
                    $explain[] = $rs;
                }
                if(!empty($explain)){
                    $this->cfg['dbdebug'][$key]['sql']      = $sql;
                    $this->cfg['dbdebug'][$key]['info']     = $info;
                    $this->cfg['dbdebug'][$key]['explain']  = $explain;
                    $this->cfg['dbdebug'][$key]['time']     = $sqltime;
                }
            }
            return $query;
        } else {
            return $this->_db->query($sql);
        }
    }

    /**
     * 取得被INSERT、UPDATE、DELETE查询所影响的记录行数
     *
     * @return int
     * */
    public function afrows()
    {
        return $this->_db->afrows();
    }

    /**
     * @param $query
     * @return source  mysql fetch
     */
    public function fetch($query)
    {
        if (!empty($query)) {
            return $this->_db->fetch($query);
        }
        return false;
    }

    //开启事务
    public function trans_begin()
    {
        $this->_db->query("set autocommit=0");
        return true;
    }

    //事务回滚
    public function trans_rollback()
    {

        $this->_db->query("ROLLBACK");
        $this->_db->query("set autocommit=1");
        return true;
    }

    //事务回滚
    public function trans_commit()
    {
        $this->_db->query("COMMIT");
        $this->_db->query("set autocommit=1");
        return true;
    }

    //sql安全监测
    public function checkquery($sql)
    {
        $cmd = trim(strtoupper(substr($sql, 0, strpos($sql, ' '))));
        if (in_array($cmd, $this->checkcmd)) {
            $test = self::_do_query_safe($sql);
            if ($test < 1) {
                return false;
            }
        }
        return true;
    }

    private function _do_query_safe($sql)
    {
        $sql   = str_replace(array('\\\\', '\\\'', '\\"', '\'\''), '', $sql);
        $mark  = $clean = '';
        if (strpos($sql, '/') === false && strpos($sql, '#') === false && strpos($sql, '-- ') === false) {
            $clean = preg_replace("/'(.+?)'/s", '', $sql);
        } else {
            $len   = mb_strlen($sql);
            $mark  = $clean = '';
            for ($i = 0; $i < $len; $i++) {
                $str = $sql[$i];
                switch ($str) {
                    case '\'':
                        if (!$mark) {
                            $mark  = '\'';
                            $clean .= $str;
                        } elseif ($mark == '\'') {
                            $mark = '';
                        }
                        break;
                    case '/':
                        if (empty($mark) && $sql[$i + 1] == '*') {
                            $mark  = '/*';
                            $clean .= $mark;
                            $i++;
                        } elseif ($mark == '/*' && $sql[$i - 1] == '*') {
                            $mark  = '';
                            $clean .= '*';
                        }
                        break;
                    case '#':
                        if (empty($mark)) {
                            $mark  = $str;
                            $clean .= $str;
                        }
                        break;
                    case "\n":
                        if ($mark == '#' || $mark == '--') {
                            $mark = '';
                        }
                        break;
                    case '-':
                        if (empty($mark) && substr($sql, $i, 3) == '-- ') {
                            $mark  = '-- ';
                            $clean .= $mark;
                        }
                        break;

                    default:

                        break;
                }
                $clean .= $mark ? '' : $str;
            }
        }

        if (strpos($clean, '@') !== false) {
            return '-3';
        }
        $clean = preg_replace("/[^a-z0-9_\-\(\)#\*\/\"]+/is", "", strtolower($clean));

        if ($this->config['afullnote']) {
            $clean = str_replace('/**/', '', $clean);
        }

        if (is_array($this->config['dfunction'])) {
            foreach ($this->config['dfunction'] as $fun) {
                if (strpos($clean, $fun . '(') !== false)
                    return '-1';
            }
        }

        if (is_array($this->config['daction'])) {
            foreach ($this->config['daction'] as $action) {
                if (strpos($clean, $action) !== false)
                    return '-3';
            }
        }

        if ($this->config['dlikehex'] && strpos($clean, 'like0x')) {
            return '-2';
        }

        if (is_array($this->config['dnote'])) {
            foreach ($this->config['dnote'] as $note) {
                if (strpos($clean, $note) !== false)
                    return '-4';
            }
        }
        return 1;
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
    public function copyTB($temTable, $isdel = false)
    {
        return $this->_db->copyTB($this->_tableName, $temTable, $isdel);
    }

    /**
     * 切换数据库
     * @param  mixed $dbName
     */
    public function useDb($dbName = null)
    {
        $this->_db->useDb($dbName);
    }

    public function setTimeout($timeout = 30)
    {
        $this->_db->setTimeout($timeout);
    }

    public function getErrorInfo()
    {
        return $this->_db->getErrorInfo();
    }

}
