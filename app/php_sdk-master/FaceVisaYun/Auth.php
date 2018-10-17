<?php
namespace FaceVisaYun;

class Auth
{
    const AUTH_URL_FORMAT_ERROR = -1;
    const AUTH_SECRET_ID_KEY_ERROR = -2;

    /**
     * 签名函数
     * @param   $expired    过期时间
     * @param   $userid     暂时不用
     * @return string          签名
     */
    public static function appSign($url_param,$expired=0) {
       // $secretId = Conf::$SECRET_ID;
        $secretKey = Conf::$SECRET_KEY;
        $appid  =  Conf::$APPID;
        if (empty($url_param) || empty($secretKey)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }
        
        if(!is_array($url_param)) return self::AUTH_SECRET_ID_KEY_ERROR;
        ksort($url_param);
        $plainText ='';
        foreach ($url_param as $key => $value) {
           if($plainText) $plainText .= '&'; 
           $plainText .= $key.'='.$value ;
        }

        $plainText = $plainText.$secretKey;
        $sign = md5($plainText);         
        return $sign;
    }
}

