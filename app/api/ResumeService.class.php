<?php
/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 慧猎网职位发布/修改
 * User:
 * Date: 2019/7/16
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */

use  com\hlw\huilie\interfaces\resume\ResumeServiceIf;
use  com\hlw\huilie\dataobject\resume\ResumeRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_ResumeService extends api_Abstract implements ResumeServiceIf
{
    //1-未查看  2-已查看 3-不合适  4-已购买  5-邀约面试  6-顾问面试确认  7-候选人拒绝  8-待面试  9-未到场确认中 10-未到场  11-已到场  0-移除
    protected $resumeModel;
    protected $fineProject;
    protected $errMsg;
    protected $interviewSmsId = '';
    protected $eduList = [0 => '未知', 1 => '高中', 2 => '中专', 3 => '大专', 4 => '本科', 5 => '硕士', 6 => '博士', 7 => 'MBA/EMBA', 8 => '博士后'];
    protected $proType = [4, 8];
    protected $money = 1;
    protected $resumeId;
    protected $projectId;
    protected $uId;
    protected $otherData;

    public function __construct() {
        $this->resumeModel = new model_pinping_resume();
        $this->fineProject = new model_pinping_fineproject();
    }

    /**
     * @desc  简历详细信息
     * @desc  简历下载或者没到场才可以查看电话、邮箱 【11、4】
     * @param ResumeRequestDTO $resumeProjectDo
     * @return ResultDO
     */
    public function info(ResumeRequestDTO $resumeProjectDo) {
        $resultDo = new ResultDO();
        $resumeId = hlw_lib_BaseUtils::getStr($resumeProjectDo->resume_id, 'int');
        $projectId = hlw_lib_BaseUtils::getStr($resumeProjectDo->project_id, 'int');
        $this->resumeId = $resumeId;
        $this->projectId = $projectId;
        $resultDo->success = true;
        $resultDo->code = 500;
        if ($resumeId <= 0 || $projectId <= 0) {
            $resultDo->message = '缺少必传参数: resume_id/projectId';
            return $resultDo;
        }
        $projectInfo = $this->fineProjectInfo($resumeId, $projectId);
        if (!$projectInfo) {
            $resultDo->message = '项目简历不存在';
            return $resultDo;
        }
        $huilieStatus = $projectInfo['huilie_status'];
        $isShowContacts = false;
        if ($huilieStatus == 4 || $huilieStatus == 11 || $huilieStatus == 12) {
            $isShowContacts = true;
        }
        $list = $this->getResume($resumeId, $isShowContacts);
        if (!$list) {
            $resultDo->message = $this->errMsg;
            return $resultDo;
        }
        //隐藏联系信息
        $huilieStatus == 1 && $this->statusChange($resumeId, $projectId, 2);
        $userModel = new model_pinping_user();
        $userInfo = $userModel->selectOne(['role_id' => $projectInfo['role_id']], 'full_name,name');
        $businessModewl = new model_pinping_business();
        $businessInfo = $businessModewl->selectOne(['business_id' => $projectId], 'name');
        $list['work_info'] = [
            'user_name' => $userInfo['full_name'],
            'work_name' => $businessInfo['name'],
            'huilie_status' => $huilieStatus,
            'pro_type' => $businessInfo['pro_type'],
        ];
        $resultDo->code = 200;
        $resultDo->message = json_encode($list);
        return $resultDo;
    }

    /**
     * @desc  简历状态更新【不合适、购买、到场】
     * @param resumeRequestDTO $resumeProjectDo
     * @return ResultDO
     */
    public function statusUp(ResumeRequestDTO $resumeProjectDo) {
        $resumeId = hlw_lib_BaseUtils::getStr($resumeProjectDo->resume_id, 'int');
        $status = hlw_lib_BaseUtils::getStr($resumeProjectDo->status, 'int');
        $projectId = hlw_lib_BaseUtils::getStr($resumeProjectDo->project_id, 'int');
        $uid = hlw_lib_BaseUtils::getStr($resumeProjectDo->uid, 'int');
        $this->otherData = hlw_lib_BaseUtils::getStr($resumeProjectDo->others);
        $this->money = $resumeProjectDo->money ? hlw_lib_BaseUtils::getStr($resumeProjectDo->money, 'int') : 1;
        $this->resumeId = $resumeId;
        $this->projectId = $projectId;
        $this->uId = $uid;
        $resultDo = new ResultDO();
        $resultDo->success = true;
        $resultDo->code = 500;
        if (!$resumeId || !$projectId) {
            $resultDo->message = '缺少必传参数: resume_id/projectId';
            return $resultDo;
        }
        $fineInfo = $this->fineProjectInfo($resumeId, $projectId);
        if (!$fineInfo) {
            $resultDo->message = '数据不存在';
            return $resultDo;
        }
        try {
//            $this->fineProject->beginTransaction();
            $res = $this->statusChange($resumeId, $projectId, $status);
            if ($res !== false) {
                $status == 3 && $this->resumeReject($fineInfo); //简历不合适
                $status == 4 && $this->resumeBuy($fineInfo, $uid, $this->money); //购买记录记录
                $status == 11 && $this->present($fineInfo, 1, $uid, $this->money); //到场记录
                $status == 9 && $this->present($fineInfo, 0, $uid, $this->money); //到场记录
                $resultDo->success = true;
                $resultDo->code = 200;
                $resultDo->message = '操作成功';
                return $resultDo;
            }
//            $this->fineProject->commit();
        } catch (Exception $e) {
//            $this->fineProject->rollBack();
            $resultDo->message = $e->getMessage();
            return $resultDo;
        }
        $resultDo->message = $this->errMsg ? $this->errMsg : '操作失败1';
        return $resultDo;
    }

    /**
     * @desc  项目操作流程
     * @param ResumeRequestDTO $resumeProjectDo
     * @return ResultDO
     */
    public function projectLog(ResumeRequestDTO $resumeProjectDo) {
        $resumeId = hlw_lib_BaseUtils::getStr($resumeProjectDo->resume_id, 'int');
        $projectId = hlw_lib_BaseUtils::getStr($resumeProjectDo->project_id, 'int');
        $this->resumeId = $resumeId;
        $this->projectId = $projectId;
        $resultDo = new ResultDO();
        $resultDo->success = true;
        $resultDo->code = 500;
        if (!$resumeId || !$projectId) {
            $resultDo->message = '缺少必传参数: resume_id/projectId';
            return $resultDo;
        }
        $fineInfo = $this->fineProjectInfo($resumeId, $projectId);
        if (!$fineInfo) {
            $resultDo->message = '项目简历不存在';
            return $resultDo;
        }
        $fineId = $fineInfo['id'];
        $roleId = $fineInfo['tj_role_id'] > 0 ? $fineInfo['tj_role_id'] : $fineInfo['tracker'];
        $userInfo = $this->userInfo($roleId);
        $userName = $userInfo['full_name'];
        $where = ['fine_id' => $fineId];
        $list = [];
        //1、顾问推荐简历
        $tjList = ['role_id' => $roleId, 'step' => 'tj', 'user_name' => $userName, 'add_time' => date('m-d H:i', $fineInfo['tjaddtime']), 'title' => '顾问推荐候选人'];
        $list[$tjList['add_time']] = ['tj' => $tjList];
        //2、HR确认简历 [面试/不合适]
        $bhsModel = new model_pinping_fineprojectbhs();
        $bhsInfo = $bhsModel->selectOne($where, '*', '', 'order by id desc');
        if ($bhsInfo) {
            $bhsInfo['step'] = 'bhs';
            $bhsInfo['title'] = '企业标记候选人不合适';
            $bhsInfo['role_id'] = $roleId;
            $bhsInfo['add_time'] = date('m-d H:i', $bhsInfo['addtime']);
            $list[$bhsInfo['add_time']] = ['bhs' => $bhsInfo];
        }
        //3、HR确认面试
        $interview = new model_pinping_fineprojectinterview();
        $interviewWhere = $where;
        $interviewWhere['is_from_hr'] = 1;
        $interviewInfo = $interview->selectOne($interviewWhere, '*', '', 'order by id desc');
        if ($interviewInfo) {
            $interviewInfo['step'] = 'interview';
            $interviewInfo['title'] = '企业发起面试邀请';
            $interviewInfo['role_id'] = $roleId;
            $interviewInfo['add_time'] = date('m-d H:i', $interviewInfo['addtime']);
            $list[$interviewInfo['add_time']] = ['hr_interview' => $interviewInfo];
        }
        //4、顾问确认面试
        $interviewWhere['is_from_hr'] = 0;
        $interviewGwInfo = $interview->selectOne($interviewWhere, '*', '', 'order by id desc');
        if ($interviewGwInfo) {
            $interviewGwInfo['step'] = 'interview';
            $interviewGwInfo['title'] = '顾问确认候选人最终面试信息';
            $interviewGwInfo['role_id'] = $roleId;
            $interviewGwInfo['user_name'] = $userName;
            $interviewGwInfo['add_time'] = date('m-d H:i', $interviewGwInfo['addtime']);
            $list[$interviewGwInfo['add_time']] = ['interview' => $interviewGwInfo];
        }
        //5、购买
        $payModel = new model_pinping_fineresumebuy();
        $buyInfo = $payModel->selectOne($where, '*', '', 'order by id desc');
        if ($buyInfo) {
            $buyInfo['step'] = 'buy';
            $buyInfo['title'] = '企业购买简历';
            $buyInfo['role_id'] = $roleId;
            $buyInfo['user_name'] = $userName;
            $buyInfo['add_time'] = date('m-d H:i', $buyInfo['add_time']);
            $list[$buyInfo['add_time']] = ['buy' => $buyInfo];
        }
        //6、到场扣币
        $present = new model_pinping_fineprojectpresent();
        $presentWhere = $where;
        $presentWhere['is_present'] = 1;
        $presentInfo = $present->selectOne($presentWhere, '*', '', 'order by id desc');
        if ($presentInfo) {
            $presentInfo['step'] = 'present';
            $presentInfo['title'] = '候选人到场，扣除慧猎币';
            $presentInfo['role_id'] = $roleId;
            $presentInfo['user_name'] = $userName;
            $presentInfo['add_time'] = date('m-d H:i', $presentInfo['add_time']);
            $list[$presentInfo['add_time']] = ['present' => $presentInfo];
        }
        krsort($list);
        $return = [];
        if ($list) {
            foreach ($list as $info) {
                foreach ($info as $data) {
                    $return[] = $data;
                }
            }
        }
        $resultDo->code = 200;
        $resultDo->message = json_encode($return);
        return $resultDo;
    }

    /**
     * @desc 面试预约【HR发起】
     *  给顾问发送短信/站内信
     * @param resumeRequestDTO $resumeRequestDo
     * @return ResultDO
     */
    public function orderInterview(ResumeRequestDTO $resumeRequestDo) {
        $resultDo = new ResultDO();
        $resumeId = hlw_lib_BaseUtils::getStr($resumeRequestDo->resume_id, 'int');
        $projectId = hlw_lib_BaseUtils::getStr($resumeRequestDo->project_id, 'int');
        $time = hlw_lib_BaseUtils::getStr($resumeRequestDo->interview_time, 'int');
        $note = hlw_lib_BaseUtils::getStr($resumeRequestDo->interview_note);
        $address = hlw_lib_BaseUtils::getStr($resumeRequestDo->interview_address);
        $interviewer = hlw_lib_BaseUtils::getStr($resumeRequestDo->interviewer);
        $this->money = $resumeRequestDo->money ? hlw_lib_BaseUtils::getStr($resumeRequestDo->money, 'int') : 1;
        $this->resumeId = $resumeId;
        $this->projectId = $projectId;
        $uid = hlw_lib_BaseUtils::getStr($resumeRequestDo->uid);
        $this->uId = $uid;
        $fineInfo = $this->fineProjectInfo($resumeId, $projectId);
        $resultDo->success = true;
        $resultDo->code = 500;
        if (!$fineInfo) {
            $resultDo->message = '不存在';
            return $resultDo;
        }
        $resumeInfo = $this->resumeModel->selectOne(['eid' => $resumeId], 'name');
        $resumeName = $resumeInfo['name'];
        $business = new model_pinping_business();
        $businessInfo = $business->selectOne(['business_id' => $projectId], 'name');
        $businessName = $businessInfo['name'];
        $fineId = $fineInfo['id'];
        $tracker = $fineInfo['tj_role_id'] ? $fineInfo['tj_role_id'] : $fineInfo['tracker'];
        $interviewModel = new model_pinping_fineprojectinterview();
        if ($interviewModel->selectOne(['fine_id' => $fineId, 'interview' => 1, 'is_from_hr' => 1])) {
            $resultDo->code = 200;
            $resultDo->message = '提交成功';
            return $resultDo;
        }
        $address = $this->characet($address);
        $interviewer = $this->characet($interviewer);
        $note = $this->characet($note);
        $data = [
            'fine_id' => $fineId,
            'name' => $resumeName,
            'job_name' => $businessName,
            'interview' => 1,
            'timestart' => $time ? date('Y-m-d H:i', $time) : '',
            'interview_place' => $address,
            'status_type' => $interviewer,
            'addtime' => time(),
            'end' => 0,
            'role_id' => $tracker,
            'is_from_hr' => 1,
            'description' => $note ? $note : '企业发起面试邀约',
        ];
        try {
//            $interviewModel->beginTransaction();
            $interviewModel->insert($data);
            $this->statusChange($resumeId, $projectId, 5);
            $this->companyCoinUp($uid, 2, $this->money);
            $this->sentMess($fineId, 'interview', $this->interviewSmsId);

//            $interviewModel->commit();
            $resultDo->code = 200;
            $resultDo->message = '提交成功';
            return $resultDo;
        } catch (Exception $e) {
//            $interviewModel->rollBack();
            $resultDo->code = 500;
            $resultDo->message = '提交失败';
            return $resultDo;
        }
    }

    /**
     * @desc 简历下载
     * @param ResumeRequestDTO $resumeRequestDo
     * @return ResultDO
     */
    public function download(ResumeRequestDTO $resumeRequestDo) {
        $resultDo = new ResultDO();
        $resultDo->success = true;
        $resultDo->code = 500;

        $resumeId = hlw_lib_BaseUtils::getStr($resumeRequestDo->resume_id, 'int');
        $projectId = hlw_lib_BaseUtils::getStr($resumeRequestDo->project_id, 'int');
        $this->resumeId = $resumeId;
        $this->projectId = $projectId;
        $projectInfo = $this->fineProjectInfo($resumeId, $projectId);
        $fineId = $projectInfo['id'];
        if (!$projectInfo) {
            $resultDo->message = '获取失败';
            return $resultDo;
        }
        $isBuy = false;
        $huilieStatus = $projectInfo['huilie_status'];
        if ($huilieStatus == 4 || $huilieStatus == 11) {
            $isBuy = true;
        }
        $resumeInfo = $this->getResume($resumeId, $isBuy);
        if (!$resumeInfo) {
            $resultDo->message = $this->errMsg;
            return $resultDo;
        }
        //已下载
        $this->fineProject->update(['id' => $fineId], ['is_load' => 1]);
        $resultDo->code = 200;
        $resultDo->message = json_encode($resumeInfo);
        return $resultDo;
    }

    /**
     * @desc  状态更改
     * @param $resumeId
     * @param $projectId
     * @param $vale
     * @return bool
     */
    private function statusChange($resumeId, $projectId, $vale) {
        $where = ['resume_id' => $resumeId, 'project_id' => $projectId];
        $data = ['updatetime' => time(), 'huilie_status' => intval($vale)];
        $fineInfo = $this->fineProjectInfo($resumeId, $projectId);
        $hlStatus = $fineInfo['huilie_status'];
        //移除状态
        if ($vale == 0 && ($hlStatus != 7 && $hlStatus != 0)) {
            $this->errMsg = "当前状态不能移除操作";
            return false;
        }
        //购买操作
        if ($vale == 4 && ($hlStatus != 4 && $hlStatus > 4)) {
            $this->errMsg = "当前状态不能购买操作";
            return false;
        }
        if (($vale == 10 || $vale == 11 || $vale == 9) && ($hlStatus != 6 && $hlStatus != 8 && $hlStatus != 10 && $hlStatus != 11 && $hlStatus != 9)) {
            $this->errMsg = "当前状态不能到场操作";
            return false;
        }
        $infoWhere = $where;
        $infoWhere['huilie_status'] = intval($vale);
        if ($this->fineProject->selectOne($infoWhere)) {
            return true;
        }
        return $this->fineProject->update($where, $data);
    }

    /**
     * @desc  获取项目信息
     * @param $resumeId
     * @param $projectId
     * @param string $filed
     * @return array|bool
     */
    private function fineProjectInfo($resumeId, $projectId, $filed = 'id,tj_role_id,tracker,huilie_status,status,resume_id,com_id,project_id,tjaddtime') {
        if (!$resumeId || !$projectId) {
            return false;
        }
        $where = ['resume_id' => $resumeId, 'project_id' => $projectId,];
        $info = $this->fineProject->selectOne($where, $filed, '', 'order by id desc');
        $info['role_id'] = $info['tj_role_id'] ? $info['tj_role_id'] : $info['tracker'];
        return $info ? $info : [];
    }

    /**
     * @desc  不合适信息添加
     * @param $fineInfo
     * @return int
     */
    private function resumeReject($fineInfo) {
        $resumeBhs = new model_pinping_fineprojectbhs();
        $data = [
            'fine_id' => $fineInfo['id'],
            'status' => $fineInfo['status'],
            'reason' => 'HR标记为不合适',
            'addtime' => time(),
            'role_id' => $fineInfo['tj_role_id'] ? $fineInfo['tj_role_id'] : $fineInfo['tracker'],
        ];
        $infoWhere = ['fine_id' => $fineInfo['id'], 'status' => $fineInfo['status']];
        if ($resumeBhs->selectOne($infoWhere)) {
            return true;
        }
        return $resumeBhs->insert($data);
    }

    /**
     * @desc 简历购买操作记录
     * @param $fineInfo
     * @param $uid
     * @param int $coin
     * @return bool|int
     * @throws Exception
     */
    private function resumeBuy($fineInfo, $uid, $coin = 3) {
        $fineId = $fineInfo['id'];
        $roleId = $fineInfo['tj_role_id'] ? $fineInfo['tj_role_id'] : $fineInfo['tracker'];
        $resumeId = $fineInfo['resume_id'];
        $resumeBug = new model_pinping_fineresumebuy();
        $resumeInfo = $this->resumeModel->selectOne(['eid' => $resumeId], 'name,wantsalary,curSalary');
        $info = $resumeBug->selectOne(['fine_id' => $fineId], 'id');
        if ($info) {
            return true;
        }
        $businessModel = new  model_pinping_business();
        $companyJobModel = new model_huiliewang_companyjob();
        $businessInfo = $businessModel->selectOne(['business_id' => $this->projectId], 'huilie_job_id,business_id');
        $huilieJobId = $businessInfo['huilie_job_id'];
        $huilieJobInfo = $companyJobModel->selectOne(['id' => $huilieJobId], 'id,service_type,job_type');
        $type = 1;
        if ($huilieJobInfo['job_type'] == 2) {
            $type = 5;
        }
        if (!$this->companyCoinUp($uid, $type, $coin)) {
            return false;
        }
        //购买记录
        $data = [
            'fine_id' => $fineId,
            'status' => $fineInfo['status'],
            'resume_id' => $resumeId,
            'resume_name' => $resumeInfo['name'],
            'salary' => $resumeInfo['wantsalary'] > 0 ? $resumeInfo['wantsalary'] : $resumeInfo['curSalary'],
//            'role_id' => $roleId,
            'huilie_coin' => $coin,
            'add_time' => time(),
        ];
        $this->connectData($fineId);
        return $resumeBug->insert($data);
    }

    /**
     * @desc 获取简历信息
     * @param $resumeId
     * @param bool $isBuy
     * @return array|bool
     */
    private function getResume($resumeId, $isBuy = false) {
        $filed = "eid,name,email,telephone,industry,job_class,sex,edu,location,wantsalary,curSalary,startWorkyear,birthday,birthYear,marital_status,curCompany,curPosition,intentCity,curStatus";
        $resumeInfo = $this->resumeModel->selectOne(['eid' => $resumeId], $filed);
        if (!$resumeInfo) {
            $this->errMsg = '简历获取失败';
            return false;
        }
        $sexs = [1 => '男', 2 => '女', 0 => '未知'];
        $maritals = [1 => '未婚', 2 => '已婚', 3 => '保密', 0 => '未知'];
        $resumeInfo['sex'] = $sexs[$resumeInfo['sex']];
        $resumeInfo['marital_status'] = $maritals[$resumeInfo['marital_status']];
        $resumeInfo['work_year'] = $resumeInfo['startWorkyear'] > 0 ? date('Y') - $resumeInfo['startWorkyear'] : 0;
        $resumeInfo['age'] = $resumeInfo['birthYear'] > 0 ? date('Y') - $resumeInfo['birthYear'] : 0;
        $resumeInfo['industry'] = $this->industryName($resumeInfo['industry']);
        $resumeInfo['job_class'] = $this->jobclassName($resumeInfo['job_class']);
        $resumeInfo['intentCity'] = $this->cityName($resumeInfo['intentCity']);
        $resumeInfo['location'] = $this->cityName($resumeInfo['location']);
        //沟通结果
        $connectInfo = $this->connectResult($resumeInfo);
        $connect_result = $connectInfo['connect_result'];
        $resumeInfo = $connectInfo['resume'];
        //隐藏联系信息
        if (!$isBuy) {
            $resumeInfo['telephone'] = '************';
            $resumeInfo['email'] = '************';
        }
        $where = ['eid' => $resumeId];
        //项目经验
        $projectModel = new model_pinping_resumeproject();
        $projectList = $projectModel->select($where, '*', '', 'order by id desc');
        $projectList = $projectList ? $projectList->items : [];
        foreach ($projectList as &$info) {
            $info['starttime'] = $info['starttime'] > 0 ? date("Y/m", $info['starttime']) : '未知';
            $info['endtime'] = $info['endtime'] > 0 ? date("Y/m", $info['endtime']) : '至今';
            $info['project_time'] = $info['starttime'] . '-' . $info['endtime'];
        }

        //工作经验
        $workModel = new model_pinping_resumework();
        $workList = $workModel->select($where, '*', '', 'order by id desc');
        $workList = $workList ? $workList->items : [];
        foreach ($workList as &$info) {
            $info['starttime'] = $info['starttime'] ? date("Y/m", $info['starttime']) : '未知';
            $info['endtime'] = $info['endtime'] ? date("Y/m", $info['endtime']) : '至今';
            $info['starttime'] && $info['work_time'] = $info['starttime'] . '-' . $info['endtime'];
        }
        //教育经验
        $eduModel = new model_pinping_resumeedu();
        $eduList = $eduModel->select($where, '*', '', 'order by id desc');
        $eduList = $eduList ? $eduList->items : [];
        foreach ($eduList as &$info) {
            $info['starttime'] && $info['starttime'] = $info['starttime'] > 0 ? date("Y/m", $info['starttime']) : '未知';
            $info['endtime'] && $info['endtime'] = $info['endtime'] > 0 ? date("Y/m", $info['endtime']) : '未知';
            $info['degree'] && $info['degree'] = $this->eduList[$info['degree']] ? $this->eduList[$info['degree']] : '未知';
            $info['starttime'] && $info['edu_time'] = $info['starttime'] . '-' . $info['endtime'];
        }
        $school = $eduList ? $eduList[0] : [];
        $schoolName = isset($school['schoolName']) ? $school['schoolName'] : '';
        $resumeInfo['school_name'] = $schoolName;
        return [
            'info' => $resumeInfo,
            'project' => $projectList,
            'work' => $workList,
            'edu' => $eduList,
            'connect_result' => $connect_result
        ];
    }

    /**
     * @desc 到场确认
     * @param $fineInfo
     * @param int $isPresent
     * @param $uid
     * @param int $coin
     * @return bool|int
     * @throws Exception
     */
    private function present($fineInfo, $isPresent = 1, $uid, $coin = 3) {
        $fineId = $fineInfo['id'];
        $roleId = $fineInfo['tj_role_id'] ? $fineInfo['tj_role_id'] : $fineInfo['tracker'];
        $resumeId = $fineInfo['resume_id'];
        $present = new model_pinping_fineprojectpresent();
        $resumeInfo = $this->resumeModel->selectOne(['eid' => $resumeId], 'name,wantsalary,curSalary');
        $info = $present->selectOne(['fine_id' => $fineId], 'id');
        if ($info) {
            return true;
        }
        if ($isPresent) {
            //d到场
            if (!$this->companyCoinUp($uid, 3, $coin)) {
                return false;
            }
        } else {
            if (!$this->companyCoinUp($uid, 4, $coin)) {
                return false;
            }
            $coin = -$coin;
        }
        $data = [
            'fine_id' => $fineId,
            'status' => $fineInfo['status'],
            'resume_id' => $resumeId,
            'resume_name' => $resumeInfo['name'],
            'salary' => $resumeInfo['wantsalary'] > 0 ? $resumeInfo['wantsalary'] : $resumeInfo['curSalary'],
            'role_id' => $roleId,
            'huilie_coin' => $coin,
            'add_time' => time(),
            'is_present' => $isPresent,
            'is_from_hr' => 1,
        ];
        return $present->insert($data);
    }

    /**
     * @desc   短信推送 @todo 模板确认
     * @param $fineId
     * @param $type
     * @param $tempId
     * @return bool
     */
    private function sentMess($fineId, $type, $tempId) {
        //
        $fineInfo = $this->fineProject->selectOne(['id' => $fineId], 'id,tj_role_id,tracker,huilie_status,status,resume_id,com_id,project_id');
        $roleId = $fineInfo['tj_role_id'] ? $fineInfo['tj_role_id'] : $fineInfo['tracker'];
        $businessId = $fineInfo['project_id'];
        $com_id = $fineInfo['com_id'];
        $resume_id = $fineInfo['resume_id'];

        $messageMode = new model_pinping_message();
        $userModel = new model_pinping_user();

        $userInfo = $userModel->selectOne(['role_id' => $roleId], 'telephone,full_name');
        $phone = $userInfo['telephone'];
        $customer = new model_pinping_customer();
        $customerInfo = $customer->selectOne(['customer_id' => $com_id], "name");
        $customerName = $customerInfo['name'];
        $smsContent = "";
        if ('interview' == $type) {
//            $smsContent = [123456, 4];
        }
        //发送系统消息
        $messageData = [
            'to_role_id' => $roleId,
            'send_time' => time(),
            'from_role_id' => 0,
            'degree' => 1,
            'content' => "慧猎客户《{$customerName}》，选择了预约面试，请跟进"
        ];
        $res = $messageMode->insert($messageData);
        //短信发送
        $config = [];
        $tempId && $config['templateId'] = $tempId;
        $smsObj = new STxSms($config);
        $smsContent && $smsObj->sentTemOne($phone, $smsContent);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * @desc 获取用户信息
     * @param $roleId
     * @return array
     */
    private function userInfo($roleId) {
        $userModel = new model_pinping_user();
        $userInfo = $userModel->selectOne(['role_id' => $roleId], 'role_id,user_id,full_name');
        return $userInfo ? $userInfo : [];
    }

    /**
     * @desc  城市信息
     * @param $code
     * @return string
     */
    private function cityName($code) {
        $city = new model_pinping_city();
        $cityInfo = $city->selectOne(['city_id' => $code], 'name');
        return $cityInfo['name'] ? $cityInfo['name'] : '';
    }

    /**
     * @desc 行业
     * @param $code
     * @return string
     */
    private function industryName($code) {
        $industry = new model_pinping_industry();
        $industryInfo = $industry->selectOne(['industry_id' => $code], 'name');
        return $industryInfo['name'] ? $industryInfo['name'] : '';
    }

    /**
     * @desc 职能
     * @param $code
     * @return string
     */
    private function jobclassName($code) {
        $jobClass = new model_pinping_jobclass();
        $jobInfo = $jobClass->selectOne(['job_id' => $code], 'name');
        return $jobInfo['name'] ? $jobInfo['name'] : '';
    }

    /**
     * @desc  慧币扣除
     * @param $uid
     * @param int $type
     * @param int $coin
     * @return bool
     * @throws Exception
     */
    private function companyCoinUp($uid, $type = 1, $coin = 1) {
        $compny = new model_huiliewang_company();
        $where = ['uid' => $uid];
        $data = [];
        $companyInfo = $compny->selectOne($where, "resume_payd,interview_payd,interview_payd_expect,resume_payd_high");
        $serviceType = 1;
        $payStatus = 0;
        $jobType = 1;
        if ($type == 1) {
            //购买简历
            $downCoin = $companyInfo['resume_payd'];
            if ($downCoin < $coin) {
                $this->errMsg = '慧沟通点数不够';
                throw new \Exception($this->errMsg);
                return false;
            }
            $downCoinNow = $downCoin - $coin;
            $data = ['resume_payd' => $downCoinNow];
            $serviceType = 0;
            $payStatus = 2;
            $jobType = 1;
        }
        if ($type == 5) {
            //购买简历
            $downCoin = $companyInfo['resume_payd_high'];
            if ($downCoin < $coin) {
                $this->errMsg = '慧沟通高级点数不够';
                throw new \Exception($this->errMsg);
                return false;
            }
            $downCoinNow = $downCoin - $coin;
            $data = ['resume_payd_high' => $downCoinNow];
            $serviceType = 0;
            $payStatus = 2;
            $jobType = 2;
        }
        //到场
        $interview_payd = $companyInfo['interview_payd'];
        $interview_payd_expect = $companyInfo['interview_payd_expect'];
        $interviewJobType = 1;
        if($coin == 3000){
            $interviewJobType = 2;
        }
        if($coin == 4000){
            $interviewJobType = 3;
        }
        if ($type == 3) {
            //到场
            $interview_paydNow = $interview_payd - $coin;
            $interview_payd_expect = $interview_payd_expect + $coin;
            $data = [
                'interview_payd' => intval($interview_paydNow),
                'interview_payd_expect' => intval($interview_payd_expect)
            ];
            $payStatus = 2;
            $jobType = $interviewJobType;
        }
        if ($type == 2) {
            //邀约面试
            $interview_payd_expect = $interview_payd_expect - $coin;
            $data = [
                'interview_payd_expect' => intval($interview_payd_expect)
            ];
            $payStatus = 3;
            $jobType = $interviewJobType;
        }
        if ($type == 4) {
            //未到场
            $interview_payd_expect = $interview_payd_expect + $coin;
            $data = [
                'interview_payd_expect' => intval($interview_payd_expect),
            ];
            $jobType = $interviewJobType;
        }
        if (!$data) {
            return false;
        }
        if ($type == 4) {
            return true;
        }
        //慧猎网订单记录
        $this->companyPayLog($coin, $serviceType, $payStatus, $jobType);
        return $compny->update($where, $data);
    }

    /**
     * @desc  账户变动记录
     * @param $coin
     * @param $type
     * @param $status
     * @param $jobType
     * @return int
     */
    private function companyPayLog($coin, $type, $status, $jobType = 0) {
        $companyPay = new model_huiliewang_companypay();
        $sn = time() . rand(10000, 99999);
        $resume = new model_pinping_resume();
        $business = new model_pinping_business();
        $resumeInfo = $resume->selectOne(['eid' => $this->resumeId], 'name');
        $resumeName = $resumeInfo['name'] ? $resumeInfo['name'] : '';
        $businessInfo = $business->selectOne(['business_id' => $this->projectId], 'name');
        $businessName = $businessInfo['name'] ? $businessInfo['name'] : '';
        $mark = $type == 0 ? "慧沟通" : '慧面试';
        $mark .= $status == 3 ? "预扣" : '扣除';
        $data = [
            'resume' => $resumeName,
            'resume_id' => $this->resumeId,
            'job_id' => $this->projectId,
            'job' => $businessName,
            'order_id' => $sn,
            'order_price' => $coin,
            'pay_time' => time(),
            'pay_state' => $status, // 1成功，2、预扣
            'com_id' => $this->uId,
            'pay_remark' => $mark,
            'type' => $type,
            'job_type' => $jobType,
            'pay_type' => 0,
        ];
        $where = ['resume_id' => $this->resumeId, 'job_id' => $this->projectId];
        if ($companyPay->selectOne($where)) {
            $companyPay->update($where, $data);
        } else {
            return $companyPay->insert($data);
        }
        return true;
    }

    /**
     * @desc  简历都买详情
     * @param ResumeRequestDTO $resumeRequestDo
     * @return ResultDO
     */
    public function jobResumeDetail(ResumeRequestDTO $resumeRequestDo) {
        $resultDo = new ResultDO();
        $resultDo->success = true;
        $resultDo->code = 500;
        $resumeId = hlw_lib_BaseUtils::getStr($resumeRequestDo->resume_id, 'int');
        $projectId = hlw_lib_BaseUtils::getStr($resumeRequestDo->project_id, 'int');
        $this->resumeId = $resumeId;
        $this->projectId = $projectId;
        $uid = hlw_lib_BaseUtils::getStr($resumeRequestDo->uid, 'int');
        $resumeInfo = $this->getResume($resumeId, true);
        $business = new  model_pinping_business();
        $huilieCompny = new model_huiliewang_company();
        $huilieJob = new model_huiliewang_companyjob();
        $companyInfo = $huilieCompny->selectOne(['uid' => $uid], 'payd,resume_payd,interview_payd,interview_payd_expect,resume_payd_high');
        $businessInfo = $business->selectOne(['business_id' => $projectId], 'maxsalary,minsalary,pro_type,name,huilie_job_id');
        $hulieJobId = $businessInfo['huilie_job_id'];
        $huilieJobInfo = $huilieJob->selectOne(['id' => $hulieJobId], 'id,service_type,job_type');
        $serviceType = $huilieJobInfo['service_type'];
        $jobType = $huilieJobInfo['job_type'];
        $money = 1;
        if ($businessInfo['pro_type'] == 8) {
            $surplus = $companyInfo['interview_payd'] + $companyInfo['interview_payd_expect'];
        } else {
            $surplus = $jobType == 2 ? $companyInfo['resume_payd_high'] : $companyInfo['resume_payd'];
        }
        $salary = $businessInfo['maxsalary'];
        $priceConfig = json_decode(new_price, true);
        if ($businessInfo['pro_type'] == 8) {
            $newPriceInterview = $priceConfig['interview'];
            $keyInterview = '0-20';
            ($salary >= 20 && $salary < 50) && $keyInterview = '20-50';
            $salary > 50 && $keyInterview = '50-9999999';
            $money = $newPriceInterview[$keyInterview]['price'];
        } else {
            $newPriceResume = $priceConfig['communicate'];
            $jobType == 2 && $money == $newPriceResume['expert']['deduct'];
            $jobType == 1 && $money == $newPriceResume['base']['deduct'];
        }
        $data = [
            'project_id' => $projectId,
            'resume_id' => $resumeId,
            'name' => $resumeInfo['info']['name'] ? $resumeInfo['info']['name'] : '',
            'pro_type' => $businessInfo['pro_type'],
            'job_name' => $businessInfo['name'] ? $businessInfo['name'] : '',
            'salary' => $businessInfo['maxsalary'] ? $businessInfo['maxsalary'] : '',
            'money' => $money,
            "surplus" => intval($surplus),
            "job_type" => $huilieJobInfo['job_type'],
            "service_type" => $serviceType
        ];
        $resultDo->code = 200;
        $resultDo->message = json_encode($data);
        return $resultDo;
    }

    /**
     * @desc  备注信息
     * @param ResumeRequestDTO $resumeRequestDo
     * @return ResultDO
     */
    public function note(\com\hlw\huilie\dataobject\resume\ResumeRequestDTO $resumeRequestDo) {
        $resumeId = hlw_lib_BaseUtils::getStr($resumeRequestDo->resume_id, 'int');
        $projectId = hlw_lib_BaseUtils::getStr($resumeRequestDo->project_id, 'int');
        $fineInfo = $this->fineProjectInfo($resumeId, $projectId);
        $resultDo = new ResultDO();
        $resultDo->success = true;
        $resultDo->code = 500;
        if (!$fineInfo) {
            $resultDo->message = '项目简历不存在';
            return $resultDo;
        }
        $fineId = $fineInfo['id'];
        $status = $fineInfo['huilie_status'];
        //拒绝面试
        $resultDo->code = 200;
        if (7 == $status) {
            $bhs = new model_pinping_fineprojectbhs();
            $info = $bhs->selectOne(['fine_id' => $fineId], '', '', 'order by id desc');
            $resultDo->message = json_encode($info);
            return $resultDo;
        }
        //邀约面试
        if (5 == $status) {
            $interview = new model_pinping_fineprojectinterview();
            $info = $interview->selectOne(['fine_id' => $fineId, 'is_from_hr' => 1], '', '', 'order by id desc');
            $resultDo->message = json_encode($info);
            return $resultDo;
        }
        //确认面试
        if (6 == $status) {
            $interview = new model_pinping_fineprojectinterview();
            $info = $interview->selectOne(['fine_id' => $fineId, 'is_from_hr' => 0], '', '', 'order by id desc');
            $resultDo->message = json_encode($info);
            return $resultDo;
        }
        $resultDo->code = 500;
        $resultDo->message = '没找到对应状态';
        return $resultDo;
    }

    /**
     * 编码转换
     * @param $data
     * @param string $charSet
     * @return string
     */
    function characet($data, $charSet = 'UTF-8') {
        if (!empty($data)) {
            $fileType = mb_detect_encoding($data, array('UTF-8', 'GBK', 'LATIN1', 'BIG5'));
            if ($fileType != $charSet) {
                $data = mb_convert_encoding($data, $charSet, $fileType);
            }
        }
        return $data;
    }

    /**
     * @desc 沟通字段
     * @param $fineId
     * @return int
     */
    private function connectData($fineId) {
        $others = json_encode($this->otherData);
        $data = [
            'fine_id' => $fineId,
            'eid' => $this->resumeId,
            'create_time' => time(),
            'update_time' => time(),
            'optional_fields' => $others
        ];
        $blendingModel = new model_pinping_resumeblending();
        return $blendingModel->insert($data);

    }

    /**
     * @desc  沟通结果
     * @param  array $resumeInfo
     * @return array
     */
    private function connectResult($resumeInfo) {
        $fineInfo = $this->fineProjectInfo($this->resumeId, $this->projectId);
        $data = ['resume' => $resumeInfo, 'connect_result' => []];
        if (!$fineInfo || $fineInfo['huilie_status'] != 12) {
            return $data;
        }
        $fineId = $fineInfo['id'];
        $blendingModel = new model_pinping_resumeblending();
        $connectInfo = $blendingModel->selectOne(['fine_id' => $fineId]);
        if (!$connectInfo) {
            return $data;
        }
        $connect_result = [];
        foreach ($connectInfo as $key => $value) {
            //老子段
            if (strstr($key, 'old_')) {
                $key1 = trim(str_replace('old_', '', $key));
                $resumeInfo[$key1] = $value;
            } else {
                $connect_result[$key] = $value;
            }

        }
        if ($connect_result['other_cont']) {
            $connect_result['other_cont'] = json_decode($connect_result['other_cont'], true);
        } else {
            $connect_result['other_cont'] = '';
        }
        $connect_result['optional_fields'] = json_decode($connect_result['optional_fields'], true);
        return ['resume' => $resumeInfo, 'connect_result' => $connect_result, 'ertrt' => $value];
    }
}