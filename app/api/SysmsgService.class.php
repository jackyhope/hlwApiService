<?php
/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 慧猎网系统信息发送
 * User: SOSO
 * Date: 2019/7/12
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */

use com\hlw\huiliewang\interfaces\sysmsg\SysmsgServiceIf;
use com\hlw\huiliewang\dataobject\sysmsg\sysmsgRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_SysmsgService extends api_Abstract implements SysmsgServiceIf
{
    protected $resultDo;
    protected $company_id;
    protected $user_id;
    protected $user_type;
    protected $messageType;
    protected $email;
    protected $phone;
    protected $content;
    protected $subject;
    protected $sysmsgDo;
    protected $userName;
    protected $templateId;
    protected $from; //1：pc 0:慧猎

    public function __construct() {
        $this->resultDo = new ResultDO();
    }

    /**
     * @desc  发送原手机短信
     * @param sysmsgRequestDTO $sysmsgDO
     * @return ResultDO
     */
    public function sendMSG(sysmsgRequestDTO $sysmsgDo) {
        // TODO: Implement sendMSG() method.
        $this->sysmsgDo = $sysmsgDo;
        $this->phone = hlw_lib_BaseUtils::getStr($sysmsgDo->phone); //电话
        $this->content = hlw_lib_BaseUtils::getStr($sysmsgDo->content); //发送内容
        $this->user_id = hlw_lib_BaseUtils::getStr($sysmsgDo->uid); //发送内容
        $this->userName = hlw_lib_BaseUtils::getStr($sysmsgDo->name); //发送内容
        $this->resultDo->success = false;
        $this->resultDo->code = 500;
        if (!$this->phone || !$this->content || !$this->user_id || !$this->userName) {
            $this->resultDo->message = 'phone、content、uid、name缺失';
            return $this->resultDo;
        }
        var_dump($this->content);
        die;
        $this->content = json_decode($this->content, true);
        if (!isset($this->content) || !isset($this->content[0])) {
            $this->resultDo->message = '短信json内容错误';
            return $this->resultDo;
        }
        //单条短信发送
        try {
            $smsObj = new STxSms();
            $smsRes = $smsObj->sentOne($this->phone, $this->content);
            if (!$smsRes) {
                $this->resultDo->message = $smsObj->getError();
                return $this->resultDo;
            }
        } catch (\Exception $e) {
            $this->resultDo->message = $e->getMessage();
            return $this->resultDo;
        }
        //记录短信记录
        $data = [
            'uid' => $this->user_id,
            'name' => $this->userName,
            'cname' => '系统',
            'mobile' => $this->phone,
            'content' => $this->content,
            'ctime' => time(),
            'state' => 1
        ];
        $mobileModel = new model_huiliewang_mobilemsg();
        $mobileModel->insert($data);
        $id = $mobileModel->lastInsertId();
        $id && $this->resultDo->success = true;
        $id && $this->resultDo->code = 200;
        $this->resultDo->message = $id ? '发送成功' : '发送失败';
        return $this->resultDo;
    }

    /**
     * @desc  发送短信
     * @param sysmsgRequestDTO $sysmsgDo
     * @return ResultDO
     */
    public function sendSms(sysmsgRequestDTO $sysmsgDo) {
        $this->sysmsgDo = $sysmsgDo;
        $this->phone = hlw_lib_BaseUtils::getStr($sysmsgDo->phone); //电话
        $this->content = hlw_lib_BaseUtils::getStr($sysmsgDo->content); //发送内容
        $this->user_id = hlw_lib_BaseUtils::getStr($sysmsgDo->uid); //发送内容
        $this->userName = hlw_lib_BaseUtils::getStr($sysmsgDo->name); //发送内容
        $this->templateId = hlw_lib_BaseUtils::getStr($sysmsgDo->templateId); //短信模板ID
        $this->from = hlw_lib_BaseUtils::getStr($sysmsgDo->fromId); //1：pc 0：慧猎
        if ($this->from && $this->from == 1) {
            //OA的ID转换成慧猎的ID
            $companyModel = new model_huiliewang_company();
            $member = new model_huiliewang_member();
            $companyInfo = $companyModel->selectOne(['tb_customer_id' => $this->user_id], 'uid,name,linkman,linktel');
            $memberIfo = $member->selectOne(['tb_customer_id' => $this->user_id],'moblie');
            if ($companyInfo && $companyInfo['uid']) {
                $this->user_id = $companyInfo['uid'];
                $this->userName = $companyInfo['linkman'] ? $companyInfo['linkman'] : $companyInfo['name'];
                $this->phone = $companyInfo['linktel'] ? $companyInfo['linktel'] : $memberIfo['moblie'];
            }else{
                $this->resultDo->message = '客户信息不存在';
                return $this->resultDo;
            }
        }
        $this->resultDo->success = false;
        $this->resultDo->code = 500;
        if (!$this->phone || !$this->content || !$this->user_id || !$this->userName) {
            $this->resultDo->message = 'phone、content、uid、name缺失';
            return $this->resultDo;
        }
        if (!isset($this->content) || !isset($this->content[0])) {
            $this->resultDo->message = '短信数组内容错误';
            return $this->resultDo;
        }
        $mobileModel = new model_huiliewang_mobilemsg();
        $lastSend = $mobileModel->selectOne(['moblie' => $this->phone, 'template_id' => $this->templateId],'*','','order by id desc');
        if ($lastSend && time() - $lastSend['ctime'] < 60) {
            $id = $lastSend['id'];
            $id && $this->resultDo->success = true;
            $id && $this->resultDo->code = 200;
            $this->resultDo->message = $id ? '发送成功' : '发送失败';
            return $this->resultDo;
        }
        //短信发送
        try {
            $config = [];
            $this->templateId && $config['templateId'] = $this->templateId;
            $smsObj = new STxSms($config);
            $smsRes = $smsObj->sentTemOne($this->phone, $this->content);
            if (!$smsRes) {
                $this->resultDo->message = $smsObj->getError();
                return $this->resultDo;
            }
        } catch (\Exception $e) {
            $this->resultDo->message = $e->getMessage();
            return $this->resultDo;
        }
        $data = [
            'uid' => $this->user_id,
            'name' => $this->userName,
            'cname' => '系统',
            'moblie' => $this->phone,
            'content' => json_encode($this->content),
            'ctime' => time(),
            'template_id' => $this->templateId ? $this->templateId : 0,
            'state' => 1
        ];

        $mobileModel->insert($data);
        $id = $mobileModel->lastInsertId();
        $id && $this->resultDo->success = true;
        $id && $this->resultDo->code = 200;
        $this->resultDo->message = $smsObj->getError();
//                $id ? '发送成功' : '发送失败';
        return $this->resultDo;
    }

    /**
     * @desc 发送邮件
     * @param sysmsgRequestDTO $sysmsgDo
     * @return ResultDO
     */
    public function sendEmail(sysmsgRequestDTO $sysmsgDo) {
        $this->sysmsgDo = $sysmsgDo;
        $this->email = hlw_lib_BaseUtils::getStr($sysmsgDo->email); //邮箱
        $this->content = hlw_lib_BaseUtils::getStr($sysmsgDo->content); //发送内容
        $this->subject = hlw_lib_BaseUtils::getStr($sysmsgDo->subject); //发送内容
        if (!$this->email || !$this->content || !$this->subject) {
            $this->resultDo->success = true;
            $this->resultDo->code = 500;
            $this->resultDo->message = '请至少传递一个参数';
            return $this->resultDo;
        }

        $this->resultDo->code = 200;
        $this->resultDo->success = true;
        $this->resultDo->message = '发送成功';
        try {
            $mailer = new SEmail();
            $mailer->sentMail($this->email, $this->subject, $this->content);
        } catch (\Exception $e) {
            $this->resultDo->code = 500;
            $this->resultDo->success = false;
            $this->resultDo->message = $e->getMessage();
        }
        return $this->resultDo;
    }

    /**
     * @desc 发送系统消息
     * @param sysmsgRequestDTO $sysmsgDo
     * @return ResultDO
     */
    public function sendSystemMess(sysmsgRequestDTO $sysmsgDo) {
        $this->sysmsgDo = $sysmsgDo;
        $this->company_id = hlw_lib_BaseUtils::getStr($sysmsgDo->company_id, 'int'); //公司ID
        $this->user_id = hlw_lib_BaseUtils::getStr($sysmsgDo->uid, 'int'); //用户ID
        $this->user_type = hlw_lib_BaseUtils::getStr($sysmsgDo->user_type, 'int'); //用户类型
        $this->content = hlw_lib_BaseUtils::getStr($sysmsgDo->content); //内容
        $this->userName = hlw_lib_BaseUtils::getStr($sysmsgDo->name); //内容
        if (!$this->company_id && !$this->user_id && !$this->user_type) {
            $this->resultDo->success = false;
            $this->resultDo->code = 500;
            $this->resultDo->message = '请至少传递一个参数';
            return $this->resultDo;
        }
        $this->from = hlw_lib_BaseUtils::getStr($sysmsgDo->fromId); //发送内容
        if ($this->from && $this->from == 1) {
            //OA的ID转换成慧猎的ID
            $companyModel = new model_huiliewang_company();
            $companyInfo = $companyModel->selectOne(['tb_customer_id' => $this->user_id], 'uid,linktel,linkman');
            if ($companyInfo && $companyInfo['uid']) {
                $this->user_id = $companyInfo['uid'];
                $this->userName = $companyInfo['linkman'] ? $companyInfo['linkman'] : $this->userName;
            }
        }
        //重复判断
        $msgModel = new model_huiliewang_sysmsg();
        $lastSend = $msgModel->selectOne(['fa_uid' => $this->user_id, 'username' => $this->userName],'*','','order by id desc');
        if ($lastSend && time() - $lastSend['ctime'] < 60) {
            $id = $lastSend['id'];
            $id && $this->resultDo->success = true;
            $id && $this->resultDo->code = 200;
            $this->resultDo->message = $id ? '发送成功' : '发送失败';
            return $this->resultDo;
        }
        $this->resultDo->success = true;
        $this->resultDo->code = 200;
        $this->resultDo->message = '发送成功';
        //发送
        try {
            $content = isset($this->content[0]) ? $this->content[0] : '';
            $data = ['content' => $content, 'fa_uid' => $this->user_id, 'username' => $this->userName, 'ctime' => time()];
            $msgModel->sent($data);
        } catch (\Exception $e) {
            $this->resultDo->success = false;
            $this->resultDo->code = 500;
            $this->resultDo->message = $e->getMessage();
        }
        return $this->resultDo;
    }

    /**
     * @desc 返回发布的职位
     * @param $jobid
     */
    public function getCompanyJob($jobid)
    {
        // TODO: Implement getCompanyJob() method.
        $companyJob = new model_huiliewang_companyjob();
        $name = $companyJob->selectOne(['id'=>$jobid],'name');
        return $name;
    }

}