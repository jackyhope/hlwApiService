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
    public function addBD($uid, $time, $rid)
    {
        // TODO: Implement addBD() method.
        $resultDo = new ResultDO();
        $member = new model_huiliewang_member();
        $company = new model_huiliewang_company();
        $customer = new model_pinping_customer();
        $customerdata = new model_pinping_customerdata();
        $res = $company->update(['uid'=>$uid],['addtime'=>$time,'conid'=>$rid]);
        if($res){
            $data = $member->selectOne(['uid'=>$uid],'username,email,moblie,address');
            $da = $customer->insert(['customer_owner_id'=>$rid,'owner_role_id'=>$rid,'name'=>$data['username'],'creator_role_id'=>$rid,'origin'=>'线下慧简历','is_locked'=>1,'introduce'=>'adsfa','location'=>'dafsa','crm_vfagxj'=>'dsaf']);
            $dataid = $customerdata->insert(['customer_id'=>intval($da)]);
            if($dataid){
                $resultDo->code = 200;
                $resultDo->message = '分配成功';
                $resultDo->success = true;
            }
        }else{
            $resultDo->code = 500;
            $resultDo->message = '分配失败';
            $resultDo->success = false;
        }
        return $resultDo;
    }
}