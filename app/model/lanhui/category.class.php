<?php
class model_lanhui_category extends gdl_components_basemodel
{
    public function primarykey()
    {
        return 'catid';
    }

    public function tableName()
    {
        return 'gdl_category';
    }
}