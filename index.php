<?php
/**
 * 慧猎网 API服务 入口
 */
require __DIR__.'/../hlw_php/ApiCore.php';

ApiCore::init('app', 'hlwApiSdk');
SDb::setConfigFile(__DIR__ . '/app/conf/db.php');
require __DIR__ . '/app/conf/constant.php';
ApiCore::run();
