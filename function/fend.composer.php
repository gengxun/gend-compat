<?php
/**
 * Gend Framework
 * 自动载入composer组件
 * @kevin
 * @version $Id
 **/
!defined('FD_PLUGIN') && define('FD_PLUGIN', dirname(FD_ROOT) . FD_DS . 'plugin' . FD_DS);
function composer()
{
        if(is_file(FD_PLUGIN.'vendor/autoload.php')){
            require_once(FD_PLUGIN.'vendor/autoload.php');
        }else{
            trigger_error("Has Not Found composer autoload.php ".FD_PLUGIN.'vendor/autoload.php', E_USER_WARNING);
        }
}
?>