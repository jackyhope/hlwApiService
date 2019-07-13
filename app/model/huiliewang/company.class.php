<?php

/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: company
 * Date: 2019/7/13
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_huiliewang_company extends hlw_components_basemodel
{

    public function primarykey() {
        return 'uid';
    }

    public function tableName() {
        return 'phpyun_company';
    }
}