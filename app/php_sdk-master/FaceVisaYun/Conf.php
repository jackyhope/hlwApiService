<?php
namespace FaceVisaYun;

class Conf
{
    const PKG_VERSION = '1.0.*';

    const API_YOUTU_END_POINT = 'https://open.facevisa.com/';
    const API_YOUTU_CHARGE_END_POINT = 'https://open.facevisa.com/';
    const API_TENCENTYUN_END_POINT = 'https://open.facevisa.com/';


    // 请到 open.youtu.qq.com查看您对应的appid相关信息并填充
    // 请统一 通过 setAppInfo 设置

    public static $APPID = '43645806'; //对应client_id
    public static $SECRET_ID = 'd39eba36386e3034ae4cc68d0fae3c303459e85b';
    public static $SECRET_KEY = 'd39eba36386e3034ae4cc68d0fae3c303459e85b';
    public static $END_POINT = 'https://open.facevisa.com/';


    // 开发者 QQ
    public static $USER_ID = '1505755153';

    // 标示SDK 版本
    public static function getUA() {
        return 'YoutuPHP/'.self::PKG_VERSION.' ('.php_uname().')';
    }



    // 初始化 应用信息
    public static function setAppInfo($appid, $secretId, $secretKey, $userid, $end_point = self::API_YOUTU_END_POINT) {

        self::$APPID = $appid;
        self::$SECRET_ID = $secretId;
        self::$SECRET_KEY = $secretKey;
        self::$USER_ID = $userid;
        self::$END_POINT = $end_point;

    }
}
