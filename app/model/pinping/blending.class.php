<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/2
 * Time: 9:24
 */

class model_pinping_blending extends hlw_components_basemodel
{
    public function primarykey() {
        return 'fine_id';
    }

    public function tableName() {
        return 'mx_resume_blending';
    }

    /**
     * @desc 获取简历联系信息
     * @param $id
     * @param string $fields
     * @return array
     */
    public function getInfo($id, $fields = '*') {
        if(!is_array($id)){
            $id = ['fine_id' => $id];
        }
        return $this->selectOne($id, $fields);
    }
}