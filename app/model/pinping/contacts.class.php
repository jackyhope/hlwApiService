<?php
class model_pinping_contacts extends hlw_components_basemodel
{

    public function primarykey() {
        return 'contacts_id';
    }

    public function tableName() {
        return 'mx_contacts';
    }

}
