<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-07-12
 * Time: 16:48
 */

use com\hlw\common\dataobject\common\ResultDO;

class api_HlwRegisterService extends api_Abstract implements \com\hlw\huiliewang\interfaces\HlwRegisterServiceIf
{
    //判定手机是否存在
    public function checkTel($tel)
    {
        // TODO: Implement checkTel() method.
        $resultDo = new ResultDO();
        $company = new model_huiliewang_company();
        $data = $company->selectOne('linktel = '.$tel);
        if(empty($data)){
            $resultDo->success = true;
            $resultDo->code = 200;
            $resultDo->message = '手机号可以使用';
        }else{
            $resultDo->success = true;
            $resultDo->code = 200;
            $resultDo->message = '手机号已存在';
        }
        return $resultDo;
    }

    //注册
    public function regist(\com\hlw\huiliewang\dataobject\register\RegisterRequestDTO $requestDO)
    {
        // TODO: Implement regist() method.
        $resultDo = new ResultDO();
        $member = new model_huiliewang_member();

        if (!$requestDO->tel) {
            $resultDo->code = 500;
            $resultDo->success = TRUE;
            $resultDo->message = '缺少电话';
            return $resultDo;
        }

        if (!$requestDO->code) {
            $resultDo->code = 500;
            $resultDo->success = TRUE;
            $resultDo->message = '缺少验证码';
            return $resultDo;
        }

        $code = $requestDO->code;
        $session_code = $requestDO->session_code;
        $code_time = $requestDO->code_time;
        $tel = $requestDO->tel;
        $invite = $requestDO->invite;

        $timerang = time()-intval($code_time);
        if($timerang > 24*3600  || !($session_code == $code)){
            $resultDo->success = false;
            $resultDo->code = 500;
            $resultDo->message = '手机验证码过期';
        }else{
            $arr['regcode'] = intval($code);
            $arr['moblie'] = $tel;
            $arr['usertype'] = 2;
            $arr['passtext'] = $invite;
            $status = $member->insert($arr);
            if($status){
                $resultDo->success = true;
                $resultDo->code = 200;
                $resultDo->message = '注册成功！';
            }else{
                $resultDo->success = false;
                $resultDo->code = 500;
                $resultDo->message = '注册失败！';
            }
        }
        return $resultDo;
    }
}