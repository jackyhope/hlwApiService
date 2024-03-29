<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-12
 * Time: 17:37
 */

class model_pinping_position extends hlw_components_basemodel
{
    public function tableName() {
        return 'mx_position'; // TODO: Change the autogenerated stub
    }

    /**
     * @desc 获取职位IDS
     * @param $where
     * @return array
     */
    public function positionIds($where) {
        if (!$where) {
            return [];
        }
        $list = $this->select("department_id in ({$where})");
        $positions = isset($list->items) ? $list->items : [];
        $positionIds = [];
        foreach ($positions as $info) {
            array_push($positionIds, $info['position_id']);
        }
        return $positionIds;
    }

    /**
     * @desc 职位部门列表
     * @return array
     */
    public function positionDeparts(){
        $list = $this->select();
        $positions = isset($list->items) ? $list->items : [];
        $return = [];
        foreach ($positions as $info){
            $return[$info['position_id']] = $info['department_id'];
        }
        return $return;
    }
}