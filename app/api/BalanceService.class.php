<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-06
 * Time: 16:31
 */

use com\hlw\huiliewang\interfaces\BalanceServiceIf;

class api_BalanceService extends api_Abstract implements BalanceServiceIf
{
    /**
     * 融营云账户令牌
     */
    //
    //账户授权令牌
    const RONGYINYUN_ACCOUNT_SID = '5bee00bd21de4a65a50e14d714d58412';
    //呼叫中心令牌
    const RONGYINYUN_CALLCENTER_APPID = '00000000699efd070169e76bd4970372';
    const RONGYINYUN_CALLCENTER_APP_TOKEN = "3ff246aa87687839df55f843459e47b6";
    //点击回拨令牌
    const RONGYINYUN_CALLBACK_APPID = '00000000699efd07016afdbdec981541';
    const RONGYINYUN_CALLBACK_APP_TOKEN = '4ac7a32cb830dfecf477941620ee1a2b';

    protected $curlTimeOut = 30; //curl接口时间限制

    public function getBalance()
    {
        // TODO: Implement getBalance() method.
        //查询
        $data = [
                "userData" => "7be4a9ce-8ea2-4c74-b822-f4472194621d"
            ];
        $data = json_encode($data);
        $auth = "NjJXOUptSHVlUWdoUExKbXJMVS1leUoxYzJWeVNXUWlPaUkxTlozSTZiMGgzaEl2T1lHaGUxbjFiNGZjSFM2RnBRc1BpbmdQaW5nQDEx";// base64_encode($data);
        $url = "http://211.152.35.81:8766/management/customer/isallow";
        $header = array('Content-Type:' . 'application/json;charset=utf-8',
            'Accept:' . 'application/json',
            'Authorization:'.$auth);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $msg = curl_exec($ch);
        return $msg;
    }

    /**
     * 融营云呼叫中心SIG获取
     */
    private static function getsig($timestamp, $accountSid, $appId) {
        $sig = strtoupper(md5($accountSid . ":" . $appId . ":" . $timestamp));
        return $sig;
    }

    /**
     * 融营云呼叫中心auth获取
     */
    private static function getauth($timestamp, $appId, $appToken) {
        $auth = base64_encode($appId . ":" . $appToken . ":" . $timestamp);
        return $auth;
    }

    /**
     * 获取指定话单
     */
    public function getPhoneCord($sessionId)
    {
        // TODO: Implement getPhoneCord() method.
        $timestamp = date('YmdHis');
        $sig = self::getsig($timestamp,self::RONGYINYUN_ACCOUNT_SID,self::RONGYINYUN_CALLCENTER_APPID);
        $auth = self::getauth($timestamp,self::RONGYINYUN_CALLCENTER_APPID,self::RONGYINYUN_CALLCENTER_APP_TOKEN);

        $url = 'https://wdapi.yuntongxin.vip/20181221/rest/click/call/record/v1?sig='.$sig;
        $header = array('Content-Type:' . 'application/json;charset=utf-8',
            'Accept:' . 'application/json',
            'Authorization:' . $auth);

        $data = ['CallDetail'=>['SessionId'=>$sessionId]];
        $data = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_HTTP_VERSION, '1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->curlTimeOut); //设置超时时长

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $msg = curl_exec($ch);
        return $msg;
    }

    /**
     * 批量获取话单
     */
    public function getMultiPhoneCord($starttime,$endtime,$maxId)
    {
        // TODO: Implement getMultiPhoneCord() method.
        $timestamp = date('YmdHis');
        $sig = self::getsig($timestamp,self::RONGYINYUN_ACCOUNT_SID,self::RONGYINYUN_CALLCENTER_APPID);
        $auth = self::getauth($timestamp,self::RONGYINYUN_CALLCENTER_APPID,self::RONGYINYUN_CALLCENTER_APP_TOKEN);

        $url = 'https://wdapi.yuntongxin.vip/20181221/rest/click/call/recordlist/v1?sig='.$sig;
        $header = array('Content-Type:' . 'application/json;charset=utf-8',
            'Accept:' . 'application/json',
            'Authorization:' . $auth);

        $data = ['BillList'=> [
            'Appid' => self::RONGYINYUN_CALLCENTER_APPID,
            'StartTime' => $starttime,
            'EndTime' => $endtime,
            'MaxId' => $maxId
        ]];

        $data = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_HTTP_VERSION, '1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->curlTimeOut); //设置超时时长

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $msg = curl_exec($ch);

        return $msg;
    }

}