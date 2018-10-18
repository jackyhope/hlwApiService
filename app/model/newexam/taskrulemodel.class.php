<?php
/**
 * 任务规则模块表
 * @author yanghao <yh38615890@sina.cn>
 */
class model_newexam_taskrulemodel extends hlw_components_basemodel 
{

    public function primarykey()
    {
        return 'id';
    }

    public function tableName() 
    {
        return 'ex_task_rule_model';
    }
}

