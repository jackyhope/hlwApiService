<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/2
 * Time: 9:24
 */

class model_pinping_resume extends hlw_components_basemodel
{
    public function primarykey() {
        return 'eid';
    }

    public function tableName() {
        return 'mx_resume';
    }

    /**
     * @desc 获取简历联系信息
     * @param $id
     * @param string $fields
     * @return array
     */
    public function getInfo($id, $fields = '*') {
        return $this->selectOne(['eid' => $id], $fields);
    }
}