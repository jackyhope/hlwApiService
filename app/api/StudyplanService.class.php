<?php

use com\hlw\ks\interfaces\StudyplanServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\studyplan\StudyplanDTO;
use com\hlw\ks\dataobject\studyplan\addStudyplanDTO;
class api_StudyplanService extends api_Abstract implements StudyplanServiceIf
{

    public function getStudyplan(StudyplanDTO $StudyplanDo)
    {
		$offset = $StudyplanDo->offset ? hlw_lib_BaseUtils::getStr($StudyplanDo->offset, 'int') : 0;
		$num = $StudyplanDo->num ? hlw_lib_BaseUtils::getStr($StudyplanDo->num, 'int') : 10;
		$page = $offset*$num;
        $result = new ResultDO();
        try {
            $studyplandb= new model_newexam_studyplan();
            $id = hlw_lib_BaseUtils::getStr($StudyplanDo->id, 'int');
            $inid = hlw_lib_BaseUtils::getStr($StudyplanDo->inid, 'string');
            $admin_reg = hlw_lib_BaseUtils::getStr($StudyplanDo->admin_reg, 'string');
            $where = array(
                'id'=>$id,
                'isdelete'=>0,
                'status'=>1,
                'admin_reg'=>$admin_reg,
            );
            if($id){
                unset($where['admin_reg']);
            }
            if($admin_reg){
                unset($where['id']);
            }
            $res = $studyplandb->select($where,'','','order by id desc limit '.$page.','.$num)->items;
            $result->data = $res;
            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
                $result->message = '无数据';
            }
            $result->success = true;
			$result->message ='order by id desc limit '.$page.','.$num;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        $result->notify_time = time();
        return $result;
    }
    public function addStudyplan(addStudyplanDTO $addStudyplanDo)
    {
        $result = new ResultDO();
        try {
            $studyfeedbackdb= new model_newexam_studyfeedback();
            $study_plan_id = hlw_lib_BaseUtils::getStr($addStudyplanDo->study_plan_id, 'int');
            $content = hlw_lib_BaseUtils::getStr($addStudyplanDo->content, 'string');
            $identity_id = hlw_lib_BaseUtils::getStr($addStudyplanDo->identity_id, 'int');
            $status = hlw_lib_BaseUtils::getStr($addStudyplanDo->status, 'int');
            $title = hlw_lib_BaseUtils::getStr($addStudyplanDo->title, 'string');
            $feedbackid = $addStudyplanDo->feedbackid ? hlw_lib_BaseUtils::getStr($addStudyplanDo->feedbackid, 'int') : '';
            if($feedbackid){
               $condition['id'] = $feedbackid;
               $item = array(
                   'content'=>$content,
                   'title'=>$title ? $title : '',
               );
               $Update = $studyfeedbackdb->update($condition,$item);
               if($Update){
                   $result->code = 1;
                   $result->message = '修改成功';
               }  else {
                   $result->code = 0;
                   $result->message = '修改失败';
               }
               
            }else{
                $res = $studyfeedbackdb->select("status={$status} and identity_id='{$identity_id}' and study_plan_id='{$study_plan_id}'")->items;
                $count = count($res);
                if ($count<5) {
                    $item = array(
                        'study_plan_id'=>$study_plan_id,
                        'content'=>$content,
                        'identity_id'=>$identity_id,
                        'status'=>$status,
                        'title'=>$title ? $title : '',
                        'add_time'=>time(),
                    );
                    $list = $studyfeedbackdb->insert($item);
                    if($list){
                        $count = 4-$count;
                        $result->code = 1;
                        if($status == 1){
                            $result->message = "还可反馈{$count}条";
                        }else{
                            $result->message = "还可添加{$count}条";
                        }
                    }else{
                        $result->code = 0;
                        if($status == 1){
                            $result->message = "反馈失败";
                        }else{
                            $result->message = "添加笔记失败";
                        }
                    }
                } else {
                    $result->code = 0;
                    if($status == 1){
                        $result->message = "最多反馈{$count}条";
                    }else{
                        $result->message = "最多添加{$count}条";
                    }
                }
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }
}
