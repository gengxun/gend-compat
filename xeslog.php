<?php

/*
 * Guolog操作类
 */
!defined('GuoLOG_ENABLE') && define('GuoLOG_ENABLE', 0);

class Gend_Guolog extends Gend
{
    private static $in;
    public static function factory($type)
    {
        if (!isset(self::$in[$type])) {
            //Guolog未启用时
            if (empty(GuoLOG_ENABLE)) {
                self::$in[$type] = new self();
                return self::$in[$type];
            }

            //Guolog启用时
            $conf = Gend_Di::factory()->get('log')[$type];
            if (empty($conf['business'])) {
                return null;
            }
            if (!is_dir($conf['logDir'])) {
                mkdir($conf['logDir'], 666, true);
            }
            self::$in[$type] = new GuoLib\Log\GuoLog($conf);
        }
        return self::$in[$type];
    }

    /**
     * 默认方法
     * @param  string $method
     * @param  array $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        //空方法，用于 Guolog不启用时的数据截获
    }

}

?>