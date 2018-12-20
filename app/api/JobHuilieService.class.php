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

        try {
            $model_userid_job = new model_huiliewang_useridjob();
            $model_company_job = new model_huiliewang_companyjob();
            $userid_job_info = $model_userid_job->selectOne(['huilie_eid' => $boss_resume_id], 'id');
            if (!$userid_job_info['id']) {
                //上传简历
                
                
                $job_info = $model_company_job->selectOne(['id' => $job_id], 'name,uid,com_name');

                $userid_job_ins = [
                    'uid' => 0,
                    'job_id' => $job_id,
                    'job_name' => $job_info['name'],
                    'com_id' => $job_info['uid'],
                    'com_name' => $job_info['com_name'],
                    'eid' => '1384458',
                    'display' => 1,
                    'datetime' => time(),
                    'type' => 1,
                    'is_browse' => 1,
                    'body' => '',
                    'did' => null,
                    'quxiao' => null,
                    'identity' => 3,
                    'resume_id' => '1384458',
                    'recommend_result' => 0,
                    'huilie_eid' => $boss_resume_id
                ];

                $model_userid_job->insert($userid_job_ins);
                
                
            }
        } catch (Exception $ex) {
            
        }
        $resultDo->success = true;
        $resultDo->code = 200;
        $resultDo->message = json_encode($model_userid_job->getDbError());
        return $resultDo;
    }

}
