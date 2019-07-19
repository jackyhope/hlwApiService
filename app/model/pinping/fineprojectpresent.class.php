<?php
/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 到场表
 * User: SOSO
 * Date: 2019/7/19
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_pinping_fineprojectpresent extends hlw_components_basemodel
{
    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'mx_fine_project_present';
    }
}