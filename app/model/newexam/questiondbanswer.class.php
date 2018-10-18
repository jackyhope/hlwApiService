<?php
class model_newexam_questiondbanswer extends hlw_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_questiondb_answer';
    }

}
