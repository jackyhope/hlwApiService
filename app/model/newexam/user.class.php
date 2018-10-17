<?php
class model_newexam_user extends gdl_components_basemodel
{

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'ex_user';
    }

    
    public function getUserInfoByName($name)
    {
        $user = $this->selectOne("username='{$name}' or phone='{$name}'", 'userid');
        return $user;
    }
}
