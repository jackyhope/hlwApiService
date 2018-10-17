<?php

/**
 * 安管培系统用户管理
 * @author yanghao <yh38615890@sina.cn>
 * @date 2017-06-01
 * @copyright (c) gandianli
 */
use com\hlw\ks\interfaces\UserServiceIf;
use com\hlw\ks\dataobject\user\UserRequestDTO;
use com\hlw\ks\dataobject\user\UserResultDTO;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\user\UserDTO;
use com\hlw\ks\dataobject\user\UserLoginRequestDTO;
use com\hlw\ks\dataobject\user\UserLoginResultDTO;
use com\hlw\ks\dataobject\user\SingleLoginResultDTO;
use com\hlw\ks\dataobject\user\UserPasswordRequestDTO;
use com\hlw\ks\dataobject\user\UseridResultDTO;
use com\hlw\ks\dataobject\user\UserSimpleRegisterDTO;

date_default_timezone_set('PRC');

class api_UserService extends api_Abstract implements UserServiceIf {

    /**
     * 取得相关平台
     * @param type $plaformType
     * @param type $name
     * @return ResultDO
     */
    public function getPlaform($plaformType, $name,$userid = 0,$level) {
        if ($level) {
            $condition = array();
            $result = new ResultDO();
            $userid = $userid ? gdl_lib_BaseUtils::getStr($userid, 'int') : 0;

            $leftJoin = array(
                'ex_user_company as uc' => 'ex_plaform.id=uc.plaform_id'
            );
            $condition['uc.userid'] = $userid;
            $condition['ex_plaform.status'] = 1;
            $items = 'ex_plaform.id,ex_plaform.parentid,ex_plaform.`real`,uc.id as indetity_id';
            $groupBy = 'group by ex_plaform.id';
            try {
                $modelPlaform = new model_newexam_plaform();
                $res = $modelPlaform->select($condition, $items, $groupBy, '', $leftJoin)->items;
                //进行平台顶级读取处理
                $servPlaform = new service_plaform();
                foreach ($res as $k => $r) {
                    if ($r['parentid'] != 0) {
                        $id = $servPlaform->getTopPlaformIdbyId($r['parentid']);
                        if (!in_array($id['id'], $ids)) {
                            $modelUserCompany = new model_newexam_usercompany();
                            $where = ['userid' => $userid , 'plaform_id' => $id['id']];
                            $item = 'id';
                            $ret = $modelUserCompany->selectOne($where, $item);
                            $ids[] = ['id' => $id['id'], 'name' => $id['real'] , 'identity_id' => $ret['id']];
                        }
                    } else {
                        $ids[] = ['id' => $r['id'], 'name' => $r['real'] , 'identity_id' => $r['indetity_id']];
                    }
                }

                if ($res) {
                    $result->data = $ids;
                    $result->code = 1;
                } else {
                    $result->code = 0;
                }
                $result->success = true;
                return $result;
            } catch (Exception $e) {
                $result->success = false;
                $result->code = $e->getCode();
                $result->message = $e->getMessage();
            }
            return $result;
        } else {
            $condition = array();
            $result = new ResultDO();
            $plaformType = $plaformType ? gdl_lib_BaseUtils::getStr($plaformType, 'int') : 0;
            $name = $name ? gdl_lib_BaseUtils::getStr($name) : '';

            if ($plaformType == 1) {
                $condition['type'] = $plaformType;
                $condition['status'] = 1;
                $items = 'name,real,id';
            } else {
                $leftJoin = array(
                    'ex_user_company as uc' => 'ex_plaform.id=uc.plaform_id',
                    'ex_user as u' => 'uc.userid=u.userid'
                );
                $condition['ex_plaform.type'] = $plaformType;
                $condition['ex_plaform.status'] = 1;
                $name ? $condition['u.idcard'] = $name : '';
                $items = 'ex_plaform.name,ex_plaform.real,ex_plaform.id';
                $groupBy = 'group by ex_plaform.id';
            }
            try {
                $modelPlaform = new model_newexam_plaform();
                $res = $modelPlaform->select($condition, $items, $groupBy, '', $leftJoin)->items;

                if ($res) {
                    $result->data = $res;
                    $result->code = 1;
                } else {
                    $result->code = 0;
                }
                $result->success = true;
                return $result;
            } catch (Exception $e) {
                $result->success = false;
                $result->code = $e->getCode();
                $result->message = $e->getMessage();
            }
            return $result;
        }
    }

    /**
     * 取得相关平台
     * @param type $plaformType
     * @param type $name
     * @return ResultDO
     */
    public function getUseridByUcid($ucid) {
        $condition = array();
        $result = new UseridResultDTO();
        $ucid = $ucid ? gdl_lib_BaseUtils::getStr($ucid, 'int') : 0;

        $condition['gdl_userid'] = $ucid;
        $items = 'userid,realname,idcard1,username';
        try {
            $modelPlaform = new model_newexam_user();
            $res = $modelPlaform->selectOne($condition, $items);

            if ($res) {
				
                $result->userid = $res['userid'];
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 安管培用户登录 
     * @param UserLoginRequestDTO $login
     * @return UserLoginResultDTO
     */
    public function login(UserLoginRequestDTO $login) {
        $result = new UserLoginResultDTO();
        try {
            $username = gdl_lib_BaseUtils::getStr($login->username, 'string');
            $password = gdl_lib_BaseUtils::getStr($login->password, 'string');
            $plaform = gdl_lib_BaseUtils::getStr($login->plaform, 'int');

//            if (strlen($username) == 15 || strlen($username) == 18) {
            if (!gdl_lib_BaseUtils::validation_filter_id_card($username)) {
                $result->code = 0;
                $result->message = '请输入正确的身份证！';
            }

            $controller = array('ex_user.idcard' => $username, 'ex_user_company.plaform_id' => $plaform, 'ex_user_company.status' => 0);
//            } else {
//                $controller = array("ex_user.username='{$username}' or ex_user.phone='{$username}'", 'ex_user_company.plaform_id' => $plaform, 'ex_user_company.status' => 0);
//            }


            $modelUser = new model_newexam_user();
            $user = $modelUser->selectOne($controller
                    , 'ex_user.username,ex_user.realname,ex_user.idcard,ex_user.userid,ex_user_company.id,ex_user_company.passsalt,ex_user_company.password,ex_user_company.company_id,ex_user_company.plaform_id,ex_user_company.company_name'
                    , ''
                    , 'order by ex_user.userid desc'
                    , array('ex_user_company ' => 'ex_user_company.userid=ex_user.userid'));

            if (empty($user['id'])) {
                $result->code = 0;
                $result->message = '用户不存在,请检查身份证号！';
            }
            if ($user['passsalt'] == 'aa123456') {
                $password = $password === '' ? '' : md5(sha1($password) . '*]f)={.zRuS;FZWUv6"TGJPO_5g<Kx#~k&|7nj(I');
            } else {
                $password = md5((gdl_lib_BaseUtils::IsMd5($password) ? md5($password) : md5(md5($password))) . $user['passsalt']);
            }

            if ($user['password'] == $password) {
                $serviceSigleLogin = new service_singlelogin();
                $ret = $serviceSigleLogin->register($user['id'], 86400, 1);

                $result->code = 1;
                $result->pid = $user['id'];
                $result->message = '登录成功';
                $result->data = [
                    'username' => $user['username'],
                    'userid' => $user['userid'],
                    'company_name' => $user['company_name'],
                    'realname' => $user['realname'],
                    'company_id' => $user['company_id'],
                    'plaform' => $plaform,
                    'idcard' => $user['idcard']
                ];
            } else {
                $result->code = 0;
                $result->message = $user['passsalt'];
            }
            $result->success = true;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    public function sigleLogin($identity_id) {
        $result = new ResultDO();
        $identity_id = $identity_id ? gdl_lib_BaseUtils::getStr($identity_id, 'str') : 0;

        try {
            $serviceSigleLogin = new service_singlelogin();
            $ret = $serviceSigleLogin->register($identity_id, 7200, 1);
			
			/***开始记录登陆信息***/
				$usercompays = new model_newexam_usercompany();
				$userDetails = $usercompays->selectOne(['id'=>$identity_id,'status'=>'0'],'userid,plaform_id');
				$plaform = new model_newexam_plaform();
				$admin_reg =$plaform->getChildAdminRegByPlaformId($userDetails['plaform_id']);
				$user = new model_newexam_user();
				$userinfor =$user->selectOne("userid='{$userDetails['userid']}'",'realname,idcard,username');
				
				$user_stat = $usercompays->select("admin_reg in('".implode('\',\'',$admin_reg)."') and userid='{$userDetails['userid']}'", 'id,admin_reg')->items;
				$data = array();
				$userlog = new model_newexam_userlog();
				foreach($user_stat as $v){
					$data = [
						'username'=>$userinfor['username'],
						'realname'=>$userinfor['realname'],
						'idcard1'=>$userinfor['idcard'],
						'admin_reg'=>$v['admin_reg'],
						'logintiem'=>date('Y-m-d H:i:s'),
						];
						 $userlog->insert($data, true);
					
				}

				
		
				
				/***开始记录登陆信息 end***/

		
            if ($ret) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    public function getSigleLoginStatus($uid) {
        try {
            $result = new SingleLoginResultDTO();
            if (!$uid) {
                $result->success = TRUE;
                $result->code = 0;
                $result->message = '参数不全';
                return $result;
            }

            $time = time();
            $modelUser = new model_newexam_usersinglelogin();
            $condition = array(
                "expire>{$time}",
                'status' => 1,
                'pid' => $uid
            );
            $res = $modelUser->selectOne($condition, 'id', '', 'order by id desc');
            if ($res['id']) {
                $result->islogin = TRUE;
            } else {
                $result->islogin = FALSE;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->islogin = FALSE;
        }
        return $result;
    }

    /**
     * 安管培系统用户信息查询
     * @access public
     * @param UserRequestDTO $userDo
     * @return UserResultDTO
     */
    public function getUserInfoById(UserRequestDTO $userDo) {
        $result = new UserResultDTO();
        try {
            $modelUser = new model_newexam_user();
            $field = $userDo->field ? gdl_lib_BaseUtils::getStr($userDo->field, 'string') : '*';
            $uid = $userDo->id ? gdl_lib_BaseUtils::getStr($userDo->id, 'int') : 0;
            $res = $modelUser->selectOne(
                    array('ex_user_company.id' => $uid), $field, '', 'order by ex_user_company.userid desc', array('ex_user_company' => 'ex_user_company.userid=ex_user.userid')
            );
            $result->data = $res;
            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 操作安管培系统用户信息
     * @access public
     * @param UserDTO $userDo
     * @return ResultDO
     */
    public function user(UserDTO $userDo) {
        $result = new ResultDO();
        $where = $userDo->where ? gdl_lib_BaseUtils::getStr($userDo->where) : '';
        $update = $userDo->update ? gdl_lib_BaseUtils::getStr($userDo->update) : '';
        $id = $userDo->id ? gdl_lib_BaseUtils::getStr($userDo->id, 'int') : 0;

        try {
            $modelUser = new model_newexam_user();
            $modelUserCompany = new model_newexam_usercompany();
            if ($id) {
                $condition = [
                    'ex_user_company.id' => $id,
                    'ex_user_company.status' => 0
                ];
                $leftJoin = [
                    'ex_user as u' => 'ex_user_company.userid=u.userid'
                ];

                $items = 'ex_user_company.company_name as company,u.username,u.phone,u.realname,u.idcard,u.address,u.photo,ex_user_company.company_id,ex_user_company.departmentid,ex_user_company.departmentgroupid,ex_user_company.job_title';
                $res = $modelUserCompany->select($condition, $items, '', '', $leftJoin)->items;
            }

            if ($where) {
                $res = $modelUser->select($where)->items;
            }

            if ($update) {
                $data = unserialize($update);
                $modelUser->update($where, $data['data']);
            }
            $result->data = $res;
            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;

            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 通知
     * @access public
     * @param UserDTO $userDo
     * @return ResultDO
     */
    public function messagepush(UserDTO $userDo) {
        $result = new ResultDO();
        $where = $userDo->where ? gdl_lib_BaseUtils::getStr($userDo->where, 'string') : '';
        $update = $userDo->update ? gdl_lib_BaseUtils::getStr($userDo->update, 'string') : '';
        $id = $userDo->id ? gdl_lib_BaseUtils::getStr($userDo->id, 'int') : 0;
        $offset = $userDo->offset ? gdl_lib_BaseUtils::getStr($userDo->offset, 'int') : 0;
        $num = $userDo->num ? gdl_lib_BaseUtils::getStr($userDo->num, 'int') : 10;
        $page = $offset * $num;
        try {
            $messagepush = new model_newexam_messagepush();
            if ($where) {
				$plaform = new model_newexam_plaform();	
				$adminregs = $plaform->getChildAdminRegByPlaformId($id);
				$adminregs = implode("','",$adminregs);
				if($adminregs==''){
					$adminregs=0;
				}
                $res = $messagepush->select(str_ireplace('&#39;', "'", $where) . " and admin_reg in('{$adminregs}')", '*', '', 'order by id desc limit ' . $page . ',' . $num)->items;
            }
            if ($update) {
                $data = unserialize($update);
                $messagepush->update($where, $data['data']);
            }
            $result->data = $res;
			$result->message = json_encode($messagepush);
            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 获取用户列表
     * @access public
     * @param UserDTO $userDo
     * @return ResultDO
     */
    public function userList(UserDTO $userDo) {
        $result = new ResultDO();
        $where = $userDo->where ? gdl_lib_BaseUtils::getStr($userDo->where, 'string') : '';
        $field = $userDo->field ? gdl_lib_BaseUtils::getStr($userDo->field, 'string') : '*';
        $update = $userDo->update ? gdl_lib_BaseUtils::getStr($userDo->update, 'string') : '';
        try {
            $modeluser = new model_newexam_user();

            if ($where) {
                $res = $modeluser->select($where, $field)->items;
            } else {
                $res = $modeluser->select();
            }
            if ($update) {
                $data = unserialize($update);
                $res = $modeluser->update($where, $data['data']);
            }
            $result->message = json_encode($res);
            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 根据部门ID取得用户列表
     * @access public
     * @param type $departmentid
     * @return ResultDO
     */
    public function getAllUserByProjecttid($projecttid, $admin_reg) {
        $projecttid = gdl_lib_BaseUtils::getStr($projecttid);
        $result = new ResultDO();
        try {
            $modelMember = new model_newexam_user();
            $res = $modelMember->select(array('project_id' => $projecttid, 'ex_user_company.admin_reg' => $admin_reg), 'ex_user_company.id', '', 'order by ex_user_company.id desc', array('ex_user_company' => 'ex_user_company.userid=ex_user.userid'))->items;
            if (!empty($res[0])) {
                $result->data = $res;
                $result->code = 1;
            } else {
                $result->message = '未找到数据';
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 根据专业ID取得用户列表
     * @access public
     * @param type $departmentid
     * @return ResultDO
     */
    public function getAllUserBySpecialtyid($specialtyid, $admin_reg) {
        $specialtyid = gdl_lib_BaseUtils::getStr($specialtyid);
        $result = new ResultDO();
        try {
            $modelMember = new model_newexam_user();
            $res = $modelMember->select(array('specialty_id' => $specialtyid, 'ex_user_company.admin_reg' => $admin_reg), 'ex_user_company.id', '', 'order by ex_user_company.id desc', array('ex_user_company' => 'ex_user_company.userid=ex_user.userid'))->items;
            if (!empty($res[0])) {
                $result->data = $res;
                $result->code = 1;
            } else {
                $result->message = '未找到数据';
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    public function newpassword(UserPasswordRequestDTO $password) {
        $result = new UserLoginResultDTO();
        try {
            $username = gdl_lib_BaseUtils::getStr($password->idcard, 'string');
            $userid = gdl_lib_BaseUtils::getStr($password->userid, 'string');
            $plaform = gdl_lib_BaseUtils::getStr($password->plaform, 'int');
            $company = gdl_lib_BaseUtils::getStr($password->company, 'int');

            $controller = array('ex_user.idcard' => $username, 'ex_user_company.plaform_id' => $plaform, 'ex_user_company.status' => 0);
            $company ? $controller['ex_user_company.company_id'] = $company : '';
            if (!gdl_lib_BaseUtils::validation_filter_id_card($username)) {
                $result->code = 0;
                $result->message = '请输入正确的身份证！';
            }

            $modelUser = new model_newexam_user();
            $user = $modelUser->selectOne($controller
                    , 'ex_user.username,ex_user.realname,ex_user.idcard,ex_user.userid,ex_user_company.id,ex_user_company.passsalt,ex_user_company.password,ex_user_company.company_id,ex_user_company.plaform_id,ex_user_company.company_name'
                    , ''
                    , 'order by ex_user.userid desc'
                    , array('ex_user_company ' => 'ex_user_company.userid=ex_user.userid'));

            if (empty($user['id'])) {
                $result->code = 0;
                $result->message = '用户不存在,请检查身份证号！';
            } else {
                $result->code = $user['id'];
                $result->data = $user;
            }
            $result->success = true;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 查询用户首页喜好配置
     */
    public function userconfig(UserDTO $userDo) {
        $result = new ResultDO();
        $id = $userDo->id ? gdl_lib_BaseUtils::getStr($userDo->id, 'int') : '0';
        $field = $userDo->field ? gdl_lib_BaseUtils::getStr($userDo->field, 'str') : '*';
        $plaform_id = $userDo->type ? gdl_lib_BaseUtils::getStr($userDo->type, 'int') : '0';

        try {

            $res = self::selectuser($id, $field, $plaform_id);
            if (empty($res[0]) && $plaform_id && $id) {
                //$res =self::userint($plaform_id);
                $res = self::userint('pt1');
            }
            $result->data = $res;

            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 添加或修改用户喜好
     */
    public function addconfig(UserLoginResultDTO $userDo) {
        $result = new ResultDO();
        $id = $userDo->pid ? gdl_lib_BaseUtils::getStr($userDo->pid, 'int') : '0';
        $connect = $userDo->message ? explode('|', gdl_lib_BaseUtils::getStr($userDo->message, 'string')) : '';
        $plaform_id = $userDo->uid ? gdl_lib_BaseUtils::getStr($userDo->uid, 'int') : '0';
        $arr = array();
        $key = ['title', 'type', 'img', 'url'];
        $datime = date('Y-m-d');
        if (!$id || $connect == '') {
            //如果身份没有或者为空就不执行下面了
            $result->data[][] = 1;
            $result->code = 0;
            $result->success = false;
            return $result;
        }
        foreach ($connect as $v) {
            $arr[] = @array_combine($key, explode(',', $v));
        }
        $arr = serialize($arr);

        try {
            $usercig = self::selectuser($id, $field, $plaform_id);
            $eventuser = new model_newexam_userconfig();
            if (empty($usercig[0])) {

                $insert = array(
                    'userconfig' => $arr,
                    'identity_id' => $id,
                    'datime' => $datime,
                    'plaform_id' => $plaform_id
                );
                $res = $eventuser->insert($insert, true);
            } else {
                $res = $eventuser->update("identity_id={$id}", "userconfig='" . $arr . "',datime='{$datime}'");
            }

            $result->data = $usercig;
            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    public function selectuser($identity_id, $field = '*', $plaform_id) {
        $eventuser = new model_newexam_userconfig();

        $res = @$eventuser->select("identity_id = '" . $identity_id . "' and plaform_id='{$plaform_id}'", $field)->items;

        return $res;
    }

    public function userint($id) {
        $eventuser = new model_newexam_setting();
        $res = @$eventuser->select("item = 'pt1' and item_key='{$id}'", 'item_value as userconfig')->items;
        return $res;
    }

    public function simpleRegister(UserSimpleRegisterDTO $simpleDO) 
    {
        try {
            $result = new ResultDO();

            $ucid = $simpleDO->ucid ? gdl_lib_BaseUtils::getStr($simpleDO->ucid, 'int') : '0';
            $username = $simpleDO->username ? gdl_lib_BaseUtils::getStr($simpleDO->username, 'string') : '';
            $truename = $simpleDO->truename ? gdl_lib_BaseUtils::getStr($simpleDO->truename, 'string') : '';
            $idcard = $simpleDO->idcard ? gdl_lib_BaseUtils::getStr($simpleDO->idcard, 'string') : '';
            $phone = $simpleDO->phone ? gdl_lib_BaseUtils::getStr($simpleDO->phone, 'string') : '';

            $model = new model_newexam_user();
            $modelUserCompany = new model_newexam_usercompany();
            $modelPlaform = new model_newexam_plaform();

            $insert = [
                'gdl_userid' => $ucid,
                'username' => $username,
                'realname' => $truename,
                'idcard' => $idcard,
                'phone' => $phone,
				'userregtime'=>$this->_time,
            ];
            $ret = $model->insert($insert);
            
            $results = $modelUserCompany->isUserCompanyExist($ret, 5);
            
            if(empty($results)){
//                $re = $modelPlaform->getInfoById(5, 'admin_reg');
//                
//                $inserUserCompany  = [
//                    'plaform_id' => 5,
//                    'userid' => $ret,
//                    'admin_reg' => $re['admin_reg']
//                ];
//                $res = $modelUserCompany->insert($inserUserCompany);
//                if($res){
                    $result->code = 1;
//                }else {
//                    $result->code = 0;
//                }
                
            } else {
                $result->code = 0;
            }
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 修改用户的积分
     * @param $identity_id
     * @param $scores
     * @param string $type
     * @return int
     */
    public function setUserScore($identity_id, $scores, $type = '+') {
        $serviceUserscores = new service_userscores();
        $res = $serviceUserscores->setUserScoresIdentityId($identity_id, $scores, $type);
        return $res;
    }
	
	/**
     * 通过身份证号查询用户信息
     * @param $idcard

     */
    public function userinfo($idcard) {
        $result = new ResultDO();
        $gdl_userid = $idcard;


        try {
			
			 $modelUserCompany = new model_newexam_user();

            $res = $modelUserCompany->selectOne(['gdl_userid'=>$gdl_userid], 'realname,photo,idcard');

            $result->data[] =$res;

            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }
	
	//companyaddress 查询用户可显示 公司 组 部门
	  public function companyaddress(UserDTO $userDo)
    {
        $result = new ResultDO();
        try {
			
			$userid = $userDo->id ? gdl_lib_BaseUtils::getStr($userDo->id, 'int') : '0';
			$plaform_id = $userDo->type ? gdl_lib_BaseUtils::getStr($userDo->type , 'int') : '0';
			$identity_id = $userDo->offset ? gdl_lib_BaseUtils::getStr($userDo->offset, 'int') : '0';
			$cid = $userDo->num ? gdl_lib_BaseUtils::getStr($userDo->num, 'int') : '0';
			$marks = $userDo->where ? gdl_lib_BaseUtils::getStr($userDo->where, 'string') : 'c';
			switch($marks){
				case 'c':
					$res = [[2]];
					$plaform = new model_newexam_plaform();
					$admin_reg =$plaform->getChildAdminRegByPlaformId($plaform_id);
					if(!empty($admin_reg)){
						$usercompany = new model_newexam_usercompany();
						$resc = $usercompany->select("userid='{$userid}' and admin_reg in('".implode('\',\'',$admin_reg)."')",'admin_reg')->items;
						$wher = '';
						foreach($resc as $v){
							$wher .= ',\''.$v['admin_reg'].'\'';
						}
						$wher = '1'.$wher;
						$company = new model_newexam_company();
						$res = $company->select("status='1' and admin_reg in(".$wher.")",'id,name')->items;
					}
				break;
				case 'b':
				
					$company = new model_newexam_company();
					$resc = $company->selectOne(['id'=>$cid],'admin_reg');
					$department = new model_newexam_department();
					$res = $department->select(['status'=>1,'pid'=>0,'admin_reg'=>$resc['admin_reg']],'id,name')->items;
				break;
				case 'f':
					$department = new model_newexam_department();
					$res = $department->select(['status'=>1,'pid'=>$cid],'id,name')->items;
				break;
				
			}
           // $eventuser = new model_newexam_eventuser();
           // $field = $engprojectDo->field ? $engprojectDo->field : '*';
           // $res = @$eventuser->select('identity_id = ' . self::userdoc($engprojectDo->id) . ' and eventid=' . $engprojectDo->eveid, $field)->items;

            $result->data = $res;
	
            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

	//companyaddress 查询用户可显示 公司 组 部门
	  public function modifydepartment(UserResultDTO $userDo)
    {
        $result = new ResultDO();
        try {
			$user = $userDo->data ? $userDo->data : '0';
				$res = [['code'=>0]];
				if(is_array($user)){
					$userid = $user['userid'];
					$plaform_id = $user['plaform_id'];
					
					$plaform = new model_newexam_plaform();
					$admin_reg =$plaform->getChildAdminRegByPlaformId($plaform_id);
					if(!empty($admin_reg)){
						$company = new model_newexam_company();
						$rescamp = $company->selectOne("id='{$user['company_id']}' ",'id,name');
						$usercompany = new model_newexam_usercompany();
						
						$update = [
							'company_id'=>$user['company_id'],
							'departmentid'=>$user['departmentid'],
							'departmentgroupid'=>$user['departmentgroupid'],
							'company_name'=>$rescamp['name'],
							'job_title'=>$user['job_title'],
						];
						$specialty_id = $user['specialty_id'];
						if($specialty_id){
							$Specialty = new model_newexam_userspecialty();
							$userSpecialty = $Specialty->getUserInfoByName($specialty_id);
							$Specialtyname = empty($userSpecialty) ? '' : $userSpecialty['name'];
							$update['specialty_id'] = $specialty_id;
							$update['specialty'] = $Specialtyname;
							
						}
						
						
						$resc = $usercompany->update("userid='{$userid}' and admin_reg in('".implode('\',\'',$admin_reg)."')", $update);
						$res = [['code'=>1]];
					}
				}
            $result->data = $res;

            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }
	
	
	/**
     * 获取当前用户是否选择班组或公司
     * @access public
     * @param UserDTO $userDo
     * @return ResultDO
     */
    public function newsinfo(UserDTO $userDo) {
        $result = new ResultDO();

        $identity_id = $userDo->id ? gdl_lib_BaseUtils::getStr($userDo->id, 'int') : 0;
		$plaform_id = $userDo->plaformid ? gdl_lib_BaseUtils::getStr($userDo->plaformid, 'int') : 0;

        try {
			$modelUserCompany = new model_newexam_usercompany();
			//departmentid,departmentgroupid,company_id
			$userinfo = $modelUserCompany->isUserCompanyInfo(0,$identity_id);
			
			$result->data[] = $userinfo;

            if ($userinfo) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }
	
	
	/**
     * 获取专业列表
     * @access public
     * @param UserDTO $userDo
     * @return ResultDO
     */
    public function specialist(UserDTO $userDo) {
        $result = new ResultDO();

        $identity_id = $userDo->id ? gdl_lib_BaseUtils::getStr($userDo->id, 'int') : 0;
		$plaform_id = $userDo->plaformid ? gdl_lib_BaseUtils::getStr($userDo->plaformid, 'int') : 0;

        try {
			$modelUserCompany = new model_newexam_usercompany();
			$userinfo = $modelUserCompany->isUserCompanyInfo(0,$identity_id);
			$modelplaform = new model_newexam_plaform();
			$admin_reg = $modelplaform->getInfoById($plaform_id,'admin_reg');
		

            if ($userinfo['admin_reg']==$admin_reg['admin_reg']) {
				$Specialty = new model_newexam_userspecialty();
				$SpecialtyList = $Specialty->getList($admin_reg['admin_reg']);
				$result->data = $SpecialtyList;
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

}
