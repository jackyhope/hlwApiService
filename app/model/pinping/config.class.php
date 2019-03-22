<?php

class model_pinping_config extends hlw_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'mx_config';
    }

    public function getInfoByName($name = '') {
        $where = ['name' => $name];
        $info = $this->selectOne($where, '', '', ['id' => 'desc']);
        return isset($info['value']) ? $info['value'] : '';
    }

}
