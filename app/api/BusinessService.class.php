<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-10
 * Time: 18:57
 */
use com\hlw\huiliewang\interfaces\BusinessServiceIf;
use \com\hlw\huiliewang\dataobject\business\businessRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_BusinessService extends api_Abstract implements  BusinessServiceIf
{
    public function searchCallist( businessRequestDTO $businessDo)
    {
        // TODO: Implement searchCallist() method.
        $resultDo = new ResultDO();
        $id = $businessDo->id;
        $name = $businessDo->name;
        if(!$id && !$name){
            $resultDo->success = true;
            $resultDo->code = 500;
            $resultDo->message = '缺少必传参数';
            return $resultDo;
        }

        $fine_model = new model_pinping_fineproject();
        $resume_model = new model_pinping_resume();
        $projectstatus_model = new model_pinping_projectstatus();
        $user_model = new model_pinping_user();
        $business_model = new model_pinping_business();
        $customer_model = new model_pinping_customer();
        $role_model = new  model_pinping_role();
        $position_model = new model_pinping_position();
        $roleDepartment_model = new  model_pinping_roleDepartment();
        if(!empty($name)){
            $eid = $resume_model->select(['name'=>$name],'eid')->items;
            $eid_arr = array();
            foreach ($eid as $k=>$v){
                $eid_arr[$k] = intval($v['eid']);
            }
            $eid_arr = implode(',',$eid_arr);
            $data = $fine_model->select('status = 1 and project_id = '.$id.' and resume_id in ('.$eid_arr.')','','','order by addtime desc')->items;
        }else{
             $data = $fine_model->select('status = 1 and project_id = '.$id,'','','order by addtime desc')->items;
        }
        foreach ($data as $k=>$v){
            $t = $projectstatus_model->selectOne(['status_id'=>intval($v['status'])],'status_name,status');
            $data[$k] = array_merge($data[$k],$t);
        }
        foreach ($data as $k=>$v){
            $t = $user_model->selectOne(['role_id'=>intval($v['tracker'])],'user_id,full_name as tracker_name,thumb_path');
            $data[$k] = array_merge($data[$k],$t);
        }
        foreach ( $data as $k=>$v) {
            $t = $business_model->selectOne(['business_id'=>intval($v['project_id'])],'business_id,industry,name as business_name');
            $data[$k] = array_merge($data[$k],$t);
        }
        foreach ( $data as $k=>$v){
            $t = $customer_model->selectOne(['customer_id'=>intval($v['com_id'])],'customer_id,name as customer_name');
            $data[$k] = array_merge($data[$k],$t);
        }
        foreach ( $data as $k=>$v){
            $t = $resume_model->selectOne(['eid'=>intval($v['resume_id'])],'eid,name,creator_role_id,curCompany as current_company,curDepartment as current_department,curPosition as current_job,sex,curSalary,wantsalary as hope_salary,telephone,edu,job_class');
            $data[$k] = array_merge($data[$k],$t);
        }

        foreach ( $data as $k=>$v){
            $t = $role_model->selectOne(['role_id'=>intval($v['tracker'])],'position_id');
            $data[$k] = array_merge($data[$k],$t);
        }
        foreach ($data as $k=>$v){
            $t = $position_model->selectOne(['position_id'=>intval($v['position_id'])],'department_id');
            $data[$k] = array_merge($data[$k],$t);
        }
        foreach ( $data as $k=>$v){
            $t= $roleDepartment_model->selectOne(['department_id'=>intval($v['department_id'])],'name as department_name');
            $data[$k] = array_merge($data[$k],$t);
        }
        $resultDo->success = true;
        $resultDo->code = 200;
        $resultDo->data = $data;
        return $resultDo;

    }
    public function getCCArray($eid)
    {
        // TODO: Implement getCCArray() method.
    }
}