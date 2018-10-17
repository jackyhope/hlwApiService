<?php
/**
 * 用户积分变更
 * @copyright (c) 2017, gandianli
 */
class service_plaform extends gdl_components_baseservice
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 读取根平台\
     * @param $parentid 
     * @return int
     */
    public function getTopPlaformIdbyId($parentid)
    {
        while ($parentid != 0){
            $model = new model_newexam_plaform();
            $where = ['id' => $parentid];
            $items = 'id,parentid,`real`';
            $res = $model->selectOne($where,$items);
            $parentid = $res['parentid'];
        }
        
        return ['id' => $res['id'] , 'real' => $res['real']];
    }

}