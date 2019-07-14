<?php
/**
 * 全局变量管理
 * User: kevin
 * Date: 2017/11/20
 * Time: 下午5:49
 */
class Gend_Di extends Gend
{
    protected  $BI = array();
    public static function factory()
    {
        static $_obj = null;
        //是否需要重新连接
        if(empty($_obj)){
            $_obj = new self();
        }
        return $_obj;
    }

    public  function set($key,$val)
    {
        if(!isset($this->BI[$key])){
            $this->BI[$key] = $val;
        }
    }

    public  function reSet($key,$val)
    {
        $this->BI[$key] = $val;
    }
    public  function getList()
    {
        return $this->BI;
    }
    public  function get($key)
    {
        return isset($this->BI[$key])?$this->BI[$key]:'' ;
    }
}