<?php
/**
 * 干电力网 API服务 入口
 */
 if($_SERVER['SERVER_ADDR'] == '192.168.3.201'){ //测试机
     require '/gdl/gdl_php/ApiCore.php';
 } else {
    require __DIR__.'/../gdl_php/ApiCore.php';
}

ApiCore::init('app', 'gdlApiSdk');
SDb::setConfigFile(__DIR__ . '/app/conf/db.php');
require __DIR__ . '/app/conf/constant.php';
ApiCore::run();
