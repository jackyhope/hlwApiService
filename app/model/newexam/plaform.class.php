<?php
class model_newexam_plaform extends hlw_components_basemodel 
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_plaform';
    }

    
    public function getInfoById($plaform_id,$field)
    {
        return $this->selectOne(['id' => $plaform_id], $field);
    }
	
	 public function getInfoByIdadminreg($plaform_id,$field)
    {
        return $this->selectOne(['id' => $plaform_id], $field);
    }
	
	public function getChildAdminRegByPlaformId($plaformId){
        $admin_regs = [];
       $result = $this->select(['status'=>1],'id,parentid parent_id,admin_reg,level')->items;

        $childs = self::getMenuTree($result,$plaformId);
        if (!empty($childs)){
            $admin_regs = array_column($childs,'admin_reg');
            $admin_regs = array_unique($admin_regs);
        }
		$arge = self::getInfoByIdadminreg($plaformId,'admin_reg');
		if(!empty( $arge['admin_reg'])){
		$admin_regs[] = $arge['admin_reg'];
		}
    return $admin_regs;

	}
	public function getMenuTree($arrCat, $parent_id = 0, $level = 0)
	{
		static  $arrTree = array(); //使用static代替global
		if( empty($arrCat)) return false;
		$level++;
		foreach($arrCat as $key => $value)
		{
			if($value['parent_id' ] == $parent_id)
			{
				$value['level'] = $level;
				$arrTree[] = $value;
				unset($arrCat[$key]); //注销当前节点数据，减少已无用的遍历
				self::getMenuTree($arrCat, $value[ 'id'], $level);
			}
		}

		return $arrTree;
	}
}
