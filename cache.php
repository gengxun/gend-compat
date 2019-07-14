<?php
/**
 * Gend Framework
**/
class Gend_Cache
{
    private static $in=null;
    /**
     * @param int $t    选择数据0-文件,1-Redis,2-swoole-table
     * @param int $db   选择数据库
     *
     * @return Gend_Cache_Fcache|Gend_Cache_Redis|null
     */
    public static function factory($t=0,$db=0)
    {
        if($t==1){
            self::$in=Gend_Cache_Redis::Factory();
        }elseif($t == 2){
            self::$in=Gend_Cache_Swooletable::Factory();
        } else {
            self::$in=Gend_Cache_Fcache::Factory();
        }
        return self::$in;
    }
}

?>