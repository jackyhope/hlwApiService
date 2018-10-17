<?php
/**
 * 用户积分变更
 * @copyright (c) 2017, gandianli
 */
class service_userscores extends gdl_components_baseservice
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 改变用户积分
     * @param $identity_id
     * @param $scores
     * @param string $type
     * @return int
     */
    public function setUserScoresIdentityId($identity_id,$scores=0,$type='+')
    {
        $identity_id = gdl_lib_BaseUtils::getStr($identity_id);
        $model = new model_newexam_usercompany();
        $where = [
            'id' => $identity_id
        ];
        $data =" scores = scores{$type}{$scores}";
        $res = $model->update($where,
            $data
        );
        return $res;
    }

}
