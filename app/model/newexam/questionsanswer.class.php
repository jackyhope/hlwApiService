<?php
class model_newexam_questionsanswer extends hlw_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_questions_answer';
    }

}
