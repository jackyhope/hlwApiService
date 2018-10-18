<?php
class model_newexam_userlog extends hlw_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_user_log';
    }

}
