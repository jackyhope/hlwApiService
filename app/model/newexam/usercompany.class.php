<?php
class model_newexam_usercompany extends gdl_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_user_company';
    }

    
    public function isUserCompanyExist($userid , $plaform_id)
    {
        $res = $this->selectOne(['userid' => $userid, 'plaform_id' => $plaform_id],'id');
        return $res['id'];
    }
	
	public function isUserCompanyInfo($userid=0,$identity_id=0,$plaform_id=0)
    {
		if($userid){
			$map = ['userid' => $userid, 'plaform_id' => $plaform_id];
		} else {
			$map = ['id' => $identity_id];
		}
        $res = $this->selectOne($map,'departmentid,departmentgroupid,company_id,admin_reg,job_title');
        return $res;
    }
}
