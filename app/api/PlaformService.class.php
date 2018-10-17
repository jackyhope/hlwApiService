<?php
use com\hlw\ks\interfaces\PlaformServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\plaform\PlaformInfoResultDTO;
use com\hlw\ks\dataobject\plaform\PlaformInviteCodeAddRequestDTO;
use com\hlw\ks\dataobject\plaform\PlaformInviteCodeResultDTO;

class api_PlaformService extends api_Abstract implements PlaformServiceIf
{


    /**
     * 取得相关平台
     * @param type $plaformType
     * @param type $name
     * @return ResultDO
     */
    public function getPlaform($plaformType, $name,$userid = 0,$level)
    {
        if ($level) {
            $data = [];
            $ids = [];
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
                            $data[] = ['id' => $id['id'], 'name' => $id['real'] , 'identity_id' => $r['indetity_id']];
                        }
                    } else {
                        if(!in_array($r['id'], $ids)){
                            $data[] = ['id' => $r['id'], 'name' => $r['real'] , 'identity_id' => $r['indetity_id']];
                        }
                    }
                    $ids[] = $r['id'];
                }

                if ($res) {
                    $result->data = $data;
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

    public function getPlaformInfoById($plaform_id)
    {
        $plaformInfoResultDTO = new PlaformInfoResultDTO();
        $plaform_id = gdl_lib_BaseUtils::getStr($plaform_id);
        try {
            $modelPlaform = new model_newexam_plaform();
            $res = $modelPlaform->selectOne(array("id=$plaform_id"), 'id,name,`real`', '', 'order by id desc');
            if ($res) {
                $plaformInfoResultDTO->data = $res;
                $plaformInfoResultDTO->code = 1;
            } else {
                $plaformInfoResultDTO->code = 0;
            }
            $plaformInfoResultDTO->success = true;
            return $plaformInfoResultDTO;
        } catch (Exception $e) {
            $plaformInfoResultDTO->success = false;
            $plaformInfoResultDTO->code = $e->getCode();
        }
        return $plaformInfoResultDTO;
    }

    public function plaformInviteCodeConfirm($code,$userid)
    {
        $plaformInviteCodeResultDO = new PlaformInviteCodeResultDTO();
        $userid = gdl_lib_BaseUtils::getStr($userid, 'int');
        $code = gdl_lib_BaseUtils::getStr($code, 'string');
        try {
            $modelPlaformInviteCode = new model_newexam_plaforminvitecode();
            $modelUserCompany = new model_newexam_usercompany();
            $modelPlaform = new model_newexam_plaform();
            $res = $modelPlaformInviteCode->selectOne(['code' => $code], 'id,code,plaform_id,expire_time,status', '', 'order by id desc');
            if ($res['id']) {
                if ($this->_time < $res['expire_time'] && $res['status'] == '1') {
                    //开始绑定
                    $results = $modelUserCompany->isUserCompanyExist($userid, $res['plaform_id']);
                    if (empty($results)) {
                        $re = $modelPlaform->getInfoById($res['plaform_id'], 'admin_reg');
                        $insert = [
                            'userid' => $userid,
                            'plaform_id' => $res['plaform_id'],
                            'admin_reg' => $re['admin_reg']
                        ];
                        $ret = $modelUserCompany->insert($insert);

                        //临时加入检修活动邀请码加入专门考场 2017-09-20 by yanghao
                        if ($code == '666000' && $this->_time < 1505997000) {
                            $modelBasicUser = new model_newexam_basicuser();
                            $modelUser = new model_newexam_user();
                            $whereUser = [
                                'userid' => $userid
                            ];
                            $itemUser = 'gdl_userid,username,realname,idcard';
                            $retUser = $modelUser->selectOne($whereUser, $itemUser);
                            if ($retUser['gdl_userid']) {
                                $modelBasicUser->insert([
                                    'basic_id' => '256',
                                    'identity_id' => $ret,
                                    'gdl_userid' => $userid,
                                    'plaform_id' => $res['plaform_id'],
                                    'username' => $retUser['username'],
                                    'usertruename' => $retUser['realname'],
                                    'idcard' => $retUser['idcard'],
                                    'create_time' => $this->_time
                                ]);
                                $modelBasicUser->insert([
                                    'basic_id' => '255',
                                    'identity_id' => $ret,
                                    'gdl_userid' => $userid,
                                    'plaform_id' => $res['plaform_id'],
                                    'username' => $retUser['username'],
                                    'usertruename' => $retUser['realname'],
                                    'idcard' => $retUser['idcard'],
                                    'create_time' => $this->_time
                                ]);
                            }
                        }

                        if ($ret) {

                            $plaformInviteCodeResultDO->code = 1;
                            $plaformInviteCodeResultDO->invitecode = $res['code'];
                            $plaformInviteCodeResultDO->message = '绑定成功';
                        } else {
                            $plaformInviteCodeResultDO->code = 0;
                            $plaformInviteCodeResultDO->message = '绑定失败';
                        }
                    } else {
                        $plaformInviteCodeResultDO->code = 0;
                        $plaformInviteCodeResultDO->message = '您已添加过此平台';
                    }
                } else {
                    $plaformInviteCodeResultDO->code = 0;
                    $plaformInviteCodeResultDO->message = '邀请码已过期';
                }
            } else {
                $plaformInviteCodeResultDO->code = 0;
                $plaformInviteCodeResultDO->message = '邀请码不存在';
            }
            $plaformInviteCodeResultDO->success = true;
        } catch (Exception $e) {
            $plaformInviteCodeResultDO->success = false;
            $plaformInviteCodeResultDO->code = $e->getCode();
        }
        return $plaformInviteCodeResultDO;
    }

    public function plaformInviteCodeAdd(PlaformInviteCodeAddRequestDTO $inviteCodeAddRequestDO)
    {
        ;
    }
}
