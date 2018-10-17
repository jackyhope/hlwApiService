<?php
class model_newexam_userspecialty extends gdl_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_user_specialty';
    }

    
    public function getUserInfoByName($id)
    {
        $user = $this->selectOne("id='{$id}'", '*');
        return $user;
    }
	public function getList($admin_reg){
        $arr=$this->select(['status'=>1,'isdelete'=>'0','admin_reg'=>$admin_reg],'id,name,pid')->items;
        return $arr;
    }
}
