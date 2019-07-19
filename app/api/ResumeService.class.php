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
        $resultDo->success = true;
        $resultDo->code = 500;
        if ($resumeId <= 0 || $projectId <= 0) {
            $resultDo->message = '缺少必传参数: resume_id/projectId';
            return $resultDo;
        }
        $projectInfo = $this->fineProjectInfo($resumeId, $projectId);
        $huilieStatus = $projectInfo['huilie_status'];
        $isShowContacts = false;
        if ($huilieStatus == 4 || $huilieStatus == 11) {
            $isShowContacts = true;
        }
        $list = $this->getResume($resumeId, $isShowContacts);
        if (!$list) {
            $resultDo->message = $this->errMsg;
            return $resultDo;
        }
        //隐藏联系信息
        $this->statusChange($resumeId, $projectId, 2);
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
            $res = $this->statusChange($resumeId, $projectId, $status);
            if ($res !== false) {
                $status == 3 && $this->resumeReject($fineInfo); //简历不合适
                $status == 4 && $this->resumeBuy($fineInfo); //购买记录记录
                $status == 11 && $this->present($fineInfo, 1); //到场记录
                $status == 10 && $this->present($fineInfo, 0); //到场记录
                $resultDo->success = true;
                $resultDo->code = 200;
                $resultDo->message = '操作成功';
                return $resultDo;
            }
        } catch (Exception $e) {
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
        $resultDo = new ResultDO();
        $resultDo->success = true;
        $resultDo->code = 500;
        if (!$resumeId || !$projectId) {
            $resultDo->message = '缺少必传参数: resume_id/projectId/status';
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
        $tjList = ['role_id' => $roleId, 'user_name' => $userName, 'add_time' => date('Y-m-d H:i', $fineInfo['tjaddtime'])];
        $list[1] = $tjList;
        //2、HR确认简历 [面试/不合适]
        $bhsModel = new model_pinping_fineprojectbhs();
        $bhsInfo = $bhsModel->selectOne($where, '*', '', 'order by id desc');
        if ($bhsInfo) {
            $bhsInfo['role_id'] = $roleId;
            $bhsInfo['add_time'] = date('Y-m-d H:i', $bhsInfo['addtime']);
            $list[3] = $bhsInfo;
        }
        //3、HR确认面试
        $interview = new model_pinping_fineprojectinterview();
        $interviewWhere = $where;
        $interviewWhere['is_from_hr'] = 1;
        $interviewInfo = $interview->selectOne($interviewWhere, '*', '', 'order by id desc');
        if ($interviewInfo) {
            $interviewInfo['role_id'] = $roleId;
            $interviewInfo['add_time'] = date('Y-m-d H:i', $interviewInfo['addtime']);
            $list[5] = $interviewInfo;
        }
        //4、顾问确认面试
        $interviewWhere['is_from_hr'] = 0;
        $interviewGwInfo = $interview->selectOne($interviewWhere, '*', '', 'order by id desc');
        if ($interviewGwInfo) {
            $interviewGwInfo['role_id'] = $roleId;
            $interviewGwInfo['user_name'] = $userName;
            $interviewGwInfo['add_time'] = date('Y-m-d H:i', $interviewGwInfo['addtime']);
            $list[6] = $interviewGwInfo;
        }
        //5、购买
        $payModel = new model_pinping_fineresumebuy();
        $buyInfo = $payModel->selectOne($where, '*', '', 'order by id desc');
        if ($buyInfo) {
            $buyInfo['role_id'] = $roleId;
            $buyInfo['user_name'] = $userName;
            $buyInfo['add_time'] = date('Y-m-d H:i', $buyInfo['add_time']);
            $list[4] = $buyInfo;
        }
        //6、到场扣币
        $present = new model_pinping_fineprojectpresent();
        $presentInfo = $present->selectOne($where, '*', '', 'order by id desc');
        if ($presentInfo) {
            $presentInfo['role_id'] = $roleId;
            $presentInfo['user_name'] = $userName;
            $presentInfo['add_time'] = date('Y-m-d H:i', $presentInfo['add_time']);
            $list[10] = $presentInfo;
        }
        $resultDo->code = 200;
        $resultDo->message = json_encode($list);
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
            $interviewModel->beginTransaction();
            $interviewModel->insert($data);
            $this->statusChange($resumeId, $projectId, 5);
            $this->sentMess($fineId, 'interview', $this->interviewSmsId);
            $interviewModel->commit();
            $resultDo->code = 200;
            $resultDo->message = '提交成功';
            return $resultDo;
        } catch (Exception $e) {
            $interviewModel->rollBack();
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
        $projectInfo = $this->fineProjectInfo($resumeId, $projectId);
        $fineId = $projectInfo['id'];
        if (!$projectInfo) {
            $resultDo->message = '获取失败';
            return $resultDo;
        }
        if ($projectInfo['huilie_status'] != 4) {
            $resultDo->message = '当前状态不能下载简历';
            return $resultDo;
        }
        $resumeInfo = $this->getResume($resumeId, true);
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
        if (($vale == 10 || $vale == 11) && $hlStatus < 8) {
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
    private function fineProjectInfo($resumeId, $projectId, $filed = 'id,tj_role_id,tracker,huilie_status,status,resume_id,com_id,project_id') {
        if (!$resumeId || !$projectId) {
            return false;
        }
        $where = ['resume_id' => $resumeId, 'project_id' => $projectId,];
        $info = $this->fineProject->selectOne($where, $filed, '', 'order by id desc');
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
     * @desc  简历购买操作记录
     * @param $fineInfo
     * @param int $coin
     * @return int
     */
    private function resumeBuy($fineInfo, $coin = 3) {
        $fineId = $fineInfo['id'];
        $roleId = $fineInfo['tj_role_id'] ? $fineInfo['tj_role_id'] : $fineInfo['tracker'];
        $resumeId = $fineInfo['resume_id'];
        $resumeBug = new model_pinping_fineresumebuy();
        $resumeInfo = $this->resumeModel->selectOne(['eid' => $resumeId], 'name,wantsalary,curSalary');
        $info = $resumeBug->selectOne(['fine_id' => $fineId], 'id');
        if ($info) {
            return true;
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
        ];
        return $resumeBug->insert($data);
    }

    /**
     * @desc 获取简历信息
     * @param $resumeId
     * @param bool $isBuy
     * @return array|bool
     */
    private function getResume($resumeId, $isBuy = false) {
        $resumeInfo = $this->resumeModel->selectOne(['eid' => $resumeId]);
        if (!$resumeInfo) {
            $this->errMsg = '简历获取失败';
            return false;
        }
        //隐藏联系信息
        if (!$isBuy) {
            $resumeInfo['telephone'] = '*';
            $resumeInfo['email'] = '*';
            $resumeInfo['wechat_number'] = '*';
            $resumeInfo['wechat_qr'] = '*';
            $resumeInfo['qq_number'] = '*';
        }

        $where = ['eid' => $resumeId];
        //项目经验
        $projectModel = new model_pinping_resumeproject();
        $projectList = $projectModel->select($where, '*', '', 'order by id desc');
        //工作经验
        $workModel = new model_pinping_resumework();
        $workList = $workModel->select($where, '*', '', 'order by id desc');
        //教育经验
        $eduModel = new model_pinping_resumeedu();
        $eduList = $eduModel->select($where, '*', '', 'order by id desc');
        return [
            'info' => $resumeInfo,
            'project' => $projectList ? $projectList : [],
            'work' => $workList ? $workList : [],
            'edu' => $eduList ? $eduList : []
        ];
    }

    /**
     * @desc  到场确认
     * @param $fineInfo
     * @param int $isPresent
     * @param int $coin
     * @return bool
     */
    private function present($fineInfo, $isPresent = 1, $coin = 3) {
        $fineId = $fineInfo['id'];
        $roleId = $fineInfo['tj_role_id'] ? $fineInfo['tj_role_id'] : $fineInfo['tracker'];
        $resumeId = $fineInfo['resume_id'];
        $present = new model_pinping_fineprojectpresent();
        $resumeInfo = $this->resumeModel->selectOne(['eid' => $resumeId], 'name,wantsalary,curSalary');
        $info = $present->selectOne(['fine_id' => $fineId], 'id');
        if ($info) {
            return true;
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
            $smsContent = "";
        }
        //发送系统消息
        $messageData = [
            'to_role_id' => $roleId,
            'send_time' => time(),
            'from_role_id' => 0,
            'degree' => 1,
            'content' => "慧猎客户《{$customerName}》，选择了预约面试，请跟进"
        ];
        $messageMode->insert($messageData);
        //短信发送
        $config = [];
        $tempId && $config['templateId'] = $tempId;
        $smsObj = new STxSms($config);
        $smsRes = $smsObj->sentTemOne($phone, $smsContent);
        if (!$smsRes) {
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
}
