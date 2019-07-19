<?php

/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 简历不合适表
 * User: SOSO
 * Date: 2019/7/19
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_pinping_fineprojectbhs extends hlw_components_basemodel
{
    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'mx_fine_project_bhs';
    }
}