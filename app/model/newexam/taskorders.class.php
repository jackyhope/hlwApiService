<?php
class model_newexam_taskorders extends hlw_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_task_orders';
    }

}
