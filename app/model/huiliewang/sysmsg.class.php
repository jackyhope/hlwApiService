<?php

/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 站内信
 * User: SOSO
 * Date: 2019/7/12
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
class model_huiliewang_sysmsg extends hlw_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'phpyun_sysmsg';
    }

    /**
     * @desc  发送消息
     * @param $data
     * @return bool|int
     */
    public function sent($data) {
        if(!$data){
            return false;
        }
        return $this->insert($data);
    }
}
