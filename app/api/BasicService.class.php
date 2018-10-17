<?php

use com\hlw\ks\interfaces\BasicServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\basic\BasicDTO;
require(dirname(dirname(__FILE__)) ."/php_sdk-master/include.php");
use TencentYoutuyun\YouTu;
use FaceVisaYun\FaceVisa;
use TencentYoutuyun\Conf;
use TencentYoutuyun\Auth;




class api_BasicService extends api_Abstract implements BasicServiceIf
{

    public function getBasicInfoById(BasicDTO $basicDo)
    {
        $result = new ResultDO();
        try {
            $modelBasic = new model_newexam_basic();
            $field = $basicDo->field ? $basicDo->field : '*';
            $res = @$modelBasic->select('ex_basic.id = ' . $basicDo->id . ' AND ex_basic.closed=1 ', $field, '', 'order by ex_basic.id desc', array('ex_basic_setting' => 'ex_basic_setting.basicid=ex_basic.id'))->items;

//            $modelCategory = @new model_newexam_category();
//            $res = @$modelCategory->selectOne("id>0");
//            $res = $modelBasic->insert(array('type' => 4));
//            $res = $modelBasic->update(array('id' => 4),array('type' => 5));
//            $res = $modelBasic->delete(array('id' => $testDo->id));
//            $res = @$modelBasic->select('id>0', 'id,type', '', 'order by id desc')->items;
            $result->data = $res;
            if ($res) {
//                $result->message = "delete done";
//                $result->message = "update done";
//                $result->message = "insert done";
                $result->code = 1;
            } else {
//                $result->message = "delete faild";
//                $result->message = "update faild";
//                $result->message = "insert faild";
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
     * basic 成绩单
     * id 考场id
     * name 用户名
     */

    public function basic(BasicDTO $basic)
    {
        $result = new ResultDO();
        try {
            $modelTest = new model_newexam_papersinfo();
            $res = $modelTest->selectOne("ex_exams_papers.ep_id='{$basic->id}'", 'ex_papers_info.name,ex_papers_info.time,ex_papers_info.modified,ex_exams_papers.modified as datme,ex_exams_papers.ep_type as type,ex_exams_papers.ep_paperId,ex_exams_papers.data,ex_exams_papers.question,ex_exams_papers.answer', '', '', array("ex_exams_papers" => "ex_papers_info.paperid = ex_exams_papers.ep_id"));
            $basicsetting = new model_newexam_basicsetting();
            if ($res['type']==2){
                $scoreshow = $basicsetting->selectOne("basicid='{$basic->basicid}'", 'scoreshow');
            }
            if (empty($res)) {
                $res['error'] = 'no_data';
            } else {
                if ($res['type']==2){
                    if (!$scoreshow['scoreshow']){
                        $res['question'] = '';
                    }
                    $res['scoreshow'] = $scoreshow['scoreshow'];
                }else{
                    $res['scoreshow'] = 1;
                }
                if (!empty($res['question'])) {
                    $question = unserialize($res['question']);
                    $answer = unserialize($res['answer']);
                    #$res["score"] = $question["questions"]["setting"]["examsetting"]["score"];
                    $res["score"] = $question["modified"];
                    #$res["questype"] = json_encode($question["questions"]["setting"]["examsetting"]["questype"]);
                    $questype = array();
                    #$res["examtime"] = $question["questions"]["setting"]['examtime'];
                    $reorganization = array();
                    $total = 0;
                    $questype_num = 0;
                    $quest_num = 0;
                    #$num_error = 0;q_typeid
                    /* foreach($question['questions'] as $k=>$v){
                      $num = 0;

                      foreach($v as $ko=>$vo){
                      $questype[] = $vo;//没有类型临时方法
                      $reorganization[$vo['q_typeid']]['num'] = ($vo['q_answer']==$answer[$vo['id']][0]) ? $num++ : $num;
                      if($vo['q_answer']==$answer[$vo['id']][0]){
                      $reorganization[$vo['q_typeid']]['error'][] = $ko;
                      }
                      $reorganization[$vo['q_typeid']]['score'] = $num*1;//这个还没
                      $quest_num = $question["questionids"]["setting"]["examsetting"]["questype"][$vo['q_typeid']]["number"];
                      }
                      $total = $total+$num;
                      #$questype_num = $questype_num+$quest_num;
                      $questype_num++;
                      } */

                    /*                     * ****获取试卷分数****** */

                    $modelPaper = new model_newexam_paper();
                    $paper = $modelPaper->selectOne("id='{$res['ep_paperId']}'", '*');

					/**********/

					// if($paper['p_papertype']==2){
						$ces_setting = $paper['ratio'];
					// } else {
					// 	$qbank = new model_newexam_qbank();
					// 	$qbankSet = $qbank->selectOne("id='{$paper['p_dbs']}'", '*');
					// 	$ces_setting = $qbankSet['ratio'];
					// }

					$dw_sorc = self::deRation($ces_setting);
					$scores = [];
					foreach($dw_sorc as $vs){
						$scores[$vs['1']] = [$vs['2'],$vs['3']];
					}
			/*************/
                    if (!empty($dw_sorc[0])) {
                        $p_setting = $scores;
                    } else {
                        $p_setting = ['1' => [1, 1], '2' => [1, 1], '3' => [1, 1], '4' => [1, 1], '5' => [1, 1], '6' => [1, 1]];
                    }
                    /*                     * ****获取试卷分数 end****** */

                    $num = 0;
                    $score = 0;
                    foreach ($question['questions'] as $ko => $vo) {
                        $reorganization[$vo['q_typeid']]['score'] =  $reorganization[$vo['q_typeid']]['score'] + $score;
                        $user_selection = isset($answer[$vo['id']]) ? is_array($answer[$vo['id']]) ? implode('', $answer[$vo['id']]) : $answer[$vo['id']] : '';
                        if (strpos($user_selection, ',')) {
                            $user_selection = str_replace(",","",@$user_selection);
                        }else{
                            $user_selection = @$user_selection;
                        }
                        $reorganization[$vo['q_typeid']]['num'] = ($vo['q_answer'] == $user_selection) ? ((int) $reorganization[$vo['q_typeid']]['num'] + 1) : (int) $reorganization[$vo['q_typeid']]['num'];
                        $reorganization[$vo['q_typeid']]['typeid'] = ((int) $reorganization[$vo['q_typeid']]['typeid'] + 1);
                        $reorganization[$vo['q_typeid']]['stat'] = $vo['q_typeid'];
                        if ($vo['q_answer'] != $user_selection) {
                            $reorganization[$vo['q_typeid']]['error'][] = $ko;
                        } else {
							if(empty($reorganization[$vo['q_typeid']]['score'])){
								$reorganization[$vo['q_typeid']]['score']=0;
							}
                            $reorganization[$vo['q_typeid']]['score'] = $reorganization[$vo['q_typeid']]['score'] + $p_setting[$vo["q_typeid"]][1]; //这个还没
                            $num++;
                        }



                        $total = $num;
                        #$questype_num = $questype_num+$quest_num;
                        $questype_num++;
                    }
                }
                $res["cc"] = json_encode($p_setting[3][1]);
                $res["examination"] = json_encode($reorganization);
                $res["total"] = $total; //答对的题

                $res["questype_num"] = $questype_num; //总题数
                $res["passing_rate"] = intval(($total / $questype_num) * 100); //正确率
                #$res["num_error"] = $num_error;//答对的题
                unset($res['question']);
                $res["time"] = self::secsToStr($res["time"]);
                $ranking = self::ranking($basic->basicid);
                if (!empty($ranking->items[0])) {
                    $rank = array();
                    foreach ($ranking->items as $k => $v) {
                        $rank[$v['name']] = $k + 1;
                    }
                    $ranking = $rank[$res["name"]];
                }
                $res['error'] = 'true';
                $res["ranking"] = $ranking;
            }
            $result->data[0] = $res;
            if ($res) {
//                $result->message = "delete done";
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
     * transcripts 排行榜
     * id 考场id
     * name 用户名
     */

    public function transcripts(BasicDTO $basic)
    {
        $result = new ResultDO();
        try {

            $ranking = self::ranking($basic->id);
            $username = self::user($basic->name);
            if (!empty($ranking->items[0])) {
                $rank = array();
                $my_ranking = 0;
                foreach ($ranking->items as $k => $v) {
                    $rank[$v['ep_userid']] = ['rank' => $k + 1, $v];
                }
                $rank[$username['id']][0]['time'] = self::secsToStr($rank[$username['id']][0]['time']);
                $my_ranking = json_encode($rank[$username['id']]);
                $rank = array();
                if (!empty($ranking->items[0])) {

                    foreach ($ranking->items as $k => $v) {
                        $v["time"] = self::secsToStr($v["time"]);
                        $rank[] = $v;
                    }
                }

                $res["ranking"] = json_encode($rank);
                $res["my_ranking"] = $my_ranking;
                $res['error'] = 'true';
            } else {
                $res['error'] = 'no_data';
            }
            $result->data[] = $res;
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

    /*     * ******下面是接口内部用的****** */

    public function ranking($basic) {
        try {
            $modelTest = new model_newexam_papersinfo();
            $rank = $modelTest->select("ex_exams_papers.basicid='{$basic}'", 'ex_exams_papers.ep_userid,ex_papers_info.name,ex_papers_info.time,ex_papers_info.modified', 'group by ex_papers_info.passscore,ex_papers_info.time', '', array("ex_exams_papers" => "ex_papers_info.paperid = ex_exams_papers.ep_id"));
        } catch (Exception $e) {
            $rank = 0;
        }
        return $rank;
    }

    public function user($name) {


        try {
            $modelTest = new model_newexam_user();
            $username = $modelTest->selectOne("username='{$name}'", 'id,realname,company');
        } catch (Exception $e) {
            $username = 0;
        }
        return $username;
    }

    public function secsToStr($secs) {
        if ($secs >= 86400) {
            $days = floor($secs / 86400);
            $secs = $secs % 86400;
            $r = $days . ' day';
            if ($days <> 1) {
                $r .= '';
            }
            if ($secs > 0) {
                $r .= ' ';
            }
        }
        if ($secs >= 3600) {
            $hours = floor($secs / 3600);
            $secs = $secs % 3600;
            $r .= $hours . ' 时';
            if ($hours <> 1) {
                $r .= '';
            }
            if ($secs > 0) {
                $r .= '  ';
            }
        }
        if ($secs >= 60) {
            $minutes = floor($secs / 60);
            $secs = $secs % 60;
            $r .= $minutes . ' 分';
            if ($minutes <> 1) {
                $r .= '';
            }
            if ($secs > 0) {
                $r .= ' ';
            }
        }
        $r .= $secs;
        if ($secs <> 1) {
            $r .= '秒';
        }
        return $r;
    }

    public function getBasicSettingById(BasicDTO $basicDo) {
        $result = new ResultDO();
        try {
            $modelBasic = new model_newexam_basic();
            $field = $basicDo->field ? $basicDo->field : '*';
            $res = @$modelBasic->selectOne('id = ' . $basicDo->id, $field, '', 'order by id desc')->items;
//            $modelCategory = @new model_newexam_category();
//            $res = @$modelCategory->selectOne("id>0");
//            $res = $modelBasic->insert(array('type' => 4));
//            $res = $modelBasic->update(array('id' => 4),array('type' => 5));
//            $res = $modelBasic->delete(array('id' => $testDo->id));
//            $res = @$modelBasic->select('id>0', 'id,type', '', 'order by id desc')->items;
            $result->data = $res;
            if ($res) {
//                $result->message = "delete done";
//                $result->message = "update done";
//                $result->message = "insert done";
                $result->code = 1;
            } else {
//                $result->message = "delete faild";
//                $result->message = "update faild";
//                $result->message = "insert faild";
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
    *identify 人脸识别
    */
    public function identify(BasicDTO $basic)
    {
        $result = new ResultDO();
        //如果对比照片其中一项为空，那么直接返回比对失败
        if(empty($basic->name)||empty($basic->field))
        {                
            $result->success = true;
            $result->code=2;
            $result->data[0] =  array('similarity' => 0);
            $result->message = 'no img';
            return $result;
        }

        try {
            //$res = array();
            //今后可设置为全局统一读取配置，利用缓存
             $faceType = gdl_lib_BaseUtils::getStr($basic->type,'int');
            if(!empty($faceType) && $faceType==2) $res = YouTu::facecompareOne($basic->field, $basic->name);
            elseif(!empty($faceType) && $faceType==1) $res = FaceVisa::facecompareOne($basic->field, $basic->name);
            else $res = YouTu::facecompareOne($basic->field, $basic->name);
            //$res = array('msg'=>$res);
            gdl_lib_BaseUtils::addLog(json_encode(['type' => $basic->type,'res' => $res,'data' => $basic]));
            $result->success = true;
            $result->code=2;
            $result->data[0] = $res;
            $result->message = $faceType;
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        $result->notify_time = time();
        return $result;
    }
	
	public function deRation($rstr){
		if(empty($rstr)) return false;
		$ratio_arr = unserialize($rstr);
		foreach($ratio_arr as $val){
		$result[] = explode(':',$val);			


		}

		return $result;	
		
	}

    /**
     * 根据条件获取所有考场信息 包括  总人数 参与人数 合格人数 合格率
     */
    public function getBasicInfo(BasicDTO $basic){
        $result = new ResultDO();
        try {
            $admin_reg = $basic->admin_reg ? gdl_lib_BaseUtils::getStr($basic->admin_reg) : 0;
            $dtime = $basic->time ? gdl_lib_BaseUtils::getStr($basic->time) : 0;;
            if($dtime=='1day'){
                $period = strtotime(date('Y-m-01', strtotime('-1 month')));
            } else if($dtime=='6day'){
                $period = strtotime(date('Y-m-01', strtotime('-6 month')));
            } else if($dtime=='12day'){
                $period = strtotime(date('Y-m-01', strtotime('-12 month')));
            }


            $BasicSetting = new model_newexam_basicsetting();
            $baSet = $BasicSetting->select('unix_timestamp(start_time) > '.$period);

            $asg= [];

            foreach($baSet->items as $vb){
                $asg['id'][] = $vb['basicid'];
            }

            $page = 0;

            $bassicArr = self::onlineUsers($page,$asg,$admin_reg);
            $bassicKeys = array();
            if(!empty($bassicArr)){

                $bassicKeys = array_keys($bassicArr);
                $bassicKey = implode(',',$bassicKeys);
                $satis = self::statistics($bassicKey,'basicid');//获取总参考人数

                foreach($satis as $vs){
                    $bassicArr[$vs['basicid']]['basic_number'] = $vs['num'];
                }

                $check_out = self::statistics($bassicKey,'basicid','ispass = 1');//获取合格人数

                foreach($check_out as $vk){
                    $bassicArr[$vk['basicid']]['basic_up'] = $vk['num'];
                }
            }

            $basics = ['basic_name'=>[],'basic_number'=>[],'basic_up'=>[]];

            foreach($bassicKeys as $v){
                if(strlen($bassicArr[$v]['basic_name'])>12){
                    $bassicArr[$v]['basic_name'] = mb_substr($bassicArr[$v]['basic_name'],0,12,'utf-8').'...';
                }
                $basics['basic_total_num'][] = $bassicArr[$v]['total_num'];
                $basics['basic_name'][] = $bassicArr[$v]['basic_name'];
                $basics['basic_number'][] = $bassicArr[$v]['basic_number'];
                $basics['basic_up'][] = $bassicArr[$v]['basic_up'];
                $basics['basic_no_pass'][] = $bassicArr[$v]['basic_number']-$bassicArr[$v]['basic_up'];
            }

            $result->datas = $basics;
            if ($basic) {
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
  *
  * 统计合格人数 onlineUsers
  * @return mixed
  * $asg 额外条件
  *默认的20个一页
  */
    public function onlineUsers($page=0,$asg=0,$admin_reg)
    {
        $bassicArr = array();
        if($page=='0') $page=1;
        $rows = 20;
        $limit = ($page - 1) * $rows . "," . $rows;
        if ($asg['id'])$asg['id'] = implode(',',$asg['id']);
        $basicModel = new model_newexam_basic();
        $basic = $basicModel->select(" ex_basic.isdelete=0 AND  ex_basic.admin_reg = '".$admin_reg."' AND ex_basic.id in (".$asg['id'].") ",
            'ex_basic.id,ex_basic.basic,count(ex_basic_user.identity_id) as total_num',
            'group by ex_basic.basic',
            'order by id desc limit '.$limit,
            array(
                'ex_basic_user' => 'ex_basic_user.basic_id = ex_basic.id',
            )
            )->items;
        foreach($basic as $k=>$v){
            $bassicArr[$v['id']] = [
                'basic_name'	=>	$v['basic'],
                'basic_number'	=>	0,
                'basic_up'	=>	0,
                'total_num'	=>	$v['total_num'],
            ];
        }
        return $bassicArr;
    }

    //统计
    public function statistics($id,$gp,$condition=0)
    {
        if($id==''){
            $id=0;
        }
        $wher = "basicid in({$id}) AND ep_type = 2";
        if($condition){
            $wher .= ' AND '.$condition;
        }
        $papersModel = new model_newexam_examspapers();
        $re = $papersModel->select($wher, 'basicid,count(*) as num', 'group by '.$gp,'order by basicid desc ')->items;
        return $re;
    }

    public function setArchives($paperid = null,$paperids = null){
        $result = new ResultDO();
        try {
            $service_archives = new service_archives();
            if (!empty($paperid)){
                $res = $service_archives->setArchives($paperid);
            }else if (!empty($paperids)){
                $res = $service_archives->setArchives($paperids);
            }
            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            $result->message = json_encode($res);
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 获取用户所在考场列表
     * @access public
     * @param int $identity_id
     * @return ResultDO
     */
    public function BasicRoom($identity_id, $search,$plaformid)
    {
        $resultDO = new ResultDO();
        $identity_id = gdl_lib_BaseUtils::getStr($identity_id);
        $search = gdl_lib_BaseUtils::getStr($search);
        $condition = array("identity_id=$identity_id and ex_basic.closed=1");
        if (!empty($search)){
            $condition = array("ex_basic.closed=1 and ex_basic_user.gdl_userid=$identity_id AND $search ");
        }
        try {
            $modelBasicUser = new model_newexam_basicuser();
            $res = $modelBasicUser->select(
                $condition,
                'ex_basic_user.basic_id basicid,ex_basic.basic,ex_basic.examid,ex_basic_setting.start_time,ex_basic_setting.end_time,ex_basic_setting.paperid,ex_basic_setting.examnumber',
                '',
                'order by ex_basic.id desc',
                array(
                'ex_basic' => 'ex_basic.id = ex_basic_user.basic_id',
                'ex_basic_setting' => 'ex_basic.id = ex_basic_setting.basicid',
                )
                )->items;
           if ($res){
               $resultDO->data = $res;
               $resultDO->code = 1;
           }else{
               $resultDO->code = 0;
           }

            $resultDO->success = true;
            return $resultDO;
        } catch (Exception $e) {
            $resultDO->success = false;
            $resultDO->code = $e->getCode();
        }
        $resultDO->notify_time = time();
        return $resultDO;
    }
}
