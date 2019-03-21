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
        $subRoleId = hlw_lib_BaseUtils::getStr($contactInfoDo->subRoleId);

        //联系人查看权限【自己创建得或者下级创建的可以查看】
        if($type == 2){
            $contactModel = new model_pinping_contacts();
            $contactInfo = $contactModel->selectOne(['contacts_id' => $itemId], '*');
            $creatorRoleId = isset($contactInfo['creator_role_id']) ? $contactInfo['creator_role_id'] : 0;
            if ($subRoleId) {
                $subRoleId = explode(',', $subRoleId);
            }
            if ($creatorRoleId != $userRoleId) {
                if(!$subRoleId || !in_array($creatorRoleId, $subRoleId)){
                    $resultDo->success = true;
                    $resultDo->code = 500;
                    $resultDo->message = '您无权查看该联系人';
                    return $resultDo;
                }
            }
        }

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
        if (!$contactInfoDo->itemId || !$contactInfoDo->userRoleId || $contactInfoDo->type <= 0) {
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
        $qq = '';
        $wechat = '';
        if ($type == 2) {
            //客户联系人信息
            $contactModel = new model_pinping_contacts();
            $contactInfo = $contactModel->selectOne(['contacts_id' => $itemId], '*');
            $phone = isset($contactInfo['telephone']) ? $contactInfo['telephone'] : '';
            $email = isset($contactInfo['email']) ? $contactInfo['email'] : '';
            $qq = isset($contactInfo['qq_no']) ? $contactInfo['qq_no'] : '';
            $wechat = isset($contactInfo['wetchat']) ? $contactInfo['wetchat'] : '';
        }
        if ($type == 1) {
            $resumeModel = new model_pinping_resume();
            $contactInfo = $resumeModel->getInfo($itemId);
            $phone = isset($contactInfo['telephone']) ? $contactInfo['telephone'] : '';
            $email = isset($contactInfo['email']) ? $contactInfo['email'] : '';
            $qq = isset($contactInfo['qq_number']) ? $contactInfo['qq_number'] : '';
            $wechat = isset($contactInfo['wechat_number']) ? $contactInfo['wechat_number'] : '';
        }
        $data = ['telephone' => $phone, 'email' => $email, 'qq' => $qq, 'wetchat' => $wechat];
        $resultDo->success = $res;
        $resultDo->code = 200;
        $resultDo->message = json_encode($data);
        return $resultDo;
    }
}