<?php
/**
 * 任务规则表
 * @author yanghao <yh38615890@sina.cn>
 */
class model_newexam_taskrule extends gdl_components_basemodel 
{

    public function primarykey()
    {
        return 'id';
    }

    public function tableName() 
    {
        return 'ex_task_rule';
    }
}

