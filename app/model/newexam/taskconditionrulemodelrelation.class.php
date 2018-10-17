<?php
/**
 * 任务规则模块与条件关系表
 * @author yanghao <yh38615890@sina.cn>
 */
class model_newexam_taskconditionrulemodelrelation extends gdl_components_basemodel 
{

    public function primarykey()
    {
        return 'id';
    }

    public function tableName() 
    {
        return 'ex_task_condition_rulemodel_relation';
    }
}
