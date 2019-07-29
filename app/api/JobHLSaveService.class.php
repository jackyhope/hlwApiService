<?php
/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 慧猎网职位发布/修改
 * User:
 * Date: 2019/7/16
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */

use com\hlw\huiliewang\interfaces\company\JobAddServiceIf;
use com\hlw\huiliewang\dataobject\company\JobAddRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_JobHLSaveService extends api_Abstract implements JobAddServiceIf
{
    protected $jobModel;
    protected $jobClass;
    protected $company;

    public function __construct() {
        $this->jobModel = new model_huiliewang_companyjob();
        $this->jobClass = new model_huiliewang_jobclass();
        $this->company = new model_huiliewang_company();
    }

    public function saveJob(JobAddRequestDTO $addRequestDo) {
        $result = new ResultDO();
        $uId = hlw_lib_BaseUtils::getStr($addRequestDo->uId, 'int');
        $name = hlw_lib_BaseUtils::getStr($addRequestDo->name);
        $minsalary = hlw_lib_BaseUtils::getStr($addRequestDo->minsalary);
        $maxsalary = hlw_lib_BaseUtils::getStr($addRequestDo->maxsalary);
        $salaryMonth = hlw_lib_BaseUtils::getStr($addRequestDo->ejob_salary_month, 'int');
        $description = hlw_lib_BaseUtils::getStr($addRequestDo->description);
        $detailReport = hlw_lib_BaseUtils::getStr($addRequestDo->detail_report);
        $provinceid = hlw_lib_BaseUtils::getStr($addRequestDo->provinceid, 'int');
        $subordinate = hlw_lib_BaseUtils::getStr($addRequestDo->detail_subordinate, 'int');
        $hy = hlw_lib_BaseUtils::getStr($addRequestDo->hy, 'int');
        $number = hlw_lib_BaseUtils::getStr($addRequestDo->number, 'int');
        $exp = hlw_lib_BaseUtils::getStr($addRequestDo->exp, 'int');
        $report = hlw_lib_BaseUtils::getStr($addRequestDo->report, 'int');
        $age = hlw_lib_BaseUtils::getStr($addRequestDo->age, 'int');
        $sex = hlw_lib_BaseUtils::getStr($addRequestDo->sex, 'int');
        $edu = hlw_lib_BaseUtils::getStr($addRequestDo->edu, 'int');
        $marriage = hlw_lib_BaseUtils::getStr($addRequestDo->marriage, 'int');
        $tblink = hlw_lib_BaseUtils::getStr($addRequestDo->tblink, 'int');
        $lang = hlw_lib_BaseUtils::getStr($addRequestDo->lang);
        $welfare = hlw_lib_BaseUtils::getStr($addRequestDo->welfare);
        $jobPost = hlw_lib_BaseUtils::getStr($addRequestDo->job_post);
        $jobId = hlw_lib_BaseUtils::getStr($addRequestDo->jobId, 'int');
        $edate = hlw_lib_BaseUtils::getStr($addRequestDo->edate, 'int');
        $service_type = hlw_lib_BaseUtils::getStr($addRequestDo->service_type, 'int');

        $result->code = 500;
        $result->success = false;
        $result->message = '';
        if (!$uId || !$name) {
            $result->message = '参数错误';
            return $result;
        }
        //检查职位名是否存在
        $jobInfo = $this->jobModel->selectOne(['uid' => $uId, 'name' => $name]);
        if ($jobInfo && $jobInfo['uid'] !== $uId) {
            $result->code = 200;
            $result->success = true;
            $result->message = $jobInfo['id'];
            return $result;
        }
        //检查职位是否存在
        if ($jobId > 0) {
            $jobInfo = $this->jobModel->selectOne(['id' => $jobId]);
            if (!$jobInfo) {
                $result->message = '职位信息获取失败';
                return $result;
            }
        }
        //企业信息
        $companyInfo = $this->company->selectOne(['uid' => $uId]);
        //数据
        $name = $this->characet($name,'gbk');
        $description =  $this->characet($description,'gbk');
        $detailReport = $this->characet($detailReport,'gbk');

        $data = [
            'name' => $name,
            'uid' => $uId,
            'sdate' => time(),
            'lastupdate' => time(),
            'lang' => $lang,
            'welfare' => $welfare,
            'pr' => $companyInfo['pr'],
            'com_name' => $companyInfo['name'],
            'com_logo' => $companyInfo['logo'],
            'com_provinceid' => $companyInfo['provinceid'],
            'mun' => $companyInfo['mun'],
            'fake_id' => 0,
            'type' => 0,
            'marriage' => $marriage,
            'edu' => $edu,
            'sex' => $sex,
            'age' => $age,
            'report' => $report,
            'exp' => $exp,
            'number' => $number,
            'hy' => $hy,
            'detail_subordinate' => $subordinate,
            'provinceid' => $provinceid,
            'detail_report' => $detailReport,
            'description' => $description,
            'ejob_salary_month' => $salaryMonth,
            'minsalary' => $minsalary,
            'maxsalary' => $maxsalary,
            'did' => 0,
            'edate' => $edate,
            'detail_dept_id' => '',
            'state' => 1,
            'service_type' => $service_type,
        ];

        if ($jobPost) {
            $data['job_post'] = $jobPost;
            $row1 = $this->jobClass->selectOne(['id' => intval($jobPost)], 'keyid');
            $row2 = $this->jobClass->selectOne(['id' => $row1['keyid']], 'keyid');
            if ($row2['keyid'] == '0') {
                $data['job1_son'] = intval($jobPost);
                $data['job1'] = $row1['keyid'];
                unset($data['job_post']);
            } else {
                $data['job1_son'] = $row1['keyid'];
                $data['job1'] = $row2['keyid'];
            }
        }

        try {
            if ($jobId > 0) {
                $this->jobModel->update(['id' => $jobId], $data);
            } else {
                $this->jobModel->insert($data);
                $result->message = var_export( $jobPost, true);
                return $result;
            }
        } catch (Exception $e) {
            $result->message = '职位添加失败' . $e->getMessage();
            return $result;
        }
        $result->code = 200;
        $result->success = true;
        $result->message = $jobId;
        return $result;
    }

    /**
     * 编码转换
     * @param $data
     * @param string $charSet
     * @return string
     */
    function characet($data, $charSet = 'UTF-8')
    {
        if (!empty($data)) {
            $fileType = mb_detect_encoding($data, array('UTF-8', 'GBK', 'LATIN1', 'BIG5'));
            if ($fileType != $charSet) {
                $data = mb_convert_encoding($data, $charSet, $fileType);
            }
        }
        return $data;
    }
}