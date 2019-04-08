<?php
/**
 * 慧猎网 API服务 入口
 * @author yanghao
 * @date 2018-10-18
 */
error_reporting(E_ALL);
define('ENV', 'online');
if(ENV == 'local'){
    require __DIR__.'/../hlw_php/ApiCore.php';
} else {
    require realpath('/home/wwwroot/hlw_php').'/ApiCore.php';
}

ApiCore::init('app', 'hlwApiSdk');


if (strpos($_SERVER['SERVER_ADDR'], '192.168.0.129') !== FALSE) {//测试环境1
    SDb::setConfigFile(__DIR__ . '/app/conf/db.test.php');
}else if (strpos($_SERVER['SERVER_ADDR'], '192.168') !== FALSE) { //本地环境
    SDb::setConfigFile(__DIR__ . '/app/conf/db.local.php');
} else {//线上环境
    SDb::setConfigFile(__DIR__ . '/app/conf/db.product.php');
}
require __DIR__ . '/app/conf/constant.php';
ApiCore::run();
