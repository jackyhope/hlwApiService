<?php

/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC:  公司订单消费记录
 * User: SOSO
 * Date: 2019/7/24
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_huiliewang_companypay extends hlw_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'phpyun_company_pay';
    }

}