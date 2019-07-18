<?php

/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: companycert
 * Date: 2019/7/15
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_huiliewang_companycert extends hlw_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'phpyun_company_cert';
    }
}