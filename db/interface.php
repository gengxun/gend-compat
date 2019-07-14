<?php
/**
 *
 * 可实现的读写数据库接口
 *
 * 对象介绍
 * @Author  PhpStorm
 * @version $Id$
 **/
abstract class Gend_Interface extends Gend
{
    /**
     * @param       $id
     * @param array $fields
     * @param int   $cache
     *
     * @return mixed
     */
    public function getById($id,$fields = array()){}

    /**
     * @param     $con
     * @param int $start
     * @param int $limit
     *
     * @return mixed
     */
    public function getList($con,$start=0,$limit=20){}

    /**
     * @param $data
     *
     * @return mixed
     */
    public function add($data){}

    /**
     * @param $condition
     *
     * @return mixed
     */
    public function del($condition){}

    /**
     * @param $id
     * @param $data
     *
     * @return mixed
     */
    public function editById($id,$data){}

}
?>