<?php

use com\hlw\huilie\interfaces\JobServiceIf;
use com\hlw\huilie\dataobject\job\JobRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_JobService extends api_Abstract implements JobServiceIf {

    public function saveJob(JobRequestDTO $saveJobDo) {
        $resultDo = new ResultDO();

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
            $resultDo->message = '发放月数';
            return $resultDo;
        }

        if (!$saveJobDo->description) {
            $resultDo->success = TRUE;
            $resultDo->code = 500;
            $resultDo->message = '岗位简介';
            return $resultDo;
        }

        try {
            $business_name = hlw_lib_BaseUtils::getStr($saveJobDo->name, 'string');
            $business_edate = hlw_lib_BaseUtils::getStr($saveJobDo->edate, 'string');
            $business_minsalary = hlw_lib_BaseUtils::getStr($saveJobDo->minsalary, 'int');
            $business_maxsalary = hlw_lib_BaseUtils::getStr($saveJobDo->maxsalary, 'int');
            $business_ejob_salary_month = hlw_lib_BaseUtils::getStr($saveJobDo->ejob_salary_month, 'int');
            $business_description = hlw_lib_BaseUtils::getStr($saveJobDo->description);
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




            $exp_arr = hlw_lib_Constant::$huilie_to_oa_exp[$business_exp];
            $edu = hlw_lib_Constant::$huilie_to_oa_edu[$business_edu];
            $report = hlw_lib_Constant::$huilie_to_oa_report[$business_report];
            $age = hlw_lib_Constant::$huilie_to_oa_age[$business_age];
            $sex = hlw_lib_Constant::$huilie_to_oa_sex[$business_sex];
            $hy = hlw_lib_Constant::$huilie_to_oa_hy[$business_hy];

            $model_business = new model_pinping_business();
            $model_business_data = new model_pinping_businessdata();

            $business_ins = [
                'name' => $business_name,
                'minsalary' => $business_minsalary * $business_ejob_salary_month,
                'maxsalary' => $business_maxsalary * $business_ejob_salary_month,
                'requirement' => $business_description,
                'minexp' => $exp_arr[0],
                'maxexp' => $exp_arr[1] > 0 ? $exp_arr[1] : 40,
                'minage' => $age[0],
                'maxage' => $age[1] > 0 ? $age[1] : 65,
                'education' => $edu,
                'industry' => $hy
            ];

            $business_data_ins = [
                'description' => '汇报对象:' . $business_detail_report . ';下属人数:' . $business_detail_subordinate . ';招聘人数:' . $business_number . ';到岗时间:' . $report,
                'enddate' => strtotime($business_edate),
                'language' => $business_language,
                'sex_require' => $sex
            ];
        } catch (Exception $ex) {
            
        }
    }

}
