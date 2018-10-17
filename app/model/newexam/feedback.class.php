<?php
class model_newexam_feedback extends gdl_components_basemodel 
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_feedback';
    }

}
