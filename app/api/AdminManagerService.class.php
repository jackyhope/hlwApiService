<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-07-13
 * Time: 17:13
 */
use com\hlw\common\dataobject\common\ResultDO;

class api_AdminManagerService extends api_Abstract implements com\hlw\huiliewang\interfaces\AdminManagerServiceIf
{
    //BD列表
    public function bdList()
    {
        // TODO: Implement bdList() method.
        $user = new model_pinping_user();
        $resultDo = new ResultDO();
        $userList = $user -> select(['identity'=>1],'role_id,full_name,invitecode')->items;
        $resultDo->code = 200;
        $resultDo->success = true;
        $resultDo->data = $userList;
        $resultDo->message = '查询成功';
        return $resultDo;
    }
    //查询BD
    public function searBD($name)
    {
        // TODO: Implement searBD() method.
        $user = new model_pinping_user();
        $resultDo = new ResultDO();
        $userList = $user -> select('identity = 1 and full_name like "%'.$name.'%"','role_id,full_name,invitecode')->items;
        $resultDo->code = 200;
        $resultDo->success = true;
        $resultDo->data = $userList;
        $resultDo->message = '查询成功';
        return $resultDo;
    }

    //BD分配
    public function addBD($uid, $time, $rid, $rname)
    {
        // TODO: Implement addBD() method.
        $resultDo = new ResultDO();
        $member = new model_huiliewang_member();
        $company = new model_huiliewang_company();
        $customer = new model_pinping_customer();
        $customerdata = new model_pinping_customerdata();
        $res = $company->update(['uid'=>$uid],['addtime'=>$time,'conid'=>$rid,'con_oa_username'=>$rname]);
        if($res){
            $data = $company->selectOne(['uid'=>$uid],'name');
//            $da = $customer->insert(['customer_owner_id'=>$rid,'owner_role_id'=>$rid,'name'=>$data['username'],'creator_role_id'=>$rid,'origin'=>'线下慧简历','is_locked'=>1,'introduce'=>'adsfa','location'=>'dafsa','crm_vfagxj'=>'dsaf']);
//            $dataid = $customerdata->insert(['customer_id'=>intval($da)]);
            if(!empty($data['name'])){
                $re = $customer->update(['name'=>$data['name']],['customer_owner_id'=>$rid,'owner_role_id'=>$rid,'is_locked'=>1]);
                if($re){
                    $resultDo->code = 200;
                    $resultDo->message = '分配成功';
                    $resultDo->success = true;
                }else{
                    $resultDo->code = 500;
                    $resultDo->message = '分配失败,该BD已被分配';
                    $resultDo->success = false;
                }
            }else{
                $resultDo->code = 500;
                $resultDo->message = '分配失败,需完整客户名称等资料';
                $resultDo->success = false;
            }
        }else{
            $resultDo->code = 500;
            $resultDo->message = '分配失败';
            $resultDo->success = false;
        }
        return $resultDo;
    }

    /**
     * @desc 客户修改手机号
     */
    public function modifyphone($uid, $phone)
    {
        // TODO: Implement modifyphone() method.
        $resultDO = new ResultDO();
        $member = new model_huiliewang_member();
        $customer = new model_pinping_customer();
        $customerr = $member->selectOne(['uid'=>$uid],'tb_customer_id');
        $res = $member->update(['uid'=>$uid],['moblie'=>$phone]);
        if($res){
            $flag = $customer->update(['customer_id'=>intval($customerr['tb_customer_id'])],['telephone'=>$phone]);
        }
        if($flag){
            $resultDO->code = 200;
            $resultDO->success = true;
            $resultDO->message = '修改成功！';
        }else{
            $resultDO->code = 500;
            $resultDO->success = false;
            $resultDO->message = '修改失败！';
        }
        return $resultDO;
    }

    /**
     * @desc  手机号验证
     * @param $uid
     * @param $phone
     * @return ResultDO
     */
    public function checkphone($uid, $phone)
    {
        // TODO: Implement checkphone() method.
        $resultDO = new ResultDO();
        $member = new model_huiliewang_member();
        $bphone = $member->selectOne(['uid'=>$uid],'moblie');
        if($phone == $bphone['moblie']){
            $resultDO->code = 200;
            $resultDO->success = true;
            $resultDO->message = '手机号正确！';
        }else{
            $resultDO->code = 500;
            $resultDO->success = false;
            $resultDO->message = '你的原手机号不正确！';
        }
        return $resultDO;
    }
}