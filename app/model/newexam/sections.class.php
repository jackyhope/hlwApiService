<?php
class model_newexam_sections extends gdl_components_basemodel 
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_sections';
    }

}
