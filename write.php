<?php
/**
 * Gend_Write
 * 生成单表的写数据表对象
 *
 * @Author  kevin
 * @version $Id$
 **/
class Gend_Write extends Gend_Db_Module
{

    static $in = null;

    public static function Factory($table='',$db='',$driver='Mysql')
    {
        if (is_null(self::$in)) {
            self::$in = new self($table, $db, $driver);
        }
        self::$in->setTable($table);
        return self::$in;
    }

    public function  __construct($table='',$db=NULL,$driver='Mysql')
    {
        $Db_Module              = "Gend_Db_{$driver}";
        $this->_db              = $Db_Module::Factory('w',$db);
    }

    /**
     * 返回数据表对象
     */
    public function getModule()
    {
        return $this->_db;
    }

    /**
     * 插入单条记录
     * @param $data
     * @return mixed
     */
    public function add($data)
    {
        $sql = $this->subSQL($data, $this->_tableName, 'insert');
        if($this->query($sql)){
            return $this->getLastId();
        }
        return false;
    }

    /**
     * 根据条件更新记录
     * @param $conditios
     * @param $data
     * @return array|string
     */
    public function edit($conditios,$data)
    {
        if(empty($conditios) || empty($data)){
            return array('code'=>0,'argument is error [$arg[0]:array ,$arg[1]:array]');
        }
        $this->setConditions($conditios);
        $where  = !empty($ids)?" id IN(".join(",",$ids).")":$this->getWhere();
        $sql = $this->subSQL($data,$this->_tableName,'update',$where);
        return $this->query($sql);
    }

    /**
     * 根据自增id更新记录
     * @param   int     $id
     * @param   array   $data
     * @return bool
     */
    public function editById($id,$data)
    {
        if(empty($id) || empty($data)){
            return array('code'=>0,'argument is error [$arg[0]:int ,$arg[1]:array]');
        }
        $sql = $this->subSQL($data, $this->_tableName, 'update', " id={$id}");
        return $this->query($sql);
    }

    /**
     * 根据条件删除记录
     * @param   mixed $condition
     * @return bool
     */
    public function del($condition)
    {
        if(empty($condition)){
            return false;
        }
        $this->setConditions($condition);
        $this->setLimit(0,0);
        $sql = $this->subSQL(array(),$this->_tableName,'delete',$this->getWhere());
        return $this->query($sql);
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
        return $this->copyTB($this->_tableName, $temTable, $isdel);
    }

    /**
     * 批量添加记录
     */
    public function batchAdd()
    {

    }

    /**
     * 根据id获取单条记录
     * @param $id int id
     * @param mixed array  要获取的字段
     * @return  array()
     **/
    public function getById($id,$fields = array())
    {
        $this->setWhere("id={$id}");
        $this->setField($fields);
        return $this->getOne();
    }
}