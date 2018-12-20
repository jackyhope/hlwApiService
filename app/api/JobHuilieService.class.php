<?php

use com\hlw\huiliewang\interfaces\JobHuilieServiceIf;
use com\hlw\huiliewang\dataobject\job\JobResumesRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_JobHuilieService extends api_Abstract implements JobHuilieServiceIf {

    public function saveJobResumes(JobResumesRequestDTO $jobresumesDo) {
        $resultDo = new ResultDO();

        if (!$jobresumesDo->job_id) {
            $resultDo->success = true;
            $resultDo->code = 500;
            $resultDo->message = '缺少job_id';
        }

        if (!$jobresumesDo->boss_resume_id) {
            $resultDo->success = true;
            $resultDo->code = 500;
            $resultDo->message = '缺少boss简历ID';
        }
        $job_id = hlw_lib_BaseUtils::getStr($jobresumesDo->job_id, 'int');
        $boss_resume_id = hlw_lib_BaseUtils::getStr($jobresumesDo->boss_resume_id, 'int');

        $resume = $jobresumesDo->resume;
        $resume_data = $jobresumesDo->resume_data;
        $resume_project = $jobresumesDo->resume_project;
        $resume_edu = $jobresumesDo->resume_edu;
        $resume_work = $jobresumesDo->resume_work;

        $huilie_resume_project = [];
        //处理项目
        foreach ($resume_project as $krp => $rp) {
            $huilie_resume_project[$krp]['startDateStr'] = $rp['starttime'];
            $huilie_resume_project[$krp]['endDateStr'] = $rp['endtime'];
            $huilie_resume_project[$krp]['proName'] = $rp['proName'];
            $huilie_resume_project[$krp]['proDes'] = $rp['proDes'];
            $huilie_resume_project[$krp]['projectOffice'] = $rp['proOffice'];
            $huilie_resume_project[$krp]['subCompany'] = $rp['proCompany'];
            $huilie_resume_project[$krp]['projectRole'] = $rp['responsibility'];
            $huilie_resume_project[$krp]['projectPerfromnance'] = $rp['performance'];
        }
        $huilie_resume_project = serialize($huilie_resume_project);

        $huilie_resume_edu = [];
        //处理学历
        foreach ($resume_edu as $kre => $re) {
            $huilie_resume_edu[$kre]['startDateStr'] = $re['starttime'];
            $huilie_resume_edu[$kre]['endDateStr'] = $re['endtime'];
            $huilie_resume_edu[$kre]['schoolName'] = $re['schoolName'];
            $huilie_resume_edu[$kre]['majorName'] = $re['majorName'];
        }
        $huilie_resume_edu = serialize($huilie_resume_edu);


        $huilie_resume_work = [];
        //处理工作经历
        foreach ($resume_work as $krw => $rw) {
            $huilie_resume_work[$krw]['startDateStr'] = $rw['starttime'];
            $huilie_resume_work[$krw]['endDateStr'] = $rw['endtime'];
            $huilie_resume_work[$krw]['companyName'] = $rw['company'];
            $huilie_resume_work[$krw]['posName'] = $rw['jobPosition'];
            $huilie_resume_work[$krw]['workDes'] = $rw['duty'];
            $huilie_resume_work[$krw]['companyDes'] = $rw['companyDes'];
        }
        $huilie_resume_work = serialize($huilie_resume_work);

        try {
            $model_userid_job = new model_huiliewang_useridjob();
            $model_company_job = new model_huiliewang_companyjob();
            $model_resume = new model_resume_resume();
            $userid_job_info = $model_userid_job->selectOne(['huilie_eid' => $boss_resume_id, 'job_id' => $job_id], 'id');

            if (!$userid_job_info['id']) {
                //上传简历
                $resume_ins = [
                    'uid' => 0,
                    'name' => $resume['name'],
                    'telphone' => $resume['telephone'],
                    'telhome' => '',
                    'photo' => '',
                    'email' => $resume['email'],
                    'current_company' => $resume['curCompany'],
                    'current_job' => $resume['curPosition'],
                    'description' => $resume_data['evaluate'],
                    'addtime' => time(),
                    'lastupdate' => time(),
                    'tag' => $resume['label'],
                    'project_content' => $huilie_resume_project,
                    'edu_content' => $huilie_resume_edu,
                    'work_content' => $huilie_resume_work,
                    'training' => '',
                    'skill' => '',
                    'other' => '',
                    'attach_info' => $resume['expect_job_type_text'],
                    'moblie_status' => 0,
                    'email_status' => 0,
                    'idcard_status' => 0,
                    'hits' => 0,
                    'status' => 0,
                    'integrity' => 0,
                    'marriage' => $resume['marital_status'],
                    'domicile' => 0,
                    'qq' => $resume['qq_number'],
                    'wxewm' => $resume['wechat_qr'],
                    'homepage' => $resume['microblog'],
                    'basic_info' => 0,
                    'r_status' => 1,
                    'birthday' => 0,
                    'edu' => 0,
                    'sex' => $resume['sex']
                ];
                $model_resume->insert($resume_ins);
                $resume_id = $model_resume->lastInsertId();

                $job_info = $model_company_job->selectOne(['id' => $job_id], 'name,uid,com_name');

                $userid_job_ins = [
                    'uid' => 0,
                    'job_id' => $job_id,
                    'job_name' => $job_info['name'],
                    'com_id' => $job_info['uid'],
                    'com_name' => $job_info['com_name'],
                    'eid' => $resume_id,
                    'display' => 1,
                    'datetime' => time(),
                    'type' => 1,
                    'is_browse' => 1,
                    'body' => '',
                    'did' => null,
                    'quxiao' => null,
                    'identity' => 3,
                    'resume_id' => $resume_id,
                    'recommend_result' => 0,
                    'huilie_eid' => $boss_resume_id
                ];

                $model_userid_job->insert($userid_job_ins);
            }
        } catch (Exception $ex) {
            
        }
        $resultDo->success = true;
        $resultDo->code = 200;
        $resultDo->message = json_encode();
        return $resultDo;
    }

}
