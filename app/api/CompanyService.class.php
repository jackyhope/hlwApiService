<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-07-16
 * Time: 14:20
 */

use com\hlw\common\dataobject\common\ResultDO;
class api_CompanyService extends api_Abstract implements com\hlw\huiliewang\interfaces\company\CompanyServiceIf
{

    public function checkResume($eid)
    {
        // TODO: Implement checkResume() method.
        $resultDO = new ResultDO();
        $resumeedu = new model_pinping_resumeedu();
        $resumework = new model_pinping_resumework();
        $resume = new model_pinping_resume();
        $basedata = $resume->selectOne(['eid'=>$eid],['name,sex,edu,birthYear,marital_status,hlocation']);

        $str = '请校验完善简历姓名、性别、学历、出生日期、婚姻状况、籍贯等基本信息';
        if(empty($basedata['name'])){
            $resultDO->message = $str;
            $resultDO->code = 500;
            return $resultDO;
        }
        if(empty($basedata['sex'])){
            $resultDO->message = $str;
            $resultDO->code = 500;
            return $resultDO;
        }
        if(empty($basedata['edu'])){
            $resultDO->message = $str;
            $resultDO->code = 500;
            return $resultDO;
        }
        if(empty($basedata['birthYear'])){
            $resultDO->message = $str;
            $resultDO->code = 500;
            return $resultDO;
        }
        if(empty($basedata['hlocation'])){
            $resultDO->message = $str;
            $resultDO->code = 500;
            return $resultDO;
        }

        $edudata = $resumeedu->selectOne(['eid'=>$eid],'starttime');
        if(empty($edudata)){
            $resultDO->code = 500;
            $resultDO->message = '请完善教育经历资料';
            return $resultDO;
        }elseif (empty($edudata['starttime'])){
            $resultDO->code = 500;
            $resultDO->message = '请完善教育经历资料';
            return $resultDO;
        }

        $workdata = $resumework->selectOne(['eid'=>$eid],'starttime');
        if(empty($workdata)){
            $resultDO->code = 500;
            $resultDO->message = '请完善工作经历资料';
            return $resultDO;
        }elseif (empty($workdata['starttime'])){
            $resultDO->code = 500;
            $resultDO->message = '请完善工作经历资料';
            return $resultDO;
        }

        $resultDO->code = 200;
        return $resultDO;

    }

    /**
     * @desc  推荐简历
     * @param $id
     * @return ResultDO
     */
    public function tjProduct($pid)
    {
        // TODO: Implement tjProduct() method.
        $resultDO =  new ResultDO();
        $fineproject = new model_pinping_fineproject();
        $resume = new model_pinping_resume();
        $business = new model_pinping_business();
        $member = new model_huiliewang_member();
        $resumeexpect = new model_huiliewang_resumeexpect();
        //id->com_id   resume_id
        $datafin =  $fineproject->selectOne(['id'=>$pid],'com_id,resume_id,project_id');
        $dataresume = $resume->selectOne(['eid'=>intval($datafin['resume_id'])],'*');
        $datajobid = $business->selectOne(['business_id'=>intval($datafin['project_id'])],'huilie_job_id');
        $datamem = $member->selectOne(['tb_customer_id'=>intval($datafin['com_id'])],'uid');
        if(empty($datamem)){
            $resultDO->code = 500;
            $resultDO->success = false;
            $resultDO->message = '慧猎网没有注册该客户，请不要推荐简历!';
            return $resultDO;
        }
        $arr_resume = [
            'uid'=> intval($datamem['uid']),
            'oa_resumeid'=> intval($dataresume['eid']),
            'oa_fineid' =>$pid,
            'huilie_job_id' =>intval($datajobid['huilie_job_id']),
            'name' => $dataresume['name'],
            'linktel' =>$dataresume['telephone'],
            'email' => $dataresume['email'],
            'industry'=> $dataresume['industry'],
            'sex' => intval($dataresume['sex']),
            'edu' => $dataresume['edu'],
            'location' => $dataresume['location'],
            'wantsalary' =>$dataresume['wantsalary'],
            'cursalary' => $dataresume['curSalary'],
            'birthYear' => intval($dataresume['birthYear']),
            'birthMonth' => intval($dataresume['birthMouth']),
            'curCompany' => $dataresume['curCompany'],
            'curPosition' => $dataresume['curPosition'],
            'curStatus' => $dataresume['curStatus'],
            'intentCity' => $dataresume['intentCity'],
            'evaluate' => $dataresume['evaluate'],
            'skill' => $dataresume['skill'],
            'language' => $dataresume['language'],
            'marital_status' => intval($dataresume['marital_status']),
            'wechat_number' => $dataresume['wechat_number'],
            'wechat_qr' => $dataresume['wechat_qr'],
            'qq_number' => $dataresume['qq_number'],
            'microblog' => $dataresume['microblog'],
            'blood_type' => $dataresume['blood_type'],
            'blood_type_text' => $dataresume['blood_type_text'],
            'linkedin' => $dataresume['linkedin'],
            'job_type' => $dataresume['job_type'],
            'job_type_text' => $dataresume['job_type_text'],
            'now_job_type' => $dataresume['now_job_type'],
            'now_industry' => $dataresume['now_industry'],
            'expect_job_type_text' => $dataresume['expect_job_type_text'],
            'expect_city_text' => $dataresume['expect_city_text'],
            'work_status' => $dataresume['work_status'],
            'work_status_remark' => $dataresume['work_status_remark'],
            'secrecy' => $dataresume['secrecy'],
            'isunited' => intval($dataresume['isunited']),
            'hlocation' => $dataresume['hlocation'],
        ];
        $res = $resumeexpect ->insert($arr_resume);
        if($res){
            $resultDO->success = true;
        }else{
            $resultDO->success = false;
        }
        return $resultDO;
    }

    /**
     * @desc  被推荐的简历列表
     * @param $uid
     * @param $jobid
     * @return ResultDO
     */
    public function productList($uid, $jobid)
    {
        // TODO: Implement productList() method.
        $resultDO = new ResultDO();
        $resumeexpect = new model_huiliewang_resumeexpect();
        $data = $resumeexpect->select(['uid'=>$uid,'huilie_job_id'=>$jobid],'*')->items;
        $resultDO->success = true;
        $resultDO->code = 200;
        $resultDO->data = $data;
        return $resultDO;
    }
}