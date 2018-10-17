<?php
class model_newexam_taskuser extends gdl_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_task_user';
    }

}
