<?php

use com\hlw\huilie\interfaces\CustomerServiceIf;
use com\hlw\common\dataobject\common\ResultDO;

class api_CustomerService extends api_Abstract implements CustomerServiceIf {

    protected $customerOwner = 1;

    public function saveCustomer(\com\hlw\huilie\dataobject\customer\CustomerRequestDTO $CustomerDo) {
        $resultDo = new ResultDO();
        if (!$CustomerDo->name) {
            $resultDo->code = 500;
            $resultDo->success = TRUE;
            $resultDo->message = '缺少名称';
            return $resultDo;
        }

        if (!$CustomerDo->hy) {
            $resultDo->code = 500;
            $resultDo->success = TRUE;
            $resultDo->message = '缺少从事行业';
            return $resultDo;
        }
        
        if (!$CustomerDo->mun) {
            $resultDo->code = 500;
            $resultDo->success = TRUE;
            $resultDo->message = '缺少企业规模';
            return $resultDo;
        }
//
        $customer_name = hlw_lib_BaseUtils::getStr($CustomerDo->name);
        $customer_hy = hlw_lib_BaseUtils::getStr($CustomerDo->hy);
        $customer_mun = hlw_lib_BaseUtils::getStr($CustomerDo->mun);
        $customer_address = $CustomerDo->address ? hlw_lib_BaseUtils::getStr($CustomerDo->address) : '';
        $customer_phonetwo = $CustomerDo->phonetwo ? hlw_lib_BaseUtils::getStr($CustomerDo->phonetwo) : '';
        $customer_phoneone = $CustomerDo->phoneone ? hlw_lib_BaseUtils::getStr($CustomerDo->phoneone) : '';
        $customer_phonethree = $CustomerDo->phonethree ? hlw_lib_BaseUtils::getStr($CustomerDo->phonethree) : '';
        $customer_introduce = $CustomerDo->content ? hlw_lib_BaseUtils::getStr($CustomerDo->content) : '';
        $customer_sdate = $CustomerDo->sdate ? hlw_lib_BaseUtils::getStr($CustomerDo->sdate) : '';
        $customer_money = $CustomerDo->money ? hlw_lib_BaseUtils::getStr($CustomerDo->money) : '';
        $customer_zip = $CustomerDo->zip ? hlw_lib_BaseUtils::getStr($CustomerDo->zip) : '';
        $customer_website = $CustomerDo->website ? hlw_lib_BaseUtils::getStr($CustomerDo->website) : '';
        $customer_busstops = $CustomerDo->busstops ? hlw_lib_BaseUtils::getStr($CustomerDo->busstops) : '';
        $customer_contacts_linkman = $CustomerDo->linkman ? hlw_lib_BaseUtils::getStr($CustomerDo->linkman) : '';
        $customer_contacts_linkjob = $CustomerDo->linkjob ? hlw_lib_BaseUtils::getStr($CustomerDo->linkjob) : '';
        $customer_contacts_linkqq = $CustomerDo->linkqq ? hlw_lib_BaseUtils::getStr($CustomerDo->linkqq) : '';
        $customer_contacts_linkmail = $CustomerDo->linkmail ? hlw_lib_BaseUtils::getStr($CustomerDo->linkmail) : '';
        $customer_contacts_linktel = $CustomerDo->linktel ? hlw_lib_BaseUtils::getStr($CustomerDo->linktel) : '';
        $customer_customer_id = $CustomerDo->linktel ? hlw_lib_BaseUtils::getStr($CustomerDo->customer_id,'int') : '0';

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

            //查询重复
            $isExist = '';
            if($customer_customer_id > 0){
                $isExist = $model_customer->selectOne(['customer_id' => $customer_customer_id], 'customer_id');
            }
            !$isExist && $isExist = $model_customer->selectOne(['name' => $customer_name], 'customer_id');
            $customer_ins = [
                'cooperation_code' => '',
                'name' => $customer_name,
                'industry' => hlw_conf_constant::$huilie_to_oa_hy[$customer_hy],
                'hr_company_logo' => '',
                'short_name' => $customer_name,
                'customer_owner_name' => '',
                'customer_owner_en_name' => '',
                'update_time' => 0,
                'is_deleted' => 0,
                'is_locked' => 0,
                'delete_role_id' => 0,
                'location' => $customer_address,
                'telephone' => $customer_tele,
                'introduce' => $customer_introduce
            ];

            if (!$isExist['customer_id']) {
                $customer_ins['owner_role_id'] = $this->customerOwner;
                $customer_ins['create_time'] = time();
                $model_customer->insert($customer_ins);
                $customer_id = $model_customer->lastInsertId();
                $mode = 'add'; //add
            } else {
                $customer_id = $isExist['customer_id'];
                $customer_ins['update_time'] = time();
                $model_customer->update(['customer_id' => $customer_id], $customer_ins);
                $mode = 'update'; //update
            }

            if ($mode == 'add') {
                $customer_data_ins = [
                    'customer_id' => $customer_id,
                    'money' => $customer_money,
                    'zip' => $customer_zip,
                    'busstops' => $customer_busstops,
                    'sdate' => $customer_sdate,
                    'website' => $customer_website,
                    'scale' => hlw_conf_constant::$huilie_to_oa_mun[$customer_mun]
                ];
                $model_customer_data->insert($customer_data_ins);
            } elseif ($mode == 'update') {
                $customer_data_ins = [
                    'money' => $customer_money,
                    'zip' => $customer_zip,
                    'busstops' => $customer_busstops,
                    'sdate' => $customer_sdate,
                    'website' => $customer_website,
                    'scale' => hlw_conf_constant::$huilie_to_oa_mun[$customer_mun]
                ];
                $model_customer_data->update(['customer_id' => $customer_id], $customer_data_ins);
            }

            $isExistLinkMan = $model_contacts->selectOne(['mx_r_contacts_customer.`customer_id`' => $customer_id], 'mx_r_contacts_customer.contacts_id', '', '', ['mx_r_contacts_customer' => 'mx_r_contacts_customer.contacts_id=mx_contacts.contacts_id']);
            if ($isExistLinkMan['contacts_id']) {
                $customer_auth_upd = [
                    'name' => $customer_contacts_linkman,
                    'telephone' => $customer_contacts_linktel,
                    'email' => $customer_contacts_linkmail,
                    'qq_no' => $customer_contacts_linkqq,
                    'post' => $customer_contacts_linkjob
                ];
                $model_contacts->update(['contacts_id' => $isExistLinkMan['contacts_id']], $customer_auth_upd);
            } else {
                $customer_auth_ins = [
                    'name' => $customer_contacts_linkman,
                    'telephone' => $customer_contacts_linktel,
                    'email' => $customer_contacts_linkmail,
                    'qq_no' => $customer_contacts_linkqq,
                    'post' => $customer_contacts_linkjob
                ];
                $model_contacts->insert($customer_auth_ins);
                $contacts_id = $model_contacts->lastInsertId();

                //修改coustmer首要联系人
                $model_customer->update(['customer_id' => $customer_id], ['contacts_id' => $contacts_id]);

                $customer_contacts_r_ins = [
                    'contacts_id' => $contacts_id,
                    'customer_id' => $customer_id
                ];
                $model_rcontacts_customer->insert($customer_contacts_r_ins);
            }
            $model_customer->commit();

            $resultDo->success = TRUE;
            $resultDo->code = 200;
            $resultDo->message = $customer_id;

            return $resultDo;
        } catch (Exception $ex) {
            $model_customer->rollBack();
        }
    }

}
