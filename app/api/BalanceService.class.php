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
}