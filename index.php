<?php
/**
 * 慧猎网 API服务 入口
 * @author yanghao
 * @date 2018-10-18
 */
define('ENV', 'online');
if(ENV == 'local'){
    require __DIR__.'/../hlw_php/ApiCore.php';
} else {
    require realpath( __DIR__).'/ApiCore.php';
}

ApiCore::init('app', 'hlwApiSdk');
SDb::setConfigFile(__DIR__ . '/app/conf/db.php');
require __DIR__ . '/app/conf/constant.php';
ApiCore::run();
