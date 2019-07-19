<?php
/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC:
 * User: SOSO
 * Date: 2019/7/19
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_pinping_jobclass extends hlw_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'mx_job_class';
    }
}