<?php
class model_newexam_knows extends gdl_components_basemodel 
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_knows';
    }

	
	public function getLists($admin_reg=''){
		
		return $this->select("admin_reg='".$admin_reg."' and isdelete=0 and k_status=1")->items;
	}
	
}
