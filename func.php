<?php
/**
 * Gend Framework
 **/
class Gend_Func
{
    private $fdir=null;
    public  $fitem=array();
    private static $in=null;

    /**
     * 对象静态初始化并载入存储在[Function]目录的函数库
     *
     * @Example:
     *          静态加载: Gend_Func::load('dopost')
     *          静态加载: Gend_Func::load('dopost','doget')
     *          动态加载: Gend_Func::load()->dopost();
     * @param  string 函数名,多个
     * @return Object
     **/
    public static function load()
    {
        if(null===self::$in){
            self::$in = new self();
            self::$in->fdir= FD_ROOT.'function/';
            $GLOBALS['_FD_FUNC']=&self::$in->fitem;
        }
        $args=func_get_args();
        foreach($args as $v) {
            self::$in->isFunction($v);
        }
        return self::$in;
    }

    /**
     * 等同于 factory 方法
     * 注意: 该方法准备废弃,在下一版本中废弃
     **/
    public static function Init()
    {
        if(null===self::$in){
            self::$in = new self();
            self::$in->fdir=FD_ROOT.'function/';
            $GLOBALS['_FD_FUNC']=&self::$in->fitem;
        }
        $args = func_get_args();

        foreach($args as $v){
            self::$in->isFunction($v);
        }
        return self::$in;

    }

    /**
     * 检测并载入函数,私密方法供内部使用
     *
     * @param  string 函数名
     * @return null
     **/
    private function isFunction($fn)
    {
        $classname = $fn;
        $fn = strtolower($fn);
        $fn = str_replace('Gend','',$fn);
        if(!in_array($fn,$this->fitem)){
            if(is_file($this->fdir.'Gend.'.$fn.'.php')){
                require_once($this->fdir.'Gend.'.$fn.'.php');
            }else{
                trigger_error("Has Not Found Function $fn()", E_USER_WARNING);
                //throw new Gend_Exception("Has Not Found Function $fn()",__LINE__);
            }
            if (class_exists($classname,false)) {
                $this->$classname= new $classname();
            }else{
                $this->$fn=$fn;
            }
        }
    }

    /**
     * 魔法函数: 自动载入对象中不存在的方法
     *
     * @param  string  函数名
     * @return resource
     **/
    public function __call($fn,$fv)
    {
        self::isFunction($fn);
        return call_user_func_array($fn,$fv);
    }
}
?>