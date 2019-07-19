<?php

/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC:  工作经历
 * User: SOSO
 * Date: 2019/7/18
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_pinping_resumework extends hlw_components_basemodel
{
    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'mx_resume_work';
    }
}