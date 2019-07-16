<?php

/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC:
 * User:
 * Date: 2019/7/16
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_huiliewang_jobclass extends hlw_components_basemodel
{
    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'phpyun_job_class';
    }
}