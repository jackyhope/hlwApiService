<?php

use com\hlw\ks\interfaces\CreditSettingServiceIf;
use com\hlw\common\dataobject\common\ResultDO;

class api_CreditSettingService extends api_Abstract implements CreditSettingServiceIf {

    public function CreditSetting($type, $id) {
        $resultDO = new ResultDO();
        $type = hlw_lib_BaseUtils::getStr($type, 'int');
        $id = hlw_lib_BaseUtils::getStr($id, 'int');
        try {
            if ($type == 1) {
                $model = new model_newexam_qbank(); //题库
            } elseif ($type == 2) {
                $model = new model_newexam_studyplan(); //学习中心
            } elseif ($type == 3) {
                $model = new model_newexam_paper(); //试卷
            }
            $list = $model->select("id='{$id}'", 'creditsettingid,scores', '', 'order by id desc')->items;
            if ($list[0]['creditsettingid'] >= 1) {
                $scores = $list[0]['scores'];
            } else {
                $scores = 0;
            }
            if ($list) {
                $resultDO->data = $list;
                $resultDO->message = $scores;
                $resultDO->code = 1;
            } else {
                $resultDO->message = $scores;
                $resultDO->code = 0;
            }
            $resultDO->success = true;
            return $resultDO;
        } catch (Exception $e) {
            $resultDO->success = false;
            $resultDO->code = $e->getCode();
        }
        $resultDO->notify_time = time();
        return $resultDO;
    }

}
