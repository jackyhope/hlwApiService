<?php

/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 工作职位
 * User: SOSO
 * Date: 2019/7/18
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_pinping_resumeworkposition extends hlw_components_basemodel
{
    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'mx_resume_work_position';
    }
}