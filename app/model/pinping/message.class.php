<?php

/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 系统消息
 * User: SOSO
 * Date: 2019/7/18
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_pinping_message extends hlw_components_basemodel
{
    public function primarykey() {
        return 'message_id';
    }

    public function tableName() {
        return 'mx_message';
    }
}