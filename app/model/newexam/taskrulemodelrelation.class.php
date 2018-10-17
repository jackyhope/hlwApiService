<?php
/**
 * 任务规则模块与规则关系表
 * @author yanghao <yh38615890@sina.cn>
 */
class model_newexam_taskrulemodelrelation extends gdl_components_basemodel 
{

    public function primarykey()
    {
        return 'id';
    }

    public function tableName() 
    {
        return 'ex_task_rule_model_relation';
    }
}

