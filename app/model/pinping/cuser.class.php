<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-12
 * Time: 17:17
 */

class model_pinping_cuser extends hlw_components_basemodel
{


    public function primarykey() {
        return 'user_id';
    }

    public function tableName() {
        return 'mx_user';
    }

}