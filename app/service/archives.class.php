<?php
/**
 * 用户Service
 * @author yanghao <yh38615890@sina.cn>
 * @date 2017-04-27
 * @copyright (c) gandianli
 */
class service_archives extends gdl_components_baseservice
{
    public function __construct()
    {
        parent::__construct();
    }

    public function setArchives($paperid){
        if (empty($paperid)){
            return false;
        }
        $result = new \com\hlw\common\dataobject\common\ResultDO();
        try {

            $modelTest = new model_newexam_papersinfo();
            $userArchives = new model_newexam_archivesmark();
            $basicUser = new model_newexam_basicuser();
            $archivesUser = new model_newexam_archivesuser();
			//$paperid='3652,3653';
		
            if (explode(',',$paperid)) {//多个试卷
			 $userPapersInfo = $modelTest->select("ex_exams_papers.ep_id in ({$paperid})",
                    'ex_papers_info.paperid,ex_papers_info.company,ex_papers_info.modified,ex_papers_info.starttime,ex_papers_info.time,ex_papers_info.scorelist,ex_papers_info.score,ex_papers_info.passscore,ex_papers_info.total_score,ex_exams_papers.ep_type,ex_exams_papers.identity_id,ex_exams_papers.ep_paper_name,ex_exams_papers.question,ex_exams_papers.answer,ex_exams_papers.ispass,ex_exams_papers.isvalid,ex_exams_papers.basicid'
                    , '', '',
                    array(
						'ex_exams_papers' => 'ex_papers_info.id = ex_exams_papers.ep_infoId',
					
                    ))->items;
			

			return $userPapersInfo;
                $userPapersInfo = $modelTest->select("ex_exams_papers.ep_id in ({$paperid})",
                    'ex_papers_info.company,
                ex_papers_info.modified,
                ex_papers_info.starttime,
                ex_papers_info.time,
                ex_papers_info.scorelist,
                ex_papers_info.score,
                ex_papers_info.passscore,
                ex_papers_info.total_score,
                ex_exams_papers.ep_type,
                ex_exams_papers.identity_id,
                ex_exams_papers.ep_paper_name,
                ex_exams_papers.question,
                ex_exams_papers.answer,
                ex_exams_papers.ispass,
                ex_exams_papers.isvalid,
                ex_exams_papers.basicid,
                ex_user.realname,
                ex_user.idcard,
                ex_user_company.company_id,
                ex_user.username,
				ex_basic_user.event_name,
				ex_basic_user.eventid
                '
                    , '', '',
                    array(
                        'ex_exams_papers' => 'ex_papers_info.id = ex_exams_papers.ep_infoId',
                        'ex_user_company' => 'ex_user_company.id = ex_exams_papers.identity_id',
                        'ex_user' => 'ex_user_company.userid=ex_user.userid',
						'ex_basic_user' => 'ex_exams_papers.basicid=ex_basic_user.basic_id',
                    ))->items;

                if (empty($userPapersInfo)){
                    $result->success = false;
                    $result->code = 0;
                    return $result;
                }
					
                if (!empty($result)){
//                    $basic_identity_ids = [];//考试了的用户
//                    $identity_ids = [];//考试了的用户
                    $basicid = 0;
                    foreach ($userPapersInfo as $rr=>$value){
//                        $basic_identity_ids[] = $value['identity_id'];
                        if ($basicid == 0){
                            $basicid = $value['basicid'];
                        }
                    }
//                    $basic_identity_ids = array_unique($basic_identity_ids);

                    //获取正在考试人员 帮助自动交卷


                    //批量获取考场用户 start
//                    $identity_id_list = $basicUser->select('basic_id = ' . $basicid . ' AND isdelete=0 AND status=1 ', 'identity_id', '')->items;//应该考试的所有用户
//                    foreach ($identity_id_list as $value){
//                        $identity_ids[] = $value['identity_id'];
//                    }
//                    $no_basic_identity_ids = array_diff($identity_ids,$basic_identity_ids);

                    //根据考场id获取考场名
                    $modelBasic = new model_newexam_basic();
                    $basicInfo = @$modelBasic->select('ex_basic.id = ' . $basicid . ' AND ex_basic.closed=1 ', 'ex_basic.admin_reg,basic,ex_basic_setting.start_time,ex_basic_setting.end_time,ex_basic_setting.paperid', '', 'order by ex_basic.id desc', array('ex_basic_setting' => 'ex_basic_setting.basicid=ex_basic.id'))->items;


                    //根据未考试的用户id获取用户信息ep_userid
//                    $no_basic_identity_ids = implode(',',$no_basic_identity_ids);//根据用户id获取用户信息
//                    $modelUser = new model_newexam_user();
//                    $paperModel = new model_newexam_paper();
//                    $userInfo = $modelUser->select("ex_user_company.id in ({$no_basic_identity_ids})", 'ex_user_company.id as identity_id,ex_user.realname,ex_user.idcard,ex_user.username,ex_user_company.company_name,ex_user_company.company_id,ex_user_company.admin_reg', '', 'order by ex_user_company.userid desc',array('ex_user_company' => 'ex_user_company.userid=ex_user.userid'))->items;
//                    $paperInfo = $paperModel->selectOne("ex_paper.id ={$basicInfo[0]['paperid']}", 'p_name,p_duration,p_total_score,p_pass_score', '');
                    //批量获取用户基本信息 end

                    //批量导入档案表 start
                    foreach ($userPapersInfo as $key=>$value){
                        $data = [];
                        $userInfo_one = $userArchives->selectOne(" basic_id = {$basicid} and identity_id = {$value['identity_id']} and start_time={$value['starttime']}", 'id', '', '');
                        $data['user_idcard'] = $value['idcard'];
                        $data['username'] = $value['username'];
                        $data['identity_id'] = $value['identity_id'];
                        $data['basic_id'] = $basicid;
                        $data['realname'] = $value['realname'];
                        $data['company_id'] = $value['company_id'];
                        $data['paper_id'] = $basicInfo[0]['paperid'];
                        $data['basic_name'] = $basicInfo[0]['basic'];
                        if (empty($data['username']) && empty($data['company_id']) && empty($data['realname'])){
                            continue;
                        }
                        $data['user_company'] = $value['company'];
                        $data['user_score'] = $value['modified'];
//                $data['plaform'] = $userPapersInfo[0]['plaform'];
                        $data['start_time'] = $value['starttime'];
                        $data['paper_end_time'] = (int)$value['starttime']+(int)$value['time'];
                        $data['time'] = $value['time'];
						$data['event_name'] = $value['event_name'];
						$data['eventid'] = $value['eventid'];
                        $data['pass'] = $value['ispass'];
                        $data['user_questions'] = $value['question'];
                        $data['user_answer'] = $value['answer'];
                        $data['score_list'] = $value['scorelist'];
                        $data['passscore'] = $value['passscore'];
                        $data['paper_score'] = $value['total_score'];//试卷总分
                        $data['paper_precision'] = ceil(($value['score']/$value['total_score'])*100).'%';;//试卷正确率
                        $data['paper_name'] = $value['ep_paper_name'];
                        $data['admin_reg'] = $basicInfo[0]['admin_reg'];
                        $correct = 0;
                        $score_list = unserialize($value['scorelist']);
                        foreach ($score_list as $cc){
                            if ($cc!=0){
                                $correct+=1;
                            }
                        }
                        $data['pass_questions'] = $correct;//答对题数
                        $data['nopass_questions'] = count($score_list)-intval($correct);//答错题数
                        $data['isvalid'] = $value['isvalid'];
                        $data['type'] = $value['ep_type'];
                        $data['status'] = 1;
                        $data['isdelete'] = 0;
                        $data['create_time'] = time();
                        if ($userInfo_one){
                            $res = $userArchives->update(" basic_id = {$basicid} and identity_id = {$value['identity_id']} and start_time={$value['starttime']}",$data);
                        }else{
                            $res = $userArchives->insert($data);
                        }
                        $ar_u = $archivesUser->selectOne(
                            array('identity_id' => $value['identity_id']),
                            'id,basic_pass_percent,basic_avg_score,basic_num,basic_pass_num',
                            '',
                            ''
                        );
                        if ($value['ispass']==1){
                            //$userArchivesData['basic_pass_num'] = $ar_u['basic_pass_num']+1;
                        }else{
                            //$userArchivesData['basic_pass_num'] = $ar_u['basic_pass_num'];
                        }
                     //   $userArchivesData['basic_num'] = $ar_u['basic_num']+1;
                     //   $userArchivesData['basic_avg_score'] = ceil(($ar_u['basic_avg_score']+$value['modified'])/2);
                     //   $userArchivesData['basic_pass_percent'] = ceil(( $userArchivesData['basic_pass_num']/($ar_u['basic_num']+1))*100).'%';//考试合格率
                        if ($ar_u){
                            $res = $userArchives->update(" identity_id = {$value['identity_id']} ",$userArchivesData);
                        }else{
                            $userArchivesData['identity_id'] = $value['identity_id'];
                            $userArchivesData['username'] = $value['username'];
                            $userArchivesData['realname'] = $value['realname'];
                          //  $userArchivesData['idcard'] = $value['idcard'];
                         //   $userArchivesData['company_name'] = $value['company'];
                            $res = $userArchives->insert($userArchivesData);
                        }
                    }
			

                }
            }else{
                $userPapersInfo = $modelTest->select("ex_exams_papers.ep_id='{$paperid}'",
                    'ex_papers_info.*,
                ex_exams_papers.ep_type,
                ex_exams_papers.identity_id,
                ex_exams_papers.ep_paper_name,
                ex_exams_papers.question,
                ex_exams_papers.answer,
                ex_exams_papers.ispass,
                ex_exams_papers.isvalid,
                ex_exams_papers.basicid'
                    , '', '', array("ex_exams_papers" => "ex_papers_info.id = ex_exams_papers.ep_infoId"))->items;

                if (!empty($result)){
                    //根据用户id获取用户信息ep_userid
                    $identity_id = $userPapersInfo[0]['identity_id'];//根据用户id获取用户信息
                    $modelUser = new model_newexam_user();
                    $userInfo = $modelUser->selectOne(
                        array('ex_user_company.id' => $identity_id),
                        'ex_user.realname,ex_user.idcard,ex_user.username,ex_user_company.admin_reg',
                        '',
                        'order by ex_user_company.userid desc',
                        array('ex_user_company' => 'ex_user_company.userid=ex_user.userid')
                    );

                    $data['user_idcard'] = $userInfo['idcard'];
                    $data['username'] = $userInfo['username'];
                    $data['realname'] = $userInfo['realname'];

                    $basicid = $userPapersInfo[0]['basicid'];//根据考场id获取考场名

                    $modelBasic = new model_newexam_basic();
                    $basicInfo = @$modelBasic->select('ex_basic.id = ' . $basicid . ' AND ex_basic.closed=1 ', 'basic', '', 'order by ex_basic.id desc', array('ex_basic_setting' => 'ex_basic_setting.basicid=ex_basic.id'))->items;
                    $data['basic_name'] = $basicInfo[0]['basic'];
                    $data['start_time'] = $basicInfo[0]['start_time'];
                    $data['paper_end_time'] = $basicInfo[0]['end_time'];
                    $data['paper_id'] = $basicInfo[0]['paperid'];
                    $data['user_company'] = $userPapersInfo[0]['company'];
                    $data['user_score'] = $userPapersInfo[0]['modified'];
//                $data['plaform'] = $userPapersInfo[0]['plaform'];
                    $data['start_time'] = $userPapersInfo[0]['starttime'];
                    $data['time'] = $userPapersInfo[0]['time'];
                    $data['pass'] = $userPapersInfo[0]['ispass'];
                    $data['user_questions'] = $userPapersInfo[0]['question'];
                    $data['user_answer'] = $userPapersInfo[0]['answer'];
                    $data['score_list'] = $userPapersInfo[0]['scorelist'];
                    $data['passscore'] = $userPapersInfo[0]['passscore'];
                    $data['paper_name'] = $userPapersInfo[0]['ep_paper_name'];
                    $data['isvalid'] = $userPapersInfo[0]['isvalid'];
                    $data['type'] = $userPapersInfo[0]['ep_type'];
                    $data['paper_score'] = $userPapersInfo[0]['total_score'];//试卷总分
                    $data['paper_precision'] = ceil(($userPapersInfo[0]['score']/$userPapersInfo[0]['total_score'])*100).'%';;//试卷正确率
                    $data['paper_name'] = $userPapersInfo[0]['ep_paper_name'];
                    $correct = 0;
                    $score_list = unserialize($userPapersInfo[0]['scorelist']);
                    foreach ($score_list as $cc){
                        if ($cc!=0){
                            $correct+=1;
                        }
                    }
                    $data['pass_questions'] = $correct;//答对题数
                    $data['nopass_questions'] = count($score_list)-intval($correct);//答错题数
                    $data['status'] = 1;
                    $data['admin_reg'] = $userInfo['admin_reg'];
                    $data['isdelete'] = 0;
                    $data['create_time'] = time();
                    $res = $userArchives->insert($data);
                    $ar_u = $archivesUser->selectOne(
                        array('identity_id' => $userPapersInfo[0]['identity_id']),
                        'id,basic_pass_percent,basic_avg_score,basic_num,basic_pass_num',
                        '',
                        ''
                    );
                   // $userArchivesData['basic_pass_num'] = $ar_u['basic_pass_num'];
                  //  $userArchivesData['basic_num'] = $ar_u['basic_num']+1;
                  //  $userArchivesData['basic_avg_score'] = ceil(($ar_u['basic_avg_score']+$userPapersInfo[0]['modified'])/2);
                  //  $userArchivesData['basic_pass_percent'] = ceil(($userArchivesData['basic_pass_num']/($ar_u['basic_num']+1))*100).'%';//考试合格率
                    if ($ar_u){
                        $res = $userArchives->update(" identity_id = {$userPapersInfo[0]['identity_id']} ",$userArchivesData);
                    }else{
                        $userArchivesData['identity_id'] = $userPapersInfo[0]['identity_id'];
                        $userArchivesData['username'] = $userInfo['username'];
                        $userArchivesData['realname'] = $userInfo['realname'];
                        //$userArchivesData['idcard'] = $userInfo['idcard'];
                        //$userArchivesData['company_name'] = $userPapersInfo[0]['company'];
                        $res = $userArchives->insert($userArchivesData);
                    }
                }
            }

        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $res;
    }
}
