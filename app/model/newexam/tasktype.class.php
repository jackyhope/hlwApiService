<?php
/**
 * 任务类型表
 */
class model_newexam_tasktype extends hlw_components_basemodel
{

    public function primarykey()
    {
        return 'id';
    }

    public function tableName() 
    {
        return 'ex_task_type';
    }
}

