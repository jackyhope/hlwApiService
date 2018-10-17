<?php
use com\hlw\ks\interfaces\ProjectuserServiceIf;
use com\hlw\common\dataobject\common\ResultDO; 
use com\hlw\ks\dataobject\projectuser\ProjectRequestDTO;
use com\hlw\ks\dataobject\projectuser\UserlistResultDTO;
use com\hlw\ks\dataobject\projectuser\ProjectOneRequestDTO;

class api_ProjectuserService extends api_Abstract implements ProjectuserServiceIf
{


    /**
     * 取得工程相关用户
     * @param type ProjectRequestDTO $project
     * @return UserlistResultDTO
     */
    public function getuserlist(ProjectRequestDTO $project)
    { 
        $condition = array();
		$result = new UserlistResultDTO();
		$result->success = false;
		$result->code = 0;
		$result->usercount = 0;
		$result->message = "用户列表为空";
		$result->data = array();
		//工程id
		$pid = $project->eventid ? gdl_lib_BaseUtils::getStr($project->eventid, 'int') : 0; 
		//条件查询 type 0获取全部 1已通过考试 2未通过考试 3未参加考试
		$type = $project->type ? gdl_lib_BaseUtils::getStr($project->type, 'int') : 0;
		//分页
		$page = $project->page ? gdl_lib_BaseUtils::getStr($project->page, 'int') : 0;
		
		if(empty($pid)){ //工程id不能为空
			$result->success = false;
			return $result;
		}
		if($page=='0') $page=1;
        $rows = 15;
        $limit = ($page - 1) * $rows . "," . $rows;
		
		$condition['eventid'] = $pid;
		$items = 'id,identity_id,username,usertruename,company,idcard,phone';
		$group = 'order by id desc limit '.$limit;
		
		try {
			//该工程下所有用户列表
			$projectdb = new model_newexam_projectuser(); 
			$res = $projectdb->select($condition,$items,$group)->items;
			$usercountrel = $projectdb->select($condition,'count(id) as usercount')->items;  
			$usercount = $usercountrel[0]['usercount'];
			
			//用户所属工程
			$projectodb = new model_newexam_projectevent();
			$evename = $projectodb->selectOne(array("id=$pid"), 'id,project_name'); 
			//var_dump($evename['project_name']);exit();
			if ($usercount>0) {  
			
				// 获取考场 该工程下考场id 模糊查询入场考试，
				// 用考场basicid and pid and identity_id 查询archives_mark表的数据
				// 获取list数据，然后赛选最高分为最终得分
				$basicobj = new model_newexam_basic();
				$basicwhere['eventid'] = $pid; //工程id
				$basicwhere['isdelete'] = 0; //0正常 1删除
				//$basicwhere['basic'] = ['LIKE','%入场考试%']; //入场考试id 减少一个%可以加速查询效率
				//$basicitems = '*';
				$basicitems = 'id,basic,eventid,status,s_name,isdelete,examid';
				$basename = $basicobj->select($basicwhere,$basicitems)->items;
				$basicid = array();//  该工程所有考场id
				$inbasic = array(); // 该工程入场卡id
				foreach($basename as $k){
					// 入场考试id
					if(strpos($k['basic'],'入场考试')){ 
						$inbasic = $k['id'];
					}
					$basicid[] = $k['id'];
				}
				
				// 参加考试的用户数据详情
				//更具入场考试basicid查询出档案表：archives_mark的用户数据true
				$markuser = array();
				if($inbasic){
					$markdb = new model_newexam_archivesmark();
					$markwhere['basic_id'] = $inbasic;
					$itmes = "id,username,identity_id,basic_id,company_id,user_idcard,max(user_score) as userscore,pass,passscore,isvalid,eventid";
					$groupBy = 'group by ex_archives_mark.identity_id order by user_score DESC';
					$markuser = $markdb->select($markwhere, $itmes, $groupBy)->items;
				}

				//用户签到数据 
				//算出每个用户的签到次数和异常次数
				$signdb = new model_newexam_signcheck();
				$signwhere['pid'] = $pid;
				$signitems = "count(id) as nums,identity_id,status,pid";
				$sgroupBy = 'group by ex_sign_check.identity_id';
				$signres = $signdb->select($signwhere,$signitems,$sgroupBy)->items; //总签到次数
				$errsign = $signdb->select('pid = '.$pid.' and status <> 1','count(id) as nums,identity_id,status,pid',$sgroupBy)->items; //异常签到次数次数

				//type 0获取全部 1已通过考试 2未通过考试 3未参加考试
				//最终返回用户数据
				
				$list = array(); //完整用户数据
				// end for res
				$i = 0;
				foreach($res as $k){ 
					if($markuser){//档案数据 工程用户详细分数							

						$list[$i]['socre'] = 0; 
						$list[$i]['pass'] = '未参加';					
						for($j=0; $j<$usercount; $j++){ //档案数据
							if($k['identity_id'] == $markuser[$j]['identity_id']){
								if($markuser[$j]['userscore']>=$markuser[$j]['passscore']){
									$list[$i]['socre'] = !empty($markuser[$j]['userscore'])?$markuser[$j]['userscore']:0;
									$list[$i]['pass'] = '通过';
								}else{
									$list[$i]['socre'] = !empty($markuser[$j]['userscore'])?$markuser[$j]['userscore']:0;
									$list[$i]['pass'] = '未通过';
								}
							} 
						}
						
					}else{
						$list[$i]['socre'] = 0; 
						$list[$i]['pass'] = '未参加';
					}

					if($signres){ //用户签到次数 
						$list[$i]['succnums'] = 0;
														
						for($ks=0; $ks<$usercount; $ks++){ // 正常签到数据
							if($k['identity_id']==$signres[$ks]['identity_id']){

								$list[$i]['succnums'] = $signres[$ks]['nums']; //用户id
							}
						}
					}else{
						$list[$i]['succnums'] = 0;
					}
					
					if($errsign){//用户异常签到次数					
						$list[$i]['errnums'] = 0; //用户id
						for($ek=0; $ek<$usercount; $ek++){ // 异常签到数据
							if($k['identity_id']==$errsign[$ek]['identity_id']){
								$list[$i]['errnums'] = $errsign[$ek]['nums']; //用户id
							}
						}
					}else{
								$list[$i]['errnums'] = 0; //用户id
					}
					$list[$i]['id'] = $k['id']; //用户id
					$list[$i]['identity_id'] = $k['identity_id']; //用户id
					$list[$i]['username'] = $k['username']; 		//用户昵称
					$list[$i]['usertruename'] = $k['usertruename'];//用户真名
					$list[$i]['company'] = !empty($k['company'])?$k['company']:'无公司';
					$list[$i]['evename'] = !empty($evename['project_name'])?$evename['project_name']:'无工程';
					$list[$i]['phone'] = !empty($k['phone'])?$k['phone']:'无手机';
					$i++;
					
				} // end for res

				$result->usercount = $usercount;
				//最终返回数据
				$s_list = array(); //通过
				$e_list = array(); //未通过
				$n_list = array(); //未参加
				foreach($list as $k){
					if($k['pass'] =='通过'){
						$s_list[] = $k; 
					}else if($k['pass'] =='未通过'){
						$e_list[] = $k; 
					}else{
						$n_list[] = $k; 
					}
				}
				//统计参加人数 通过人数 未通过人数
				$successCount = 0;
				$notCount = 0;
				if($markuser){
					foreach($markuser as $k){
						if($k['userscore']>=$k['passscore']){
							$successCount++;	
						}else{
							$notCount++;
						}	
					}
				}
				if($type==1){
					$result->data = $s_list;
					//通过人数
					$result->usercount = $successCount;
				}else if($type==2){ 
					$result->data = $e_list;
					$result->usercount = $notCount;
				}else if($type==3){
					$result->data = $n_list;
					$result->usercount = intval($usercount-$successCount-$notCount);
				}else{ //start else 全部用户
					$result->data = $list;
				}// end else
				

				$result->code = 1;
				$result->message = "获取用户成功";
			} else {
				$result->code = 0;
				$result->usercount = 0;
				$result->message = "没有用户";
				$result->data = array();
			}
			$result->success = true;
			return $result;
		} catch (Exception $e) {
			$result->success = false;
			$result->code = $e->getCode();
			$result->message = $e->getMessage();
			$result->usercount = 0;
		}
		return $result; 
    }
	/**
     * 取得单个用户详细信息
     * @param type ProjectOneRequestDTO $oneuser
     * @return UserlistResultDTO
     */
	 public function getoneuserinfo(ProjectOneRequestDTO $oneuser){
		 
		 $pid = $oneuser->eventid ? gdl_lib_BaseUtils::getStr($oneuser->eventid, 'int') : 0;  
		 $userid = $oneuser->userid ? gdl_lib_BaseUtils::getStr($oneuser->userid, 'int') : 0;
		 $result = new ResultDO();
		 if($pid and $userid){
			$condition['eventid'] = $pid; //工程id
			$condition['identity_id'] = $userid; //用户id or identity_id
			try {
				$projectdb = new model_newexam_projectuser(); 
				$res = $projectdb->select($condition)->items;  
				if (count($res)>0) { 
					//获取考场 该工程下考场id 
					$basicobj = new model_newexam_basic();
					$basicwhere['eventid'] = $pid; 
					$basicitems = '*';
					$basename = $basicobj->select($basicwhere,$basicitems)->items;
					$basicid = array();
					foreach($basename as $k){
						$basicid[] = $k['id'];
					}
					
					//获取exams_papers数据
					$expaers = new 	model_newexam_examspapers();
					$expwhere['basicid'] = ['in',$basicid]; 
					$expwhere['isvalid'] = 1;
					//$expwhere['type'] = 1; 考试分类如何区分
					$expwhere['identity_id'] = $userid;
					$times = "ep_id,identity_id,ep_userid,ispass,ep_paperId,ep_type";
					$oneuser = $expaers->select($expwhere,$times)->items;
					//var_dump($oneuser);exit();
					//获取档案数据 如何更具工程id 和用户identiry_id 获取用户单词单次考试的详细分数？？？
					//$markobject = new model_newexam_archivesmark();
					//$markwhere['paper_id'] = $oneuser[0]['ep_paperId'];
					//$markwhere['identity_id'] = $userid;
					//$times = "id,username,basic_id,identity_id,realname,user_score,pass,passscore";
					//$markresuser = $markobject->select($markwhere,$times)->items;
					
					//获取工程名称
					$projectodb = new model_newexam_projectevent();
					$evename = $projectodb->selectOne(array("id=$pid"), 'id,project_name'); 
					//签到次数
					$signdb = new model_newexam_signcheck();
					$signwhere['pid'] = $pid;
					$signwhere['identity_id'] = $userid;
					$items = "id,identity_id,status,pid";
					$signres = $signdb->select($signwhere,$items)->items;
					//签到次数，签到异常
					$signcounts = count($signres);
					$successsigncount = 0;
					$eroorsigncount = 0;
					foreach($signres as $k){
						if($k['status']!=1){
							$eroorsigncount++;
						}
					}
					
					//用户详细数据
					$userinfo = array();
					foreach($res as $k){
						$userinfo[0]['name'] = $k['usertruename'];
						$userinfo[0]['company'] = $k['company']?$k['company']:'无公司';
						$userinfo[0]['eventname'] = $evename['project_name']?$evename['project_name']:'无工程';
						$userinfo[0]['num'] = 0; //如何读档案数据 分数
						$userinfo[0]['pass'] = 0; //是否通过 0未通过 1通过
						$userinfo[0]['phone'] = $k['phone']?$k['phone']:'无电话';
						$userinfo[0]['sign'] = $signcounts;
						$userinfo[0]['exception_sign'] = $eroorsigncount;
					}
					$result->data = $userinfo;
					$result->code = 1;
					$result->message = "获取用户详细资料成功";
				} else {
					$result->code = 0;
					$result->message = "没有该用户详细资料";
				}
				$result->success = true;
				return $result;
			} catch (Exception $e) {
				$result->success = false;
				$result->code = $e->getCode();
				$result->message = $e->getMessage();
			}
			return $result; 
		 }else{
			 $result->success = false;
			 return $result;
		 }
	 }
 
}
