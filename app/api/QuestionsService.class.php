<?php

use com\hlw\ks\interfaces\QuestionsServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\questions\QuestionsDTO;
use com\hlw\ks\dataobject\questions\AnswerResuiltDTO;

class api_QuestionsService extends api_Abstract implements QuestionsServiceIf 
{

    public function getQuestionsInfoByIds(QuestionsDTO $questionsDo)
    {
        $result = new ResultDO();
        try {
            $modelQuestions = new model_newexam_questions();
            $field = $questionsDo->field ? $questionsDo->field : '*';
            $res = @$modelQuestions->select('id in (' . $questionsDo->ids . ') ', $field, '', 'order by id desc')->items;
            $result->data = $res;
            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        $result->notify_time = time();
        return $result;
    }

    /* 统计试题数量 */

    public function countQuestion(QuestionsDTO $userDo) 
    {
        $result = new ResultDO();
        //根据题库ID，知识点ID统计试题数量
        try {


            $obModelQbank = new model_newexam_qbank();
            $obModelQues = new model_newexam_questions();
            $obModelQuesDb = new model_newexam_questiondb();
            $obModelKnows = new model_newexam_knows();
            $obModelQuesType = new model_newexam_questype();
            //获取题库配置
            $qbankConf = $obModelQbank->getInfo($userDo->qbankid);
            $qbankid = $userDo->qbankid ? $userDo->qbankid : 0;
            $knowsId = $userDo->knowsid ? $userDo->knowsid : 0;
            $typeId = $userDo->typeId ? $userDo->typeid : 0;

            $sqlWhere = 'q_qbankid = ' . $qbankid;
            if (!empty($knowsId))
                $sqlWhere .= ' and q_knowsid = ' . $knowsId;
            if (!empty($typeId))
                $sqlWhere .= ' and q_typeid = ' . $typeId;

            //if(!empty($knowsId)) $groupBy = 'GROUP BY q_knowsid';
            //if(!empty($typeId)) $groupBy = 'GROUP BY q_typeid';
            if (empty($groupBy))
                $groupBy = 'GROUP BY q_knowsid,q_typeid';


            //if(!empty($knowsId)) $field = 'q_knowsid,count(q_knowsid) as total';
            //if(!empty($typeId)) $field = 'q_typeid,count(q_typeid) as total';
            if (empty($field))
                $field = 'q_knowsid,q_typeid,count(q_knowsid) as total';



            //统计知识点对应数量
            $o = $qbankConf['parentid'] <= 0 ? $obModelQues : $obModelQuesDb;
            $res = $o->select($sqlWhere, $field, $groupBy)->items;


            //if($userDo->backType == 1)
            //$obModelKnows->	select('','','')->items;
            //获取知识点信息
            $knows = $obModelKnows->getLists($qbankConf['admin_reg']);
            foreach ($knows as $val) {
                $knowsTemp[$val['id']] = $val['k_name'];
            }

            //获取题型		

            $quesTypes = $obModelQuesType->getLists();
            foreach ($quesTypes as $val) {
                $quesTypesTemp[$val['id']] = $val['type'];
            }



            //对统计结果进行数据转换	
            foreach ($res as $key => $val) {
                $val['k_name'] = $knowsTemp[$val['q_knowsid']];
                $val['type'] = $quesTypesTemp[$val['q_typeid']];

                $resultDataTemp[] = $val;
            }

			

            $result->data = $resultDataTemp;
            $result->code = empty($resultDataTemp) ? 0 : 1;
            $result->success = true;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }

        return $result;
    }

    /**
     * 根据题目ID取得答案
     * @param type $questionId
     * @param type $qtype
     * @return AnswerResuiltDTO
     */
    public function getAnswerByQuestionId($questionId, $qtype)
    {
        $result = new AnswerResuiltDTO();
        $data = array();
        try {
            $qtype = hlw_lib_BaseUtils::getStr($qtype);
            $questionId = hlw_lib_BaseUtils::getStr($questionId);

            $modelQuestionsAnswer = $qtype == 1 ? new model_newexam_questionsanswer() : new model_newexam_questiondbanswer();
            $condition['questions_id'] = $questionId;
            $items = '`option`,`answer`';

            $res = $modelQuestionsAnswer->select($condition, $items,'','order by `option` asc')->items;
            foreach ($res as $r){
                $data[$r['option']] = "{$r['option']}.{$r['answer']}";
            }
            $result->questionId = $questionId;
            if ($res) {
                $result->answers = $data;
            } else {
                $result->answers = null;
            }
            return $result;
        } catch (Exception $e) {
            $result->questionId = 0;
            $result->answers = NULL;
        }
        return $result;
    }

}
