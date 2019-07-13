<?php

/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 用户
 * Date: 2019/7/13
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_huiliewang_member extends hlw_components_basemodel
{

    public function primarykey() {
        return 'uid';
    }

    public function tableName() {
        return 'phpyun_member';
    }

}