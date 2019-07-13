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

    public function __construct() {
        $this->resultDo = new ResultDO();
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
        if (!$this->phone || !$this->content) {
            $this->resultDo->success = true;
            $this->resultDo->code = 500;
            $this->resultDo->message = '请至少传递一个参数';
            return $this->resultDo;
        }
        //@todo 短信发送

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
        $this->user_id = hlw_lib_BaseUtils::getStr($sysmsgDo->user_id, 'int'); //用户ID
        $this->user_type = hlw_lib_BaseUtils::getStr($sysmsgDo->user_type, 'int'); //用户类型
        $this->content = hlw_lib_BaseUtils::getStr($sysmsgDo->content); //内容
        if (!$this->company_id && !$this->user_id && !$this->user_type) {
            $this->resultDo->success = false;
            $this->resultDo->code = 500;
            $this->resultDo->message = '请至少传递一个参数';
            return $this->resultDo;
        }
        $this->resultDo->success = true;
        $this->resultDo->code = 200;
        $this->resultDo->message = '发送成功';
        try {
            $msgModel = new model_huiliewang_sysmsg();
            $data = ['content' => $this->content, 'fa_uid' => $this->user_id, 'username' => 'sdsdsds', 'ctime' => time()];
            $msgModel->sent($data);
        } catch (\Exception $e) {
            $this->resultDo->success = false;
            $this->resultDo->code = 500;
            $this->resultDo->message = $e->getMessage();
        }
        return $this->resultDo;
    }

}