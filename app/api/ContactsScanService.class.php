<?php
/**
 * @desc 用户信息浏览
 * Date: 2019/3/2
 */

use com\hlw\huiliewang\interfaces\ContactInfoServiceIf;
use com\hlw\huiliewang\dataobject\contactInfo\contactScanRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_ContactsScanService extends api_Abstract implements ContactInfoServiceIf
{
    /**
     * @desc  用户是否可以查看联系人信息
     * @param contactScanRequestDTO $contactInfoDo
     * @return ResultDO
     */
    public function isScan(ContactScanRequestDTO $contactInfoDo) {
        $resultDo = new ResultDO();
        if (!$contactInfoDo->itemId || !$contactInfoDo->userRoleId || $contactInfoDo->type <= 0) {
            $resultDo->success = true;
            $resultDo->code = 500;
            $resultDo->message = '缺少必传参数';
            return $resultDo;
        }
        $itemId = hlw_lib_BaseUtils::getStr($contactInfoDo->itemId, 'int');
        $userRoleId = hlw_lib_BaseUtils::getStr($contactInfoDo->userRoleId, 'int');
        $type = hlw_lib_BaseUtils::getStr($contactInfoDo->type, 'int');

        $contactScanModel = new model_pinping_contactsscan();
        $res = $contactScanModel->isScan($itemId, $userRoleId, $type);
        $resultDo->success = $res;
        $resultDo->code = 200;
        $resultDo->message = $contactScanModel->getError();
        return $resultDo;
    }

    /**
     * @desc 查看联系人信息
     * @param contactScanRequestDTO $contactInfoDo
     * @return ResultDO
     */
    public function scan(ContactScanRequestDTO $contactInfoDo) {
        $resultDo = new ResultDO();
        if (!$contactInfoDo->itemId || !$contactInfoDo->userRoleId || $contactInfoDo->type <= 0 ) {
            $resultDo->success = false;
            $resultDo->code = 300;
            $resultDo->message = '缺少必传参数';
            return $resultDo;
        }
        $itemId = hlw_lib_BaseUtils::getStr($contactInfoDo->itemId, 'int');
        $userRoleId = hlw_lib_BaseUtils::getStr($contactInfoDo->userRoleId, 'int');
        $type = hlw_lib_BaseUtils::getStr($contactInfoDo->type, 'int');
        $contactScanModel = new model_pinping_contactsscan();
        $res = $contactScanModel->scanInfo($itemId, $userRoleId, $type);
        if (!$res) {
            $resultDo->success = false;
            $resultDo->code = 500;
            $resultDo->message = $contactScanModel->getError();
            return $resultDo;
        }
        //返回信息
        $phone = '';
        $email = '';
        if ($type == 2) {
            //客户信息
            $contactCustomerModel = new model_pinping_rcontactscustomer();
            $contactModel = new model_pinping_contacts();
            $contactIdArr = $contactCustomerModel->selectOne(['customer_id' => $itemId], 'contacts_id');
            $contactsId = $contactIdArr['contacts_id'];
            $contactInfo = $contactModel->selectOne(['contacts_id' => $contactsId],'telephone,email');
            $phone = $contactInfo['telephone'];
            $email = $contactInfo['email'];
        }
        if ($type == 1) {
            $resumeModel = new model_pinping_resume();
            $contactInfo = $resumeModel->getInfo($itemId);
            $phone = $contactInfo['telephone'];
            $email = $contactInfo['email'];
        }
        $data = ['telephone' => $phone, 'email' => $email];
        $resultDo->success = $res;
        $resultDo->code = 200;
        $resultDo->message = json_encode($data);
        return $resultDo;
    }
}