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
        $eid = $businessDo->eid;
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
            if($eid)
                $data = $fine_model->select('status = 1 and project_id = '.$id.' and resume_id = '.$eid,'','','order by addtime desc')->items;
            else
                $data = $fine_model->select('status = 1 and project_id = '.$id,'','','order by addtime desc')->items;
        }
        $status_arr = array();
        $tracker_arr = array();
        $projectId_arr = array();
        $comId_arr = array();
        $resumeId_arr = array();
        $roleId_arr = array();
        $positionId_arr = array();
        $departmentId_arr = array();
        foreach ($data as $k=>$v){
            $status_arr[] = $v['status'];
            $tracker_arr[] = $v['tracker'];
            $projectId_arr[] = $v['project_id'];
            $comId_arr[] = $v['com_id'];
            $resumeId_arr[] = $v['resume_id'];
            $roleId_arr[] = $v['tracker'];
            $positionId_arr[] = $v['position_id'];
            $departmentId_arr[] = $v['department_id'];
        }
        $status = implode(',',$status_arr);
        $tacker = implode(',',$tracker_arr);
        $projectId = implode(',',$projectId_arr);
        $comId = implode(',',$comId_arr);
        $resumeId = implode(',',$resumeId_arr);
        $roleId = implode(',',$roleId_arr);
        $positionId = implode(',',$positionId_arr);
        $departmentId = implode(',',$departmentId_arr);
        $data_status = $projectstatus_model->select('status_id in ('.$status.')','status_id,status_name,status')->items;
        $data_trcaker = $user_model->select('role_id in ('.$tacker.')','role_id,user_id,full_name as tracker_name,thumb_path')->items;
        $data_projectId = $business_model->select('business_id in ('.$projectId.')','business_id,industry,name as business_name')->items;
        $data_com = $customer_model->select('customer_id in ('.$comId.')','customer_id,name as customer_name')->items;
        $data_resume = $resume_model->select('eid in ('.$resumeId.')','eid,name,creator_role_id,curCompany as current_company,curDepartment as current_department,curPosition as current_job,sex,curSalary,wantsalary as hope_salary,telephone,edu,job_class')->items;
        $data_role = $role_model->select('role_id in ('.$roleId.')','role_id,position_id')->items;
        $data_position = $position_model->select('position_id in ('.$positionId.')','position_id,department_id')->items;
        $data_department = $roleDepartment_model->select('department_id in ('.$departmentId.')','department_id,name as department_name')->items;
        $data_merge = array();
        $data_merge = array_merge($data_merge,$data_status);
        foreach ($data as $key=>$val){
            foreach ($data_merge as $k=>$v){
                    if($val['status'] == $v['status_id'])
                        $data[$key] = array_merge($data[$key],$v);
            }
            foreach ($data_trcaker as $k=>$v){
                if($val['tracker'] == $v['role_id'])
                    $data[$key] = array_merge($data[$key],$v);
            }
            foreach ($data_projectId as $k=>$v){
                if($val['project_id'] == $v['business_id'])
                    $data[$key] = array_merge($data[$key],$v);
            }
            foreach ($data_com as $k=>$v){
                if($val['com_id'] == $v['customer_id'])
                    $data[$key] = array_merge($data[$key],$v);
            }
            foreach ($data_resume as $k=>$v){
                if($val['resume_id'] == $v['eid'])
                    $data[$key] = array_merge($data[$key],$v);
            }
            foreach ($data_role as $k=>$v){
                if($val['tracker'] == $v['role_id'])
                    $data[$key] = array_merge($data[$key],$v);
            }
            foreach ($data_position as $k=>$v){
                if($val['position_id'] == $v['position_id'])
                    $data[$key] = array_merge($data[$key],$v);
            }
            foreach ($data_department as $k=>$v){
                if($val['department_id'] == $v['department_id'])
                    $data[$key] = array_merge($data[$key],$v);
            }
        }
//        foreach ($data as $k=>$v){
//            $t = $projectstatus_model->selectOne(['status_id'=>intval($v['status'])],'status_name,status');
//            $data[$k] = array_merge($data[$k],$t);
//        }111
//        foreach ($data as $k=>$v){
//            $t = $user_model->selectOne(['role_id'=>intval($v['tracker'])],'user_id,full_name as tracker_name,thumb_path');
//            $data[$k] = array_merge($data[$k],$t);
//        }111
//        foreach ( $data as $k=>$v) {
//            $t = $business_model->selectOne(['business_id'=>intval($v['project_id'])],'business_id,industry,name as business_name');
//            $data[$k] = array_merge($data[$k],$t);
//        }111
//        foreach ( $data as $k=>$v){
//            $t = $customer_model->selectOne(['customer_id'=>intval($v['com_id'])],'customer_id,name as customer_name');
//            $data[$k] = array_merge($data[$k],$t);
//        }111
//        foreach ( $data as $k=>$v){
//            $t = $resume_model->selectOne(['eid'=>intval($v['resume_id'])],'eid,name,creator_role_id,curCompany as current_company,curDepartment as current_department,curPosition as current_job,sex,curSalary,wantsalary as hope_salary,telephone,edu,job_class');
//            $data[$k] = array_merge($data[$k],$t);
//        }111
//
//        foreach ( $data as $k=>$v){
//            $t = $role_model->selectOne(['role_id'=>intval($v['tracker'])],'position_id');
//            $data[$k] = array_merge($data[$k],$t);
//        }111
//        foreach ($data as $k=>$v){
//            $t = $position_model->selectOne(['position_id'=>intval($v['position_id'])],'department_id');
//            $data[$k] = array_merge($data[$k],$t);
//        }111
//        foreach ( $data as $k=>$v){
//            $t= $roleDepartment_model->selectOne(['department_id'=>intval($v['department_id'])],'name as department_name');
//            $data[$k] = array_merge($data[$k],$t);
//        }
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