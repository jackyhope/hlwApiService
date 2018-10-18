<?php
/**
 * 任务规则service
 * @author yanghao <yh38615890@sina.cn>
 * @copyright (c) 2017, gandianli
 */
class service_reward extends hlw_components_baseservice
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 奖励
     */
    public function getRewardByConditionId($conditionId)
    {
        $conditionId = hlw_lib_BaseUtils::getStr($conditionId);
        $model = new model_newexam_taskreward();
        if (is_array($conditionId)){ // 如果是条件ID数组
            $conditionId = array_unique($conditionId);
            $coids = implode(',',$conditionId);
            $where = "  rm.condition_id in ({$coids}) ";

        }else{
            $where = [
                'rm.condition_id' => $conditionId
            ];
        }

        $leftJoin = [
            'ex_task_condition_reward_relation as rm ' => 'rm.reward_id=ex_task_reward.id'
        ];
        $res = $model->select($where,
            'ex_task_reward.reward_type,ex_task_reward.reward_parm,rm.condition_id ',
            '',
            '',
            $leftJoin
        )->items;

        return $res;
    }

}
