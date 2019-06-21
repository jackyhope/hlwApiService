<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-18
 * Time: 17:15
 */

class model_pinping_jobrank extends hlw_components_basemodel
{
    public function tableName() {
        return 'mx_job_rank'; // TODO: Change the autogenerated stub
    }


    /**
     * @desc 等级列表数据
     * @return array
     */
    public function ranks() {
        $list = $this->select();
        $list = $list->items ? $list->items : [];
        $data = [];
        foreach ($list as $info) {
            $data[$info['id']] = $info['name'];
        }
        return $data;
    }
}