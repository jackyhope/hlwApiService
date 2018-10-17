<?php

use com\hlw\ks\interfaces\SignrulesServiceIf;
use com\hlw\ks\dataobject\signrules\SignrulesDTO;
use com\hlw\ks\dataobject\signrules\SignrulDTO;
use com\hlw\common\dataobject\common\ResultDO;

date_default_timezone_set('PRC');
class api_SignrulesService extends api_Abstract implements SignrulesServiceIf
{
 
    /**
     * 查询工程签到规则
     */
    public function record(SignrulesDTO $userDo) 
    {
        $result = new ResultDO();
		$identity_id = $userDo->identity_id ? gdl_lib_BaseUtils::getStr($userDo->identity_id,'int') : 0;
		$pid = $userDo->pid ? gdl_lib_BaseUtils::getStr($userDo->pid,'int') : 0;

        try {
			$come_back = [];
			/***先查询用户最近的一条签到记录****/

			$user_card = self::signcheck(['identity_id'=>$identity_id,'pid'=>$pid]);
			/***先查询用户最近的一条签到记录 end****/
			
			/******查询签到规则******/

			$p_config = self::signconfig(['pid'=>$pid]);
			/******查询签到规则 end******/
			
			
			/******查询签到点坐标******/

			$p_signrules = self::signrules(['pid'=>$pid]);
			/******查询签到点坐标 end******/
			
			$come_back = [
				'check_time'	=>empty($user_card['check_time']) ? '0' : $user_card['check_time'], //最后一次签到时间 可能为空
				'discern'		=>(int)$p_config['discern'], //是否开启人脸识别
				'enforce'		=>empty($p_config['enforce']) ? '0' : $p_config['enforce'], //是否强制签到
				'discern_deta'	=>'0',
				'checkin'=>1, //默认能签到
				'number'		=>empty($p_config['number']) ? '0' : $p_config['number'], //要求签到的次数
				'star_time'		=>empty($p_config['star_time']) ? strtotime(date('Y-m-d').' 01:01:01') : strtotime(date('Y-m-d').$p_config['star_time']), //签到开始时间
				'intervals'		=>empty($p_config['intervals']) ? '0' : $p_config['intervals'], //签到间隔时间
				'coordinate'	=>empty($p_signrules['coordinate']) ? '' : $p_signrules['coordinate'], //签到坐标配置
				'description'	=>empty($p_signrules['description']) ? '' : $p_signrules['description'], //规则描述
			];
			if($come_back['discern']){
				$monitorrule = new model_newexam_monitorrule();
				$m_signrules = $monitorrule->selectOne(['id'=>$p_signrules['discern_deta'],'type'=>3,'isdelete'=>0,'status'=>1],'*');
				$come_back['discern_deta'] = empty($m_signrules['rule']) ? '' : $m_signrules['rule'];
			}
			
            $result->data[] = $come_back;
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
	*签到
	*/
	public function checkin(SignrulesDTO $userDo) 
    {
        $result = new ResultDO();
		$identity_id = $userDo->identity_id ? gdl_lib_BaseUtils::getStr($userDo->identity_id,'int') : 0;
		$pid = $userDo->pid ? gdl_lib_BaseUtils::getStr($userDo->pid,'int') : 0;
		$address = $userDo->address ? gdl_lib_BaseUtils::getStr($userDo->address,'string') : 0;
		$sign = $userDo->sign ? $userDo->sign : [];
		$plaform_id = $userDo->plaform_id ? gdl_lib_BaseUtils::getStr($userDo->plaform_id,'int') : 0;
		$note = $userDo->note ? $userDo->note : '';
		$type = $userDo->type ? gdl_lib_BaseUtils::getStr($userDo->type,'int') : 0;
		
        try {
			$come_back = [];
			/***先查询用户最近的一条签到记录****/
			$user_card = self::signcheck(['identity_id'=>$identity_id,'pid'=>$pid]);
			/***先查询用户最近的一条签到记录 end****/

			/******查询签到规则******/
			$p_config = self::signconfig(['pid'=>$pid]);
			/******查询签到规则 end******/
			
			
			/******查询签到点坐标******/
			$p_signrules = self::signrules(['pid'=>$pid]);
			/******查询签到点坐标 end******/
			
			$usercompany = new model_newexam_plaform();
			$user_company = $usercompany->selectOne(['id'=>$plaform_id]);
			
			
			$projectevent = new model_newexam_projectevent();
			$project_event = $projectevent->selectOne(['id'=>$pid],'project_name');
			
			$titm = time();
			/******计算2次签到插值******/
			$check_time = empty($user_card['check_time']) ? '0' : $user_card['check_time'];
			$intervals = empty($p_config['intervals']) ? '0' : $p_config['intervals'];
			$star_time = empty($p_config['star_time']) ? strtotime(date('Y-m-d').' 01:01:01') : strtotime(date('Y-m-d').$p_config['star_time']);
			$intervals = $titm-$intervals*60;
			$go_beyond=2;
			$go_num=0;
			if(empty($p_config)){
				/**如果压根就没设置签到点**/
				$p_config['number']=0;
				$p_config['enforce']=0;
				$p_config['intervals']=0;
				/***查询用户今天签到了几次****/
				$user_card_num = self::signcheck(['identity_id'=>$identity_id,'pid'=>$pid],1);
				/***查询用户今天签到了几次 end****/
				if($user_card_num['num']>2){
					$go_beyond=3;
					$go_num=3;
				} else {
					$go_beyond=4;
				}
			}
			if(($intervals<$check_time && date('Y-m-d',$check_time)==date('Y-m-d') && $p_config['enforce']) || $go_beyond==3){
			//if($p_config['enforce']==8){
				//如果当前时间减去间隔时间 小于上次签到时间 并且上次签到时间等于今天 强制签到 就认为时间没到 
				$come_back = ['code'=>0,'next_time'=>($check_time+$p_config['intervals']*60),'num'=>$go_num];
			} else {
				$date = [
					'identity_id'=>$identity_id,
					'status'=>'1',
					'check_time'=>$titm,
					'sign_coord'=>serialize($sign),
					'sign_address'=>$address,
					'sign_in'=>'', //签到时候最近的点
					'pid'=>$pid,
					'project_name'=>$project_event['project_name'] ? $project_event['project_name'] : '未知工程',
					'plaform_id'=>$plaform_id,
					'admin_reg'=>empty($user_company['admin_reg']) ? $plaform_id : $user_company['admin_reg'],
					'number'=>(int)$p_config['number'],
					'enforce'=>(int)$p_config['enforce'],
		

				];
				if($p_config['discern'] && $note!=''){
					$date['bind']=2;
				}
				
				if($go_beyond==2){
					$coordinate = unserialize($p_signrules['coordinate']);
					$ranging = [];
					foreach($coordinate as $v){
						$ranging[]=self::GetDistance($sign['lat'], $sign['lng'], $v['coordinate']['lat'], $v['coordinate']['lng'])-$v['deviation'];
					}
					
					asort($ranging); //重排距离
					$key = array_keys($ranging); //获取key
					$valone = reset($ranging); //获取重排后第一个值
					//if($valone<=0){ //注释掉这个 这样永远都有最近签到点 不会看起来奇怪
						$date['sign_in']=$coordinate[$key[0]]['title'];
						
					
				//		}
				} else {
					$date['sign_in']='未设置签到点';
				}
				if($valone>0 && $p_config['enforce']){
					$date['status']=2; //没有到签到点签到
				}
				
				if($p_config['enforce'] && empty($user_card['check_time']) && $titm>($star_time+7200)){
						$date['taipu']=3; //签到超时
				}
				

				if(($p_config['discern'] && $note=='') || $type){
					$date['taipu']=4;
				}
				
				$date['distance'] = $valone;
				$signcheck = new model_newexam_signcheck();
				$inserid = $signcheck->insert($date);
				if($inserid>1){
				/********如果有人脸识别就开始写入获取的照片*********/
				if($p_config['discern'] && $note!=''){
				 $imgsrc = explode(',',$note);
				 $signdoc = new model_newexam_signdoc();
					for($c=0;$c<count($imgsrc);$c++){
						$datec = [
							'address'=> $imgsrc[$c],
							'signid'=>$inserid,
						];
						$signdoc->insert($datec);
					}
				}
			
				/********如果有人脸识别就开始写入获取的照片*********/
				
				$come_back = ['code'=>1,'next_time'=>($titm+$p_config['intervals']*60),'num'=>$go_num];
				} else {
					$come_back = ['code'=>0,'next_time'=>($titm+$p_config['intervals']*60),'num'=>$go_num];
					
				}
			}
			
            $result->data[] = $come_back;
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
	*签到记录 按日
	*/
	public function checkrecord(SignrulDTO $userDo) 
    {
        $result = new ResultDO();
		$identity_id = $userDo->identity_id ? gdl_lib_BaseUtils::getStr($userDo->identity_id,'int') : 0;
		$pid = $userDo->pid ? gdl_lib_BaseUtils::getStr($userDo->pid,'int') : 0;
		$plaform_id = $userDo->plaform_id ? gdl_lib_BaseUtils::getStr($userDo->plaform_id,'int') : 0;
		$datime = $userDo->datime ? gdl_lib_BaseUtils::getStr($userDo->datime,'string') : date('Y-m-d');

        try {
			$come_back = [];
			


			/******查询签到规则******/
			$p_config = self::signconfig(['pid'=>$pid]);
			/******查询签到规则 end******/
			$signcheck = new model_newexam_signcheck();
			$user_card = $signcheck->select('identity_id='.$identity_id.' and pid='.$pid.' and plaform_id='.$plaform_id.' and FROM_UNIXTIME(check_time, \'%Y-%m-%d\') =\''.$datime.'\'','*','','order by sorts asc')->items;
			$signdoc = new model_newexam_signdoc();
			foreach($user_card as $vc){
				$tag=[];
				$sign_doc='';
				$sign_connect=['conter'=>''];
					if($vc['status']==3){ $vc['status']=1; }
				if($vc['bind']==2){
					
					$sign_doc = $signdoc->select(['signid'=>$vc['id']],'address')->items;
					$sign_connect = $signdoc->selectOne(['signid'=>$vc['id']],'conter');
				} else if($vc['bind']==1){
					$sign_connect = $signdoc->selectOne(['signid'=>$vc['id']],'conter');
					
				}
				/***拼接异常信息***/
				if($vc['status']==2){
					$vc['status']=2;
					$tag[] = '地点异常';
					
				}
				
				if($vc['status']==4){
					$vc['status']=3; //3是未签到 2是异常 方便前端区分
					$tag[] = '未签到';
				}
				
				if($vc['taipu']==3){
					$vc['status']=2;
					$tag[] = '签到超时';
					
				}
				
				if($vc['taipu']==4){
					$vc['status']=2;
					$tag[] = '人脸识别未通过';
				}
				
				/***拼接异常信息 end***/
			
				$come_back[] = [
					'id'=>$vc['id'], 
					'status'=>$vc['status'], //状态
					'check_time'=>date('H:i',$vc['check_time']), //打卡时间
					'sign_address'=>$vc['sign_address'], //打卡地点
					'sign_in'=>$vc['sign_in'], //设置的最近打卡点
					'taipu'=>$vc['taipu'], //设置的最近打卡点
					'tag'=>json_encode($tag),
					'sign_doc'=>json_encode($sign_doc),
					'number'=>$vc['number'],
					'remarks'=>$sign_connect['conter'],
					'enforce'=>$vc['enforce'],
					//'sorts'=>$vc['sorts'],
				];
			}

			

            $result->data = $come_back;
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
	*签到记录 按月
	*/
	public function checkrecordmonth(SignrulDTO $userDo) 
    {
        $result = new ResultDO();
		$identity_id = $userDo->identity_id ? gdl_lib_BaseUtils::getStr($userDo->identity_id,'int') : 0;
		$pid = $userDo->pid ? gdl_lib_BaseUtils::getStr($userDo->pid,'int') : 0;
		$plaform_id = $userDo->plaform_id ? gdl_lib_BaseUtils::getStr($userDo->plaform_id,'int') : 0;
		$datime = $userDo->datime ? gdl_lib_BaseUtils::getStr($userDo->datime,'string') : date('Y-m');

        try {
			


			/******查询签到规则******/
			$p_config = self::signconfig(['pid'=>$pid]);
			/******查询签到规则 end******/
			$signcheck = new model_newexam_signcheck();
			$user_card = $signcheck->select('identity_id='.$identity_id.' and pid='.$pid.' and plaform_id='.$plaform_id.' and FROM_UNIXTIME(check_time, \'%Y-%m\') =\''.$datime.'\'',"FROM_UNIXTIME(check_time,'%Y-%m-%d') days,COUNT(id) num,identity_id,number",' GROUP BY days','order by id desc')->items; //按月统计所有
			
			$user_abnormal = $signcheck->select('identity_id='.$identity_id.' and pid='.$pid.' and plaform_id='.$plaform_id.' and (taipu>1 or status>1) and FROM_UNIXTIME(check_time, \'%Y-%m\') =\''.$datime.'\' and check_time>'.$p_config['create_time'],"FROM_UNIXTIME(check_time,'%Y-%m-%d') days",' GROUP BY days','order by id desc')->items; //按月统计有异常的
			
			
			$abnormal = [];
			$come_back = [];
			
			foreach($user_abnormal as $vb){
				$abnormal[] = $vb['days'];
			}
			
			foreach($user_card as $vc){
				$status=1;
				if($vc['num']<$vc['number'] || in_array($vc['days'],$abnormal)){
					$status=0;
				}

				$come_back[] = [
					'days'=>$vc['days'],
					'num'=>$vc['num'], //打卡次数
					'identity_id'=>$vc['identity_id'],

					'number'=>$vc['number'], //打卡当天要求的次数
					'status'=>$status,

				];
			}


            $result->data = $come_back;
	
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
	*签到忘记签到备注
	*/
	public function signnote(SignrulesDTO $userDo) 
    {
        $result = new ResultDO();
		$identity_id = $userDo->identity_id ? gdl_lib_BaseUtils::getStr($userDo->identity_id,'int') : 0;
		$pid = $userDo->pid ? gdl_lib_BaseUtils::getStr($userDo->pid,'int') : 0;
		$note = $userDo->address ? gdl_lib_BaseUtils::getStr($userDo->address,'string') : 0; //备注
		$plaform_id = $userDo->plaform_id ? gdl_lib_BaseUtils::getStr($userDo->plaform_id,'int') : 0;
		$datime = $userDo->admin_reg ? strtotime($userDo->admin_reg.' 23:59:58') : time();
		$sorts = $userDo->type ? gdl_lib_BaseUtils::getStr($userDo->type,'int') : 1;
		
        try {
			$come_back = [];

			/******查询签到规则******/
			$p_config = self::signconfig(['pid'=>$pid]);
			/******查询签到规则 end******/
	
			$usercompany = new model_newexam_usercompany();
			$user_company = $usercompany->selectOne(['id'=>$identity_id,'plaform_id'=>$plaform_id]);
			
			
			$projectevent = new model_newexam_projectevent();
			$project_event = $projectevent->selectOne(['id'=>$pid],'project_name');
			


			

				$date = [
					'identity_id'=>$identity_id,
					'status'=>'4',
					'check_time'=>$datime,
					'sign_coord'=>'',
					'sign_address'=>'',
					'sign_in'=>'', //签到时候最近的点
					'pid'=>$pid,
					'project_name'=>$project_event['project_name'],
					'plaform_id'=>$plaform_id,
					'admin_reg'=>$user_company['admin_reg'],
					'number'=>$p_config['number'],
					'enforce'=>$p_config['enforce'],
					'bind'=>1,
					//'sorts'=>$sorts,

				];
				

				$signcheck = new model_newexam_signcheck();
				$iinserid = $signcheck->insert($date);
				$come_back = ['code'=>1];

				
				$signdoc = new model_newexam_signdoc();
				$datec = [
					'conter'=>$note,
					'signid'=>$iinserid,
				];
				 $signdoc->insert($datec);
				
			
            $result->data[] = $come_back;
	
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
	*签到备注
	*/
	public function signinnote(SignrulesDTO $userDo) 
    {
        $result = new ResultDO();
		
		$pid = $userDo->pid ? gdl_lib_BaseUtils::getStr($userDo->pid,'int') : 0;
		$note = $userDo->address ? gdl_lib_BaseUtils::getStr($userDo->address,'string') : ''; //备注
		
		
        try {
			$come_back = ['code'=>0];
				if($pid && $note!=''){
				$come_back = ['code'=>1];

				$signcheck = new model_newexam_signcheck();
				$signcheck->update(['id'=>$pid,'bind'=>0], ['bind'=>1]);
				$signdoc = new model_newexam_signdoc();
				$singdeic = $signdoc->selectOne(['signid'=>$pid],'id');
				
				if(empty($singdeic['id'])){
					$datec = [
						'conter'=>$note,
						'signid'=>$pid,
					];
					$signdoc->insert($datec);
		
				} else {
			
					$signdoc->update(['signid'=>$pid], ['conter'=>$note]);
				}
			}
            $result->data[] = $come_back;
	
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
	
	
	
	
	
	
	public function signcheck($arr,$type=0){
		$signcheck = new model_newexam_signcheck();
		if($type){
			$user_card = $signcheck->selectOne('identity_id='.$arr['identity_id'].' and pid='.$arr['pid'].' and check_time >'.strtotime(date('Y-m-d').' 00:00:01'),'count(*) num');
		} else {
			$user_card = $signcheck->selectOne('identity_id='.$arr['identity_id'].' and pid='.$arr['pid'].' and check_time >'.strtotime(date('Y-m-d').' 00:00:01'),'check_time','','order by id desc');
		}
		return $user_card;
	}
	
	public function signconfig($arr){
		$signconfig = new model_newexam_signconfig();
		$p_config = $signconfig->selectOne(['pid'=>$arr['pid']],'*');
		return $p_config;
	}
	
	public function signrules($arr){
		$signrules = new model_newexam_signrules();
		$p_signrules = $signrules->selectOne(['pid'=>$arr['pid'],'isdelete'=>0],'*');
		return $p_signrules;
	}
	
	//获取2点之间的距离
	public function GetDistance($lat1, $lng1, $lat2, $lng2){
	  $PI = 3.1415926535898;
	  $radLat1 = $lat1 * ($PI / 180);
	  $radLat2 = $lat2 * ($PI / 180);
	  $a = $radLat1 - $radLat2;
	  $b = ($lng1 * ($PI / 180)) - ($lng2 * ($PI / 180));
	  $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));
	  $s = $s * 6378.137;
	  $s = round($s * 10000) / 10;
	  return $s;
	}
	
}

