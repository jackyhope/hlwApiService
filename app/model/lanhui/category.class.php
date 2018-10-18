<?php
class model_lanhui_category extends hlw_components_basemodel
{
    public function primarykey()
    {
        return 'catid';
    }

    public function tableName()
    {
        return 'hlw_category';
    }
}