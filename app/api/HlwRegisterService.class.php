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

    public function sendMSG($tel, $code)
    {
        // TODO: Implement sendMSG() method.
        $resultDO = new ResultDO();
        $this->resultDo->success = false;
        $this->resultDo->code = 500;
        if(!tel){
            $resultDO->message = '请添加手机号';
        }
        try{

        }catch (\Exception $e){

        }
        return $resultDO;
    }

    //判定手机是否存在
    public function checkTel($tel)
    {
        // TODO: Implement checkTel() method.
        $resultDo = new ResultDO();
        $company = new model_huiliewang_member();
        $data = $company->selectOne(['moblie'=>$tel]);
        if(empty($data)){
            $resultDo->success = true;
            $resultDo->code = 200;
            $resultDo->message = '手机号可以使用';
        }else{
            $resultDo->success = true;
            $resultDo->code = 500;
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
        $company = new model_huiliewang_company();
        $user = new model_pinping_user();

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
        if($timerang > 5*60 ){
            $resultDo->success = false;
            $resultDo->code = 500;
            $resultDo->message = '手机验证码过期';
        }else if( !($session_code == $code)){
            $resultDo->success = false;
            $resultDo->code = 500;
            $resultDo->message = '您的验证码有误';
        }else{
            $arr['regcode'] = intval($code);
            $arr['moblie'] = $tel;
            $arr['usertype'] = 2;
            $arr['passtext'] = $invite;

            $flag = $member->selectOne(['moblie'=>$tel]);
            if(empty($flag)){
                $status = $member->insert($arr);
                $member_id = $member->lastInsertId();
            }else
                $status = false;

            if($status){
                $customer = new model_pinping_customer();
                $customer_data = new model_pinping_customerdata();
                $customer_ins = [
                    'cooperation_code' => '',
                    'name' => $tel,
                    'industry' => '',
                    'hr_company_logo' => '',
                    'short_name' => '',
                    'customer_owner_name' => '',
                    'customer_owner_en_name' => '',
                    'create_time' => time(),
                    'update_time' => 0,
                    'is_deleted' => 0,
                    'is_locked' => 0,
                    'owner_role_id' => 0,
                    'delete_role_id' => 0,
                    'location' => '',
                    'telephone' => $tel,
                    'introduce' => ''
                ];
                $customer->insert($customer_ins);
                $customer_id = $customer->lastInsertId();
                if(!empty($invite)){ //接有邀请码的 company保存BD信息
                    $data = $user->selectOne(['identity'=>1,'invitecode'=>intval($invite)],'role_id,full_name');
                    $arr_com = [
                        'con_oa_userroleid'=>intval($data['role_id']),
                        'con_oa_username'=>$data['full_name'],
                        'tb_customer_id'=>$customer_id,
                        'uid'=>$member_id,
                        'linktel'=>$tel
                    ];
                    $company->insert($arr_com);
                }else{
                    $arr_com = [
                        'tb_customer_id'=>$customer_id,
                        'uid'=>$member_id,
                        'linktel'=>$tel
                    ];
                    $company->insert($arr_com);
                }
                $customer_data_ins = [
                    'customer_id' => $customer_id,
                    'money' => '',
                    'zip' => '',
                    'busstops' => '',
                    'sdate' => '',
                    'website' => '',
                    'scale' => ''
                ];
                $customer_data->insert($customer_data_ins);
                $member->update(['uid'=>$member_id],['tb_customer_id'=>$customer_id]);
                $resultDo->success = true;
                $resultDo->code = 200;
                $resultDo->data = [0=>['uid'=>$member_id]];
                $resultDo->message = '注册成功！';
            }else{
                if($flag){
                    $resultDo->success = true;
                    $resultDo->code = 200;
                    $resultDo->message = '注册成功！';
                }else{
                    $resultDo->success = false;
                    $resultDo->code = 500;
                    $resultDo->message = '注册失败！';
                }
            }
        }
        return $resultDo;
    }
}