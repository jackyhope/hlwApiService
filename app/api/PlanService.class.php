<?php
use com\hlw\ks\interfaces\PlanServiceIf;
use com\hlw\ks\dataobject\plan\IntroduceResultDTO;

class api_PlanService extends api_Abstract implements PlanServiceIf
{

    public function introduce($planId)
    {
        $introduceResultDTO = new IntroduceResultDTO();
        $planId = hlw_lib_BaseUtils::getStr($planId);
        try {
            $modelPlan = new model_newexam_plan();
            $res = $modelPlan->selectOne(array("id=$planId"), 'id,name,introduce', '', 'order by id desc');
            if ($res) {
                $introduceResultDTO->data = $res;
                $introduceResultDTO->code = 1;
            } else {
                $introduceResultDTO->code = 0;
            }
            $introduceResultDTO->success = true;
            return $introduceResultDTO;
        } catch (Exception $e) {
            $introduceResultDTO->success = false;
            $introduceResultDTO->code = $e->getCode();
        }
        $introduceResultDTO->notify_time = time();
        return $introduceResultDTO;
    }

}
