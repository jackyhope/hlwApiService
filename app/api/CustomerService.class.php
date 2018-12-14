<?php

use com\hlw\huilie\interfaces\CustomerServiceIf;
use com\hlw\huilie\dataobject\customer\AddCustomerRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_CustomerService extends api_Abstract implements CustomerServiceIf {

    public function addCustomer(AddCustomerRequestDTO $addCustomerDo) {
        $resultDo = new ResultDO();

        if (!$addCustomerDo->name) {
            $resultDo->code = 500;
            $resultDo->success = TRUE;
            $resultDo->message = "确实企业名称";
        }

        $customer_name = hlw_lib_BaseUtils::getStr($addCustomerDo->name);
        $customer_address = $addCustomerDo->address ? hlw_lib_BaseUtils::getStr($addCustomerDo->address) : '';
        $customer_phonetwo = $addCustomerDo->phonetwo ? hlw_lib_BaseUtils::getStr($addCustomerDo->phonetwo) : '';
        $customer_phoneone = $addCustomerDo->phoneone ? hlw_lib_BaseUtils::getStr($addCustomerDo->phoneone) : '';
        $customer_phonethree = $addCustomerDo->phonethree ? hlw_lib_BaseUtils::getStr($addCustomerDo->phonethree) : '';
        $customer_introduce = $addCustomerDo->content ? hlw_lib_BaseUtils::getStr($addCustomerDo->content) : '';
        $customer_sdate = $addCustomerDo->sdate ? hlw_lib_BaseUtils::getStr($addCustomerDo->sdate) : '';
        $customer_money = $addCustomerDo->money ? hlw_lib_BaseUtils::getStr($addCustomerDo->money) : '';
        $customer_zip = $addCustomerDo->zip ? hlw_lib_BaseUtils::getStr($addCustomerDo->zip) : '';
        $customer_website = $addCustomerDo->website ? hlw_lib_BaseUtils::getStr($addCustomerDo->website) : '';
        $customer_busstops = $addCustomerDo->busstops ? hlw_lib_BaseUtils::getStr($addCustomerDo->busstops) : '';
        $customer_contacts_linkman = $addCustomerDo->linkman ? hlw_lib_BaseUtils::getStr($addCustomerDo->linkman) : '';
        $customer_contacts_linkjob = $addCustomerDo->linkjob ? hlw_lib_BaseUtils::getStr($addCustomerDo->linkjob) : '';
        $customer_contacts_linkqq = $addCustomerDo->linkqq ? hlw_lib_BaseUtils::getStr($addCustomerDo->linkqq) : '';
        $customer_contacts_linkmail = $addCustomerDo->linkmail ? hlw_lib_BaseUtils::getStr($addCustomerDo->linkmail) : '';
        $customer_contacts_linktel = $addCustomerDo->linktel ? hlw_lib_BaseUtils::getStr($addCustomerDo->linktel) : '';
        
        $customer_tele = '';
        if ($customer_phoneone) {
            $customer_tele = $customer_phoneone . '-';
        }
        if ($customer_phonetwo) {
            $customer_tele .= $customer_phonetwo;
        }

        if ($customer_phonethree) {
            $customer_tele .= '-' . $customer_phonethree;
        }
        
        $model_customer = new model_pinping_customer();
        $model_customer_data = new model_pinping_customerdata();
        $model_contacts = new model_pinping_contacts();
        $model_rcontacts_customer = new model_pinping_rcontactscustomer();
        
        try {
            $model_customer->beginTransaction();
            $customer_ins = [
                'cooperation_code' => '',
                'name' => $customer_name,
                'hr_company_logo' => '',
                'short_name' => $customer_name,
                'customer_owner_name' => '',
                'customer_owner_en_name' => '',
                'create_time' => time(),
                'update_time' => 0,
                'is_deleted' => 0,
                'is_locked' => 0,
                'owner_role_id' => 0,
                'delete_role_id' => 0,
                'location' => $customer_address,
                'telephone' => $customer_tele,
                'introduce' => $customer_introduce
            ];
            hlw_lib_BaseUtils::addLog(json_encode($customer_ins));
            $model_customer->insert($customer_ins);
            $customer_id = $model_customer->lastInsertId();

            $customer_data_ins = [
                'customer_id' => $customer_id,
                'money' => $customer_money,
                'zip' => $customer_zip,
                'busstops' => $customer_busstops,
                'sdate' => $customer_sdate,
                'website' => $customer_website
            ];
            $model_customer_data->insert($customer_data_ins);

            $customer_auth_ins = [
                'name' => $customer_contacts_linkman,
                'telephone' => $customer_contacts_linktel,
                'email' => $customer_contacts_linkmail,
                'qq_no' => $customer_contacts_linkqq,
                'post' => $customer_contacts_linkjob
            ];
            $model_contacts->insert($customer_auth_ins);
            $contacts_id = $model_contacts->lastInsertId();

            $customer_contacts_r_ins = [
                'contacts_id' => $contacts_id,
                'customer_id' => $customer_id
            ];
            $model_rcontacts_customer->insert($customer_contacts_r_ins);

            $model_customer->commit();
            
            $resultDo->success = TRUE;
            $resultDo->code = 200;
            $resultDo->message = "注册成功";
            
            return $resultDo;
        } catch (Exception $ex) {
            $model_customer->rollBack();
        }
    }

}
