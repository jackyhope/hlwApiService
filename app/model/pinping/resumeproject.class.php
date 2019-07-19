<?php
/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 项目经验表
 * User: SOSO
 * Date: 2019/7/18
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_pinping_resumeproject extends hlw_components_basemodel
{
    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'mx_resume_project';
    }
}