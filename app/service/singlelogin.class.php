<?php
class service_singlelogin extends hlw_components_baseservice
{
    public function __construct() 
    {
        parent::__construct();
    }
    
    public function register($userid,$expire,$from)
    {
        $modelSigleLogin = new model_newexam_usersinglelogin();

        //注销之前未处理的 登录状态记录
        $res = $modelSigleLogin->update(array('pid' => $userid), array('status' => 0));

        $insert = array(
            'pid' => $userid,
            'from' => $from,
            'expire' => $expire + time(),
            'time' => time(),
            'status' => 1
        );
        $ret = $modelSigleLogin->insert($insert);
        if ($ret) {
            return TRUE;
        } else {
            $this->setError(0, $modelSigleLogin->getDbError());
            return FALSE;
        }
    }

}
