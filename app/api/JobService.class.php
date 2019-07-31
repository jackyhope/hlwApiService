<?php

use com\hlw\huilie\interfaces\JobServiceIf;
use com\hlw\huilie\dataobject\job\JobRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_JobService extends api_Abstract implements JobServiceIf
{

    protected $oaUser = 1;
    protected $proTypeMap = [
        0 => '4',
        1 => '8'
    ];

    public function __construct() {
        $this->oaUser = OA_ROLE;
    }

    public function saveJob(JobRequestDTO $saveJobDo) {
        $resultDo = new ResultDO();

        if (!$saveJobDo->mode) {
            $resultDo->success = TRUE;
            $resultDo->code = 500;
            $resultDo->message = '缺少变更模式';
            return $resultDo;
        }

        if (!$saveJobDo->job_id) {
            $resultDo->success = TRUE;
            $resultDo->code = 500;
            $resultDo->message = '缺少job_id';
            return $resultDo;
        }

        if (!$saveJobDo->name) {
            $resultDo->success = TRUE;
            $resultDo->code = 500;
            $resultDo->message = '缺少职位名';
            return $resultDo;
        }

        if (!$saveJobDo->edate) {
            $resultDo->success = TRUE;
            $resultDo->code = 500;
            $resultDo->message = '缺少职位招聘结束时间';
            return $resultDo;
        }

        if (!$saveJobDo->minsalary) {
            $resultDo->success = TRUE;
            $resultDo->code = 500;
            $resultDo->message = '缺少职位最小薪资';
            return $resultDo;
        }

        if (!$saveJobDo->maxsalary) {
            $resultDo->success = TRUE;
            $resultDo->code = 500;
            $resultDo->message = '缺少职位最大薪资';
            return $resultDo;
        }

        if (!$saveJobDo->ejob_salary_month) {
            $resultDo->success = TRUE;
            $resultDo->code = 500;
            $resultDo->message = '缺少发放月数';
            return $resultDo;
        }

        if (!$saveJobDo->description) {
            $resultDo->success = TRUE;
            $resultDo->code = 500;
            $resultDo->message = '缺少岗位简介';
            return $resultDo;
        }

        if (!$saveJobDo->com_name) {
            $resultDo->success = TRUE;
            $resultDo->code = 500;
            $resultDo->message = '缺少公司名称';
            return $resultDo;
        }

        $jobClass = hlw_lib_BaseUtils::getStr($saveJobDo->job_class);
        //职能
        $jobclassId = '';
        if ($jobClass) {
            $jobClassModel = new model_pinping_jobclass();
            $jobs = explode(',', $jobClass);
            $listName = $jobClassModel->select();
            $listName = $listName->items;
            $jobNames = [];
            foreach ($listName as $info) {
                $jobNames[$info['job_id']] = $info['name'];
            }
            $oaJobClass = '';
            foreach ($jobs as $jobName) {
                foreach ($jobNames as $jobclassId => $jobInfo) {
                    similar_text($jobName, $jobInfo, $percent);
                    if ($percent > 60) {
                        $oaJobClass .= $jobclassId . ',';
                        break;
                    }
                }
            }
            $jobclassId = trim($oaJobClass,',');
        }
        try {
            $business_name = hlw_lib_BaseUtils::getStr($saveJobDo->name, 'string');
            $business_edate = hlw_lib_BaseUtils::getStr($saveJobDo->edate, 'string');
            $business_minsalary = hlw_lib_BaseUtils::getStr($saveJobDo->minsalary, 'int');
            $business_maxsalary = hlw_lib_BaseUtils::getStr($saveJobDo->maxsalary, 'int');
            $business_ejob_salary_month = hlw_lib_BaseUtils::getStr($saveJobDo->ejob_salary_month, 'int');
            $business_description = hlw_lib_BaseUtils::getStr($saveJobDo->description);
            $customer_name = hlw_lib_BaseUtils::getStr($saveJobDo->com_name);
            $business_mode = hlw_lib_BaseUtils::getStr($saveJobDo->mode);
            $business_job_id = hlw_lib_BaseUtils::getStr($saveJobDo->job_id);
            $business_detail_report = $saveJobDo->detail_report ? hlw_lib_BaseUtils::getStr($saveJobDo->detail_report) : ''; //汇报对象
            $business_detail_subordinate = $saveJobDo->detail_subordinate ? hlw_lib_BaseUtils::getStr($saveJobDo->detail_subordinate) : ''; //下属人数
            $business_number = $saveJobDo->number ? hlw_lib_BaseUtils::getStr($saveJobDo->number) : ''; //招聘人数
            $business_report = $saveJobDo->report ? hlw_lib_BaseUtils::getStr($saveJobDo->report) : ''; //到岗时间
            $business_exp = $saveJobDo->exp ? hlw_lib_BaseUtils::getStr($saveJobDo->exp) : '';
            $business_language = $saveJobDo->language ? hlw_lib_BaseUtils::getStr($saveJobDo->language) : '';
            $business_edu = $saveJobDo->edu ? hlw_lib_BaseUtils::getStr($saveJobDo->edu) : '';
            $business_age = $saveJobDo->age ? hlw_lib_BaseUtils::getStr($saveJobDo->age) : '';
            $business_sex = $saveJobDo->sex ? hlw_lib_BaseUtils::getStr($saveJobDo->sex) : '';
            $business_hy = $saveJobDo->hy ? hlw_lib_BaseUtils::getStr($saveJobDo->hy) : '';
            $business_sdate = $saveJobDo->sdate ? hlw_lib_BaseUtils::getStr($saveJobDo->sdate) : '';
            $service_type = $saveJobDo->service_type ? hlw_lib_BaseUtils::getStr($saveJobDo->service_type) : '0';
            $customerId = $saveJobDo->customer_id ? hlw_lib_BaseUtils::getStr($saveJobDo->customer_id) : '0';
            $location = $saveJobDo->location ? hlw_lib_BaseUtils::getStr($saveJobDo->location) : '0';
            $proType = isset($this->proTypeMap[$service_type]) ? $this->proTypeMap[$service_type] : 4;


            $exp_arr = hlw_conf_constant::$huilie_to_oa_exp[$business_exp];
            $edu = hlw_conf_constant::$huilie_to_oa_edu[$business_edu];
            $report = hlw_conf_constant::$huilie_to_oa_report[$business_report];
            $age = hlw_conf_constant::$huilie_to_oa_age[$business_age];
            $sex = hlw_conf_constant::$huilie_to_oa_sex[$business_sex];
            $hy = hlw_conf_constant::$huilie_to_oa_hy[$business_hy];
            $address = hlw_conf_constant::$huilie_to_oa_city[$location];

            $model_business = new model_pinping_business();
            $model_business_data = new model_pinping_businessdata();
            $model_customer = new model_pinping_customer();
            $model_rbusinesscontacts = new model_pinping_rbusinesscontacts();

            //查询公司相关信息
            $customer_info = $model_customer->selectOne(['customer_id' => $customerId], 'customer_id,contacts_id');
            !$customer_info && $customer_info = $model_customer->selectOne(['name' => $customer_name], 'customer_id,contacts_id');
            //判断是否已经同步
            $businessInfo = $model_business->selectOne(['huilie_job_id' => $business_job_id, 'is_deleted' => 0], 'business_id,huilie_job_id');
            if ($businessInfo) {
                $business_mode = 'update';
            } else {
                $business_mode = 'add';
            }

            $model_business->beginTransaction();
            if ($business_mode == 'add') {
                $business_ins = [
                    'name' => $business_name,
                    'minsalary' => ($business_minsalary * $business_ejob_salary_month) / 10000,
                    'maxsalary' => ($business_maxsalary * $business_ejob_salary_month) / 10000,
                    'requirement' => $business_description,
                    'huilie_job_id' => $business_job_id,
                    'minexp' => $exp_arr[0] ? $exp_arr[0] : 0,
                    'maxexp' => $exp_arr[1] ? $exp_arr[1] : 40,
                    'minage' => $age[0] ? $age[0] : 0,
                    'maxage' => $age[1] ? $age[1] : 65,
                    'education' => $edu ? $edu : "大专",
                    'industry' => $hy ? $hy : '',
                    'startdate' => $business_sdate,
                    'prefixion' => 'M_',
                    'customer_id' => $customer_info['customer_id'],
                    'creator_role_id' => $this->oaUser,
                    'owner_role_id' => $this->oaUser,
                    'total_amount' => 0,
                    'total_subtotal_val' => 0,
                    'final_discount_rate' => 0,
                    'final_price' => 0,
                    'create_time' => time(),
                    'update_time' => time(),
                    'status_id' => 1,
                    'nextstep_time' => 0,
                    'is_deleted' => 0,
                    'delete_role_id' => 0,
                    'delete_time' => 0,
                    'contacts_id' => $customer_info['contacts_id'],
                    'possibility' => '80%',
                    'status_type_id' => 1,
                    'grade' => '5',
                    'isshare' => '',
                    'address' => $address,
                    'pro_type' => $proType,
                    'jobclass' => $jobclassId,
                ];
                $model_business->insert($business_ins);
                $business_id = $model_business->lastInsertId();
                $model_business->update(['business_id' => $business_id], ['code' => date('Ymd') . '-00' . $business_id]);

                $business_data_ins = [
                    'business_id' => $business_id,
                    'description' => '汇报对象:' . $business_detail_report . ';下属人数:' . $business_detail_subordinate . ';招聘人数:' . $business_number . ';到岗时间:' . $report,
                    'enddate' => $business_edate,
                    'language' => $business_language,
                    'sex_require' => $sex
                ];
                $model_business_data->insert($business_data_ins);

                //添加business_contacts关系
                $businesscontacts_ins = [
                    'business_id' => $business_id,
                    'contacts_id' => $customer_info['contacts_id']
                ];
                $model_rbusinesscontacts->insert($businesscontacts_ins);
            } elseif ($business_mode == 'update') {
                $business_upd = [
                    'name' => $business_name,
                    'minsalary' => ($business_minsalary * $business_ejob_salary_month) / 10000,
                    'maxsalary' => ($business_maxsalary * $business_ejob_salary_month) / 10000,
                    'requirement' => $business_description,
                    'huilie_job_id' => $business_job_id,
                    'minexp' => $exp_arr[0] ?  $exp_arr[0] : 0,
                    'maxexp' => $exp_arr[1] ? $exp_arr[1] : 40,
                    'minage' => $age[0] ? $age[0] : 0,
                    'maxage' => $age[1] ? $age[1] : 65,
                    'education' => $edu ? $edu : "大专",
                    'industry' => $hy ? $hy : '',
                    'startdate' => $business_sdate,
                    'prefixion' => 'M_',
                    'customer_id' => $customer_info['customer_id'],
                    'update_time' => time(),
                    'contacts_id' => $customer_info['contacts_id'],
                    'possibility' => '80%',
                    'pro_type' => $proType,
                    'address' => $address,
                    'jobclass' => $jobclassId,
                ];
                $model_business->update(['huilie_job_id' => $business_job_id], $business_upd);
                $business_info = $model_business->selectOne(['huilie_job_id' => $business_job_id], 'business_id');
                $business_id = $business_info['business_id'];

                $business_data_upd = [
                    'description' => '汇报对象:' . $business_detail_report . ';下属人数:' . $business_detail_subordinate . ';招聘人数:' . $business_number . ';到岗时间:' . $report,
                    'enddate' => $business_edate,
                    'language' => $business_language,
                    'sex_require' => $sex
                ];
                $model_business_data->update(['business_id' => $business_id], $business_data_upd);
            }
            $model_business->commit();

            $resultDo->success = TRUE;
            $resultDo->code = 200;
            $resultDo->message = json_encode($model_business);
            return $resultDo;
        } catch (Exception $ex) {
            $resultDo->success = TRUE;
            $resultDo->code = 301;
            $resultDo->message = $model_business->getDbError();
            $model_business->rollBack();
            return $resultDo;
        }
    }

}
