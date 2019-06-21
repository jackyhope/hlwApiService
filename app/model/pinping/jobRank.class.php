<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-12
 * Time: 17:17
 */

class model_pinping_jobRank extends hlw_components_basemodel
{
    public function tableName() {
        return 'mx_job_rank'; // TODO: Change the autogenerated stub
    }

    public function ranks() {
        $ranks = [];
        $list = $this->select(['isdelete' => 0]);
        $list = $list->items;
        foreach ($list as $info) {
            $ranks[$info['id']] = $info['name'];
        }
        return $ranks;
    }
}