<?php
class model_pinping_businessstatus extends hlw_components_basemodel
{

    public function primarykey() {
        return 'status_id';
    }

    public function tableName() {
        return 'mx_business_status';
    }

}
