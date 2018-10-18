<?php
/**
 * 安管培公司相关接口
 * @author yanghao <yh38615890@sina.cn>
 * @date 07-05-29
 * @copyright (c) gandianli
 */
use com\hlw\ks\interfaces\CompanyServiceIf;
use com\hlw\ks\dataobject\company\CompanyInfoResultDTO;

class api_CompanyService extends api_Abstract implements CompanyServiceIf
{
    public function getCompanyInfoById($companyId, $userId, $relation = false,$identity_id = null)
    {
        $companyId = hlw_lib_BaseUtils::getStr($companyId, 'int');
        $userId = hlw_lib_BaseUtils::getStr($userId, 'int');
        $identity_id = hlw_lib_BaseUtils::getStr($identity_id, 'int');
        $result = new CompanyInfoResultDTO();
        try {
            //判断公司是否和此用户绑定
            if ($relation) {
                $modelUserCompany = new model_newexam_usercompany();
                if (is_null($identity_id)){
                    $conditionUserCompany = array(
                        'user_id' => $userId,
                        'company_id' => $companyId
                    );
                }else{
                    $conditionUserCompany = array(
                        'id' => $identity_id,
                        'company_id' => $companyId
                    );
                }

                $itemUserCompany = 'id';
                $resUserCompany = $modelUserCompany->selectOne($conditionUserCompany, $itemUserCompany);
                if (!$resUserCompany['id']) {
                    $result->success = TRUE;
                    $result->code = 0;
                    $result->message = '该用户和该查询公司未绑定';
                    return $result;
                }
            }

            $modelCompany = new model_newexam_company();
            $conditionCompany = array('id' => $companyId);
            $itemCompany = 'pid,name,remark,project_id,admin_id,intendant';
            $company = $modelCompany->selectOne($conditionCompany, $itemCompany);
            $result->data = $company;
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
}
