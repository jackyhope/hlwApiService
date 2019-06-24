<?php
class model_pinping_invoice extends hlw_components_basemodel
{

    public function primarykey() {
        return 'invoice_id';
    }

    public function tableName() {
        return 'mx_invoice';
    }

}

