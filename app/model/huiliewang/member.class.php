<?php


class model_huiliewang_member extends hlw_components_basemodel
{

    public function primarykey() {
        return 'uid';
    }

    public function tableName() {
        return 'phpyun_member';
    }

}