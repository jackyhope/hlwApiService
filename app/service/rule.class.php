<?php
/**
 * 任务规则service
 * @author yanghao <yh38615890@sina.cn>
 * @copyright (c) 2017, gandianli
 */
class service_rule extends hlw_components_baseservice
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 取得规则模块
     */
    public function getRuleModelByConditionId($conditionId)
    {
        $conditionId = hlw_lib_BaseUtils::getStr($conditionId);
        $model = new model_newexam_taskconditionrulemodelrelation();
        if (is_array($conditionId)){ // 如果是条件ID数组
            $conditionId = array_unique($conditionId);
            $coids = implode(',',$conditionId);
            $where = "  ex_task_condition_rulemodel_relation.condition_id in ({$coids}) ";

        }else{
            $where = [
                'ex_task_condition_rulemodel_relation.condition_id' => $conditionId
            ];
        }

        $leftJoin = [
            'ex_task_condition as rs ' => 'rs.id=ex_task_condition_rulemodel_relation.condition_id',
            'ex_task_rule_model as rm ' => 'rm.id=ex_task_condition_rulemodel_relation.rulemodel_id',
        ];
        $res = $model->select($where,
            'rm.rule,ex_task_condition_rulemodel_relation.condition_id,rs.parm ',
            '',
            '',
            $leftJoin
        )->items;
        foreach ($res as &$values){
            $values['rule'] = unserialize($values['rule']);
        }
        return $res;
    }

    /**
     * 任务规则处理中心
     * @param $str / 任务数组
     * @param $data / 用户数据
     * @param int $type 1试卷 2题库 3文档 4视频
     * @return array
     */
    public function deModelRule($str,$data,$type=1){
        $isfins = [];
//        return $str;
        foreach ($str as $rr){//遍历所有条件规则
            $rule  = $rr['rule'];
            $parmid  = $rr['parm'];
            $rule  = array_values($rule);
            $rule_num = count($rule);
            $rs = 0;
            $small_rule = [];
            foreach ($rule as $rk =>$r) {//遍历一个条件的完成规则
                $small_r = explode(':',$r);//解析小规则
                if ($type==1){
                    $small_rule['paperid'] = $parmid;
                }else if ($type==2){
                    $small_rule['qbankid'] = $parmid;
                }
                $small_rule[$small_r[0]] = $small_r[1];
            }

            foreach ($small_rule as $rk =>$r){
                if ($type==1){
                        switch ($rk){
                            case 'paperid';//是否通过
                                if ($data['paperid']==$parmid){
                                    $rs ++;
                                }
                                break;
                            case 'ispass';//是否通过
                                if ($data['ispass']==$r){
                                    $rs ++;
                                }
                                break;
                            case 'pass_num';//通过数量
                                if ($data['pass_num']>=$r){
                                    $rs ++;
                                }
                                break;
                            case 'accuracy';//正确率 %
                                if ($data['accuracy']>=$r){
                                    $rs ++;
                                }
                                break;
                            case 'nopass_num';//未通过数量
                                if ($data['nopass_num']<=$r){
                                    $rs ++;
                                }
                                break;
                                break;
                            case 'do_time';//
                                if ($data['do_time']<=$r){
                                    $rs ++;
                                }
                                break;
                            default:
                    }

                }elseif ($type=2){//
                    switch ($rk){
                        case 'qbankid';//是否通过
                            if ($data['qbankid']==$parmid){
                                $rs ++;
                            }
                            break;
                        case 'num';//做题数量
                            if ($data['num']>=$r){
                                $rs ++;
                            }
                            break;
                        case 'pass_num';//做题数量
                            if ($data['pass_num']>=$r){
                                $rs ++;
                            }
                            break;
                        case 'nopass_num';//做题数量
                            if ($data['nopass_num']<=$r){
                                $rs ++;
                            }
                            break;
                        case 'accuracy';//正确率 %
                            if ($data['accuracy']>=$r){
                                $rs ++;
                            }
                            break;
                        case 'do_time';//总用时
                            if ($data['do_time']<=$r){
                                $rs ++;
                            }
                            break;
                        default:
                    }
                }
            }

            if ($rs>=($rule_num+1)){
                $isfins[$rr['condition_id']] = 1;
            }else{
                $isfins[$rr['condition_id']] = 0;
            }
        }
        return $isfins;
    }
}
