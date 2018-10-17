<?php

/**
 * 培训相关接口
 * @author yanghao <yh38615890@sina.cn>
 * @date 07-04-12
 * @copyright (c) gandianli
 */

use com\hlw\ks\interfaces\TrainingServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\training\MaterialResultDTO;
use com\hlw\ks\dataobject\training\FeedBackRequestDTO;
use com\hlw\ks\dataobject\training\MaterialListRequestDTO;
date_default_timezone_set('PRC'); 
class api_TrainingService extends api_Abstract implements TrainingServiceIf 
{
    /**
     * 意见与反馈
     * @access public
     * @param FeedBackRequestDTO $feedBackDo
     * @return ResultDO
     */
    public function feedBack(FeedBackRequestDTO $feedBackDo) 
    {
        $resultDO = new ResultDO();

        $identity_id = $feedBackDo->identity_id ? gdl_lib_BaseUtils::getStr($feedBackDo->identity_id, 'int') : 0;
        $content = $feedBackDo->content ? gdl_lib_BaseUtils::getStr($feedBackDo->content, 'string') : '';

        try {
            $modelFeedBack = new model_newexam_feedback();
            $modelFeedBackImg = new model_newexam_feedbackimg();

            $insert = array(
                'identity_id' => $identity_id,
                'content' => $content,
                'create_time' => date('Y-m-d H:i:s')
            );

            $resId = $modelFeedBack->insert($insert);

            if ($resId) {
                if (!empty($feedBackDo->images)) {
                    //插入学习反馈图片
                    foreach ($feedBackDo->images as $vimg) {
                        $resImg = $modelFeedBackImg->insert(
                                array(
                                    'feedback_id' => $resId,
                                    'image' => $vimg,
                                    'create_time' => date('Y-m-d H:i:s')
                                )
                        );
                    }
                }
            }

            if ($resImg) {
                $resultDO->message = '添加成功';
                $resultDO->code = 1;
            } else {
                $resultDO->code = 0;
            }
            $resultDO->success = true;
            return $resultDO;
        } catch (Exception $e) {
            $resultDO->success = false;
            $resultDO->message = $e->getMessage();
            $resultDO->code = $e->getCode();
        }
        return $resultDO;
    }

    /**
     * 
     * @param type $identity_id
     * @return ResultDO
     */
    public function creditRecord($identity_id)
    {
        $resultDO = new ResultDO();
        $identity_id = gdl_lib_BaseUtils::getStr($identity_id);
        try {
            $modelContrastpractice = new model_newexam_contrastpractice();
            $Contrastpractice = $modelContrastpractice->select("scores>=1 and identity_id='{$identity_id}'", 'question_id,scores,question,practice_time,q_question,name','','order by id desc')->items;
            if ($Contrastpractice) {
                $resultDO->data = $Contrastpractice;
                $resultDO->code = 1;
            } else {
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
