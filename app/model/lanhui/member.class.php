<?php

class model_lanhui_member extends gdl_components_basemodel
{
    public function primarykey()
    {
        return 'userid';
    }

    public function tableName()
    {
        return 'gdl_member';
    }

    function content_table($moduleid, $itemid, $split, $table_data = '') {
	if($split) {
		return split_table($moduleid, $itemid);
	} else {
		$table_data or $table_data = get_table($moduleid, 1);
		return $table_data;
	}
}

}
