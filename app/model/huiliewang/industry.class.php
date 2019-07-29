<?php
/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC:
 * User: SOSO
 * Date: 2019/7/27
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_huiliewang_industry extends hlw_components_basemodel
{
    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'phpyun_industry';
    }
}