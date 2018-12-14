<?php
class model_pinping_customer extends hlw_components_basemodel
{

    public function primarykey() {
        return 'customer_id';
    }

    public function tableName() {
        return 'mx_customer';
    }

}

