<?php
class model_newexam_signcheck extends gdl_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_sign_check';
    }

}
