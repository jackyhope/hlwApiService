<?php

/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 电信消息记录
 * User:
 * Date: 2019/7/13
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_huiliewang_mobilemsg extends hlw_components_basemodel
{
    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'phpyun_moblie_msg';
    }
}