<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-18
 * Time: 17:15
 */

class model_pinping_achievement extends hlw_components_basemodel
{
    public function tableName() {
        return 'mx_achievement'; // TODO: Change the autogenerated stub
    }

    /**
     * @desc 等级列表数据
     * @return array
     */
    public function lists($moth) {
        !$moth && $moth = date('Y-m', time());
        $moth = strtotime($moth);
        $end = strtotime("+1 month", $moth);
        $list = $this->select("addtime > {$moth} and addtime < $end");
        $list = $list->items ? $list->items : [];
        $data = [];
        foreach ($list as $info) {
            $userId = $info['user_id'];
            $data[$userId] += $info['integral'];
        }
        return $data;
    }
}