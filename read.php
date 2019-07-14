<?php
/**
 * 单表读数据对象
 *
 * @Author  kevin
 * @version $Id$
 **/
class Gend_Read extends Gend_Db_Module
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
        if(!empty($table)){
            $this->_tableName   = $table;
        }
        $Db_Module              = "Gend_Db_{$driver}";
        $this->_db              = $Db_Module::Factory('r',$db);
    }

    /**
     * 返回数据表对象
     */
    public function getModule()
    {
        return $this->_db;
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

    /**
     * 根据ids获取多条记录
     * @param $idarr array
     * @param $fields array  要获取的字段
     * @return  array()
     **/
    public function getByIdArray($idarr,$fields = array())
    {
        $ids = join(',',$idarr);
        $this->setWhere("id IN({$ids})");
        $this->setField($fields);
        return $this->getList();
    }

    /**
     * 根据条件获取记录列表
     * @param $conditions
     * @return mixed
     */
    public function getListByCondition($conditions = array(), $fields = array(), $order = array())
    {
        $this->setConditions($conditions);
        $this->setField($fields);
        $this->setOrder($order);
        return $this->getList();
    }

    /**
     * 根据条件获取多条记录
     * @param $conditions
     * @return mixed
     */
    public function getInfoByCondition($conditions=array(),$fields = array(),$order='')
    {
        $this->setConditions($conditions);
        $this->setField($fields);
        if(!empty($order)){
            $this->setOrder($order);
        }
        return $this->getOne();
    }

    /**
     * 分页获取数据列表
     * @param $con
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function getDataList($con=array(),$start=0,$limit=20,$fields = array())
    {
        $item = array('psize' => $limit, 'skip' => $start, 'total' => 0, 'list' => array());
        $this->setConditions($con);
        $this->setField($fields);
        $this->setLimit($start,$limit);
        $item['total'] = $this->getSum();
        $item['list']  = $this->getList();
        return $item;
    }

}