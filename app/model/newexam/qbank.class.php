<?php
class model_newexam_qbank extends hlw_components_basemodel 
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_qbank';
    }

	
	public function getInfo($id){
		$result = $this->selectOne('id='.$id);
		return $result;
	}
	
	
	
}
