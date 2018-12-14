<?php
class model_pinping_customerdata extends hlw_components_basemodel
{

    public function primarykey() {
        return 'customer_id';
    }

    public function tableName() {
        return 'mx_customer_data';
    }

}


