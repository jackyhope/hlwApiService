<?php
class model_newexam_questype extends hlw_components_basemodel 
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_questype';
    }

	
	public function getLists(){
	
		return $this->select("isdelete=0 and status=1")->items;
	}
}
