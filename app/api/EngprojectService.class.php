<?php

use com\hlw\ks\interfaces\EngprojectServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\engproject\EngprojectDTO;
use com\hlw\ks\dataobject\engproject\EngprojectRequestDTO;
use com\hlw\ks\dataobject\engproject\EngprojectDocRequestDTO;

class api_EngprojectService extends api_Abstract implements EngprojectServiceIf
{

    /**
     * 工程列表
     * @param EngprojectRequestDTO $engprojectDo
     * @return ResultDO
     */
    public function englist(EngprojectRequestDTO $engprojectDo)
    {
        $result = new ResultDO();

        $id = $engprojectDo->id ? gdl_lib_BaseUtils::getStr($engprojectDo->id) : 0;
        $task_title = empty($engprojectDo->filename['task_title']) ? 0 : mysql_escape_string($engprojectDo->filename['task_title']);
		$plaformArr = empty($engprojectDo->filename['plaformArr']) ? 0 : mysql_escape_string($engprojectDo->filename['plaformArr']);
		
		$page = $engprojectDo->limit ? gdl_lib_BaseUtils::getStr($engprojectDo->limit,'int') : 0;
		$num = $engprojectDo->num ? gdl_lib_BaseUtils::getStr($engprojectDo->num,'int') : 10;
		$page = $page*10;
        if (!$id) {
            $result->success = false;
            $result->code = 0;
            $result->message = '缺少参数ID';
            return $result;
        }

        try {
            $list = [[]];
            $str = null;
            $eventuser = new model_newexam_eventuser();
            $field = $engprojectDo->field ? $engprojectDo->field : '*';
            $res = $eventuser->select('gdl_userid = ' . $engprojectDo->id.' and plaform_id in('.$plaformArr.')', $field,'', 'order by id desc limit '.$page.','.$num)->items;
            if (!empty($res[0])) {
                foreach ($res as $v) {
                    $str[] = $v['eventid'];
                }
                $str = implode(',', $str);
                $projectevent = new model_newexam_projectevent();
                if ($task_title) {
                    $onet = ' ';
                    $onet = $onet . " and project_name like '%{$task_title}%'";
                }
                $res = $projectevent->select('id in(' . $str . ") and status=1 and isdelete=0 ".$onet, $field, '', 'order by id desc limit '.$page.','.$num)->items;
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
     * 
     * @param EngprojectRequestDTO $engprojectDo
     * @return ResultDO
     */
    public function authoritw(EngprojectRequestDTO $engprojectDo)
    {
        $result = new ResultDO();
        try {
            $eventuser = new model_newexam_eventuser();
            $field = $engprojectDo->field ? $engprojectDo->field : '*';
            $res = @$eventuser->select('identity_id = ' . self::userdoc($engprojectDo->id) . ' and eventid=' . $engprojectDo->eveid, $field)->items;

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

    /*
     * 通过id 查询文件对应地址
     */

    public function fileadd(EngprojectRequestDTO $engprojectDo)
    {
        $result = new ResultDO();
        try {

			$address = explode(',',$engprojectDo->field);
			$add = [];
			$fileadd = [];
			$back = [];
			foreach($address as $v){
				
				if(is_numeric($v)){
					$fileadd[] = $v;
				} else {
					$back[] = (int)$v;
				}
			}
			
			if(!empty($fileadd)){
				$exfile = new model_newexam_exfile();
				$res = @$exfile->select('id in(' . implode(',',$fileadd).')', 'id,original as title,name as address,type')->items;
				foreach($res as $ve){
					$ve['type'] = self::mark($ve['type']);
					$vc['cpid'] = 'a';
					$add[] = $ve;
				}
			}
			
			if(!empty($back)){
				$eventdoc = new model_newexam_eventdoc();
				$res = @$eventdoc->select('id in(' . implode(',',$back).') and isdelete=0', 'id,title,address,type')->items;
				foreach($res as $vc){
					$vc['type'] = self::markdoc($vc['type']);
					$vc['cpid'] = 'b';
					$add[] = $vc;
				}
			}
			
			
			
			if(empty($add)){
				$result->data[] = $add;
			} else {
				$result->data = $add;
			}
           


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
        $result->notify_time = time();
        return $result;
    }

    /*
     * 用户反馈写入
     */

    public function inback(EngprojectRequestDTO $engprojectDo)
    {
        $result = new ResultDO();
        try {
            $practice = new model_newexam_eventbask();
            $username = $engprojectDo->filename;
            $username['identity_id'] = self::userdoc($username['identity_id']);
            $id = $practice->insert($username)->items;

            $result->data[] = [1];
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
     * 
     * @param EngprojectRequestDTO $engprojectDo
     * @return ResultDO
     */
    public function mydocument(EngprojectRequestDTO $engprojectDo)
    {
        $result = new ResultDO();
        try {
            $list = [[]];
            $eventuser = new model_newexam_eventuser();
            $field = $engprojectDo->field ? $engprojectDo->field : '*';
            $task_title = empty($engprojectDo->filename['task_title']) ? 0 : mysql_escape_string($engprojectDo->filename['task_title']);
			
			$page = $engprojectDo->limit ? gdl_lib_BaseUtils::getStr($engprojectDo->limit,'int') : 0;
			$num = $engprojectDo->num ? gdl_lib_BaseUtils::getStr($engprojectDo->num,'int') : 10;
			$page = $page*10;

            $status = $engprojectDo->status ? (int) $engprojectDo->status : 0;
            $user = @$eventuser->select('identity_id = ' . $engprojectDo->id . ' and eventid=' . $engprojectDo->eveid, '*')->items;
            if (!empty($user[0])) {
                $onet = '';
                if ($status) {
                    $onet = ' and id=' . $status;
                }
                if ($task_title) {
                    $onet = $onet . " and task_title like '%{$task_title}%'";
                }
                $eventtask = new model_newexam_eventtask();
                $list = @$eventtask->select('eventid=' . $engprojectDo->eveid . ' and status=1 and isdelete=0' . $onet, '*', '', 'order by id desc limit '.$page.','.$num)->items;
            }

            $result->data = $list;
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
     * 日常管控文档资料列表
     * @param EngprojectDocRequestDTO $engprojectDocDo
     * @return ResultDO
     */
    public function mydoc(EngprojectDocRequestDTO $engprojectDocDo)
    {
        $result = new ResultDO();
        try {
            $eventid = $engprojectDocDo->eventid ? gdl_lib_BaseUtils::getStr($engprojectDocDo->eventid, 'int') : 0;
            $identity_id = $engprojectDocDo->identity_id ? $engprojectDocDo->identity_id : 0;
			
			$type = $engprojectDocDo->type ? gdl_lib_BaseUtils::getStr($engprojectDocDo->type,'int') : 0;
			$page = $engprojectDocDo->offset ? gdl_lib_BaseUtils::getStr($engprojectDocDo->offset,'int') : 0;
			$num = $engprojectDocDo->num ? gdl_lib_BaseUtils::getStr($engprojectDocDo->num,'int') : 10;
			$page = $page*10;
			
            if (!$eventid) {
                throw new Exception('缺少工程ID');
            }

            $modeEventDoc = new model_newexam_eventdoc();
            $condition = array(
                'eventid' => $eventid,
                'status' => 1,
                'isdelete' => 0,
                'identity_id' =>$identity_id,

            );
			if($type){
				$condition['type']=$type;
			}

            $items = 'title,introduction,address,type,identity_id,id,dtime';
            $res = $modeEventDoc->select($condition, $items,'', 'order by id desc limit '.$page.','.$num)->items;
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
     * 根据文档ID取得文档信息
     * @param type $id
     * @param type $eventid
     * @return ResultDO
     * @throws Exception
     */
    public function getMyDoById($id, $eventid) 
    {
		$cpi=0;
        $result = new ResultDO();
        try {
            $eventid = gdl_lib_BaseUtils::getStr($eventid, 'int');
            $id = gdl_lib_BaseUtils::getStr($id, 'int');

            if (!$eventid) {
                throw new Exception('缺少工程ID');
            }

            if (!$id) {
                throw new Exception('缺少ID');
            }

            $condition = array(
                'id' => $id,
                'eventid' => $eventid,
				'isdelete'=>0,
            );

            $item = 'title,introduction,address,type,identity_id userid,id';

				
				
			
            $modelEventDoc = new model_newexam_eventdoc();
            $res = $modelEventDoc->selectOne($condition, $item, '', 'order by id desc');
			if(empty($res['userid'])){
				$modelEventDoc = new model_newexam_exfile();
				 $res = $modelEventDoc->selectOne(['id'=>$id,'isdelete'=>0],'original title,original introduction,name address,type,1,id');
			}
			
            $result->data = array(0 => $res);
			
			//$result->message = json_encode($modelEventDoc);
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
     * 日常管控文档资料上传
     * @param EngprojectDocRequestDTO $engProjectDoc
     * @return ResultDO
     * @throws Exception
     */
    public function myDocAdd(EngprojectDocRequestDTO $engProjectDoc) 
    {
        $result = new ResultDO();
        try {
            $title = $engProjectDoc->title ? gdl_lib_BaseUtils::getStr($engProjectDoc->title) : '';
            $address = $engProjectDoc->address ? gdl_lib_BaseUtils::getStr($engProjectDoc->address) : '';
            $eventid = $engProjectDoc->eventid ? gdl_lib_BaseUtils::getStr($engProjectDoc->eventid, 'int') : 0;
            $introduction = $engProjectDoc->introduction ? gdl_lib_BaseUtils::getStr($engProjectDoc->introduction) : '';
            $type = $engProjectDoc->type ? gdl_lib_BaseUtils::getStr($engProjectDoc->type, 'int') : 0;
            $identity_id = $engProjectDoc->identity_id ? gdl_lib_BaseUtils::getStr($engProjectDoc->identity_id, 'int') : 0;

            if (!$title || !$identity_id || !$type || !$address || !$eventid) {
                throw new Exception('信息不完整');
            }

            $insert = array(
                'eventid' => $eventid,
                'title' => $title,
                'identity_id' => $identity_id,
                'address' => $address,
                'type' => $type,
                'introduction' => $introduction,
                'dtime' => date('Y-m-d', time())
            );

            $modelEventDoc = new model_newexam_eventdoc();
            $res = $modelEventDoc->insert($insert, true);

            if ($res) {
                $result->code = 1;
                $result->message = '资料添加成功';
            } else {
                $result->code = 0;
                $result->message = '资料添加失败!请稍后再试';
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
     * 修改标题

     */
    public function feaktitle(EngprojectDocRequestDTO $engProjectDoc)
    {
        $result = new ResultDO();

        try {
            $title = $engProjectDoc->title ? gdl_lib_BaseUtils::getStr($engProjectDoc->title) : 0;
            $id = $engProjectDoc->eventid ? gdl_lib_BaseUtils::getStr($engProjectDoc->eventid) : 0;
            $identity_id = $engProjectDoc->identity_id ? gdl_lib_BaseUtils::getStr($engProjectDoc->identity_id) : 0;

            $update = array();
            if ($title) {
                $update['title'] = $title;
            }

            $condition = array();
            if ($identity_id) {
                $condition['identity_id'] = $identity_id;
            }

            if ($id) {
                $condition['id'] = $id;
            }


            if ($identity_id || $id) {
                $modelEventDoc = new model_newexam_eventdoc();
                $res = $modelEventDoc->update($condition, $update);
            } else {
                $res = 0;
            }

            if ($res) {
                $result->code = 1;
                $result->message = '修改成功';
            } else {
                $result->code = 0;
                $result->message = '修改失败!请稍后再试';
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
     * 日常管控文档资料修改
     * @param type $id
     * @param EngprojectDocRequestDTO $engProjectDoc
     * @return ResultDO
     * @throws Exception
     */
    public function myDocEdit($id, EngprojectDocRequestDTO $engProjectDoc)
    {
        $result = new ResultDO();
        try {
            $title = $engProjectDoc->title ? gdl_lib_BaseUtils::getStr($engProjectDoc->title) : '';
            $address = $engProjectDoc->address ? gdl_lib_BaseUtils::getStr($engProjectDoc->address) : '';
            $eventid = $engProjectDoc->eventid ? gdl_lib_BaseUtils::getStr($engProjectDoc->eventid, 'int') : 0;
            $introduction = $engProjectDoc->introduction ? gdl_lib_BaseUtils::getStr($engProjectDoc->introduction) : '';
            $userid = $engProjectDoc->userid ? gdl_lib_BaseUtils::getStr($engProjectDoc->userid, 'int') : 0;
            $id = gdl_lib_BaseUtils::getStr($id,'int');

            if (!$title || !$userid || !$address || !$eventid) {
                throw new Exception('信息不完整');
            }

            $update = array(
                'eventid' => $eventid,
                'title' => $title,
                'userid' => $userid,
                'address' => $address,
                'introduction' => $introduction
            );
            
            $condition = array('id' => $id);

            $modelEventDoc = new model_newexam_eventdoc();
            $res = $modelEventDoc->update($condition, $update);

            if ($res) {
                $result->code = 1;
                $result->message = '资料修改成功';
            } else {
                $result->code = 0;
                $result->message = '资料修改失败!请稍后再试';
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
     * 违规列表
     * @param EngprojectRequestDTO $engprojectDo
     * @return ResultDO
     */
    public function violation(EngprojectRequestDTO $engprojectDo) 
    {
        $result = new ResultDO();
        try {

            $eventback = new model_newexam_eventbask();
            $field = $engprojectDo->field ? $engprojectDo->field : '*';
            $status = $engprojectDo->status ? $engprojectDo->status : '0';
			$page = $engprojectDo->limit ? gdl_lib_BaseUtils::getStr($engprojectDo->limit,'int') : 0;
			$num = $engprojectDo->num ? gdl_lib_BaseUtils::getStr($engprojectDo->num,'int') : 10;
			$page = $page*10;
			$limit = 'limit '.$page.','.$num;
			
            $cont = ' status=1 and isdelete=0';
            if ($status) {
                $cont = $cont . ' and type=' . $status;
            }
            if ($engprojectDo->id) {
                $cont = $cont . ' and identity_id = ' . self::userdoc($engprojectDo->id);
            }
            if ($engprojectDo->type) {
                $res = @$eventback->select($cont . ' and id=' . $engprojectDo->eveid, $field, '', 'order by id desc ' . $limit)->items;
            } else {
                $res = @$eventback->select($cont . ' and eventid="' . $engprojectDo->eveid . '"', $field, '', 'order by id desc ' . $limit)->items;
            }
            $asg = array();
            $suffix = '';
            $temporary = '';
            if (!empty($res[0])) {
                foreach ($res as $vr) {
                    $suffix = explode(',', $vr['annex']);
                    if (!empty($suffix[0]) && empty($suffix[1])) {
                        $suffix[1] = $suffix[0];
                    }
                    $temporary = empty($suffix[1]) ? ['type' => 'txt', 'address' => ''] : self::file_urlc($suffix[1]);
                    $vr['urladdress'] = $temporary['address'];
                    $vr['urltype'] = $temporary['type1'];
                    $asg[] = $vr;
                }
                $res = $asg;
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
     * 我的文档删除
     * @param EngprojectDocRequestDTO $engProjectDoc
     * @return ResultDO
     * @throws Exception
     */
    public function mydelete(EngprojectDocRequestDTO $engProjectDoc) 
    {
        $result = new ResultDO();
        try {
            $identity_id = $engProjectDoc->identity_id ? gdl_lib_BaseUtils::getStr($engProjectDoc->identity_id, 'int') : 0;
            $address = $engProjectDoc->address ? gdl_lib_BaseUtils::getStr($engProjectDoc->address) : 0;

            if (!$identity_id || !$address) {
                throw new Exception('信息不完整');
            }

            
            $modelEventDoc = new model_newexam_eventdoc();
            $res = $modelEventDoc->update("id in ({$address}) and identity_id={$identity_id}", 'isdelete=1');
            if ($res) {
                $result->code = 1;
                $result->message = '删除成功';
            } else {
                $result->code = 0;
                $result->message = '删除失败!请稍后再试';
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

    /*
     * 违规列表
     */

    public function mybacknr(EngprojectRequestDTO $engprojectDo) 
    {
        $result = new ResultDO();
        try {

            $eventback = new model_newexam_eventbask();
            $field = $engprojectDo->field ? $engprojectDo->field : '*';
            $status = $engprojectDo->status ? $engprojectDo->status : '0';

            $res = @$eventback->select(' status=1 and isdelete=0 and identity_id = ' . self::userdoc($engprojectDo->id) . ' and id=' . $engprojectDo->eveid, "*")->items;

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

    public function userdoc($id)
    {

       /* $eventuser = new model_newexam_user();
        $user = @$eventuser->select("userid=" . $id)->items;
        if (empty($user[0]['userid'])) {
            $user[0]['userid'] = 0;
        }
        return $user[0]['userid'];
		*/
		return $id;
    }

    public function mark($name)
    {
        $type = 'txt';
        if (strstr($name, 'img') || strstr($name, 'mage')) {
            $type = 'img';
        } else if (strstr($name, 'video')  || strstr($name,'io/mpeg')) {
            if (strstr($name, 'mp3') || strstr($name, 'oog')  || strstr($name,'io/mpeg')) {
                $type = 'audio';
            } else {
                $type = 'video';
            }
        }

        return $type;
    }

    public function markdoc($id)
    {
        $id = trim($id);
        $type = [
            '1' => 'img',
            '3' => 'audio',
            '4' => 'video',
            'image/png' => 'img',
            'image/jpeg' => 'img',
            'audio/ogg' => 'audio',
            'audio/mp3' => 'audio',
            'audio/mp4' => 'video',
        ];

        if (empty($type[$id])) {
            $type[$id] = 'txt';
        }


        return $type[$id];
    }
	
	
	public function file_urlc($id)
    {		
	$fileadd='';
	$back='';
				if(is_numeric($id)){
					$fileadd = $id;
				} else {
					$back = (int)$id;
				}
			
			
			if($fileadd!=''){
				$exfile = new model_newexam_exfile();
				$res = @$exfile->selectOne("id='{$fileadd}'", 'original as title,name as address,type');
				
					$res['type1'] = self::mark($res['type']);
					$res['cpid'] ='a';
			
			}
			
			if($back!=''){
				$eventdoc = new model_newexam_eventdoc();
				$res = @$eventdoc->selectOne("id='{$back}' and isdelete=0", 'title,address,type');
			
					$res['type1'] = self::markdoc($res['type']);
					$res['cpid'] ='b';
					
				
			}
			
			
		return 	$res;
			
    }

}
