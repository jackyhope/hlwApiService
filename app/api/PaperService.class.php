<?php

use com\hlw\ks\interfaces\PaperServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\paper\PaperDTO;

class api_PaperService extends api_Abstract implements PaperServiceIf
{

    public function getPaperList(PaperDTO $paperDo)
    {
        $result = new ResultDO();
        try {
            $examsPapers= new model_newexam_examspapers();
            if (is_null($paperDo->field))$paperDo->field = '*';
            if (is_null($paperDo->order))$paperDo->order = '';
            $condition = "ex_exams_papers.identity_id='{$paperDo->identity_id}' and ex_exams_papers.ep_type='{$paperDo->ep_type}'";
            if ($paperDo->ep_id) $condition.=" and ex_exams_papers.ep_id='{$paperDo->ep_id}'";
            $res = $examsPapers->select(
                $condition,
                $paperDo->field,
                '',
                $paperDo->order,
                array( 'ex_papers_info' => 'ex_papers_info.id=ex_exams_papers.ep_infoId')
                )->items;

            $result->data = $res;
           // $result->message = json_encode($paperDo);
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
}
