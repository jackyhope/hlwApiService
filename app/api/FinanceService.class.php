<?php
/**
 * @desc 财务
 */

use com\hlw\huiliewang\interfaces\finance\ReachServiceIf;
use com\hlw\huiliewang\dataobject\finance\reachRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_FinanceService extends api_Abstract implements ReachServiceIf
{
    protected $userMode;
    protected $excelTitle = [
        'user_name' => '姓名',
        'work_days' => '实际出勤天数',
        'attendance_days' => '应出勤天数',
        'probation_days' => '试用期天数',
    ];

    public function __construct() {
        $this->userMode = new model_pinping_user();
    }

    /**
     * @desc  业绩列表
     * @param ReachRequestDTO $reachRequestDTO
     * @return ResultDO
     */
    public function listReach(reachRequestDTO $reachRequestDTO) {
        $resultDo = new ResultDO();
        $day = hlw_lib_BaseUtils::getStr($reachRequestDTO->day);
        $name = hlw_lib_BaseUtils::getStr($reachRequestDTO->name);
        $type = hlw_lib_BaseUtils::getStr($reachRequestDTO->type, 'int');
        $department = hlw_lib_BaseUtils::getStr($reachRequestDTO->department, 'int');
        $page = hlw_lib_BaseUtils::getStr($reachRequestDTO->page, 'int');
        $pageSize = hlw_lib_BaseUtils::getStr($reachRequestDTO->pageSize, 'int');
        $roleIds = hlw_lib_BaseUtils::getStr($reachRequestDTO->roleIds);

        if (!$day) {
            $resultDo->success = true;
            $resultDo->code = 500;
            $resultDo->message = '缺少必传参数';
            return $resultDo;
        }
        $this->userMode->setCount(true);
        $this->userMode->setPage($page);
        $this->userMode->setLimit($pageSize);
        $list = $this->userMode->userReachList($day, $name, $type, $department,$roleIds);
        $resultDo->success = '';
        $resultDo->code = 200;
        $resultDo->message = json_encode($list);
        return $resultDo;
    }

    /**
     * @desc 考勤数据导入
     * @param reachRequestDTO $reachRequestDTO
     * @return ResultDO
     */
    public function weekdayExport(reachRequestDTO $reachRequestDTO) {
        $resultDo = new ResultDO();
        $path = hlw_lib_BaseUtils::getStr($reachRequestDTO->path);
        $date = hlw_lib_BaseUtils::getStr($reachRequestDTO->dataDate);
        $fileId = hlw_lib_BaseUtils::getStr($reachRequestDTO->file_id);
        $resultDo->success = false;
        $resultDo->code = 500;
        if (!$path || !$date) {
            $resultDo->message = '缺少必传参数';
            return $resultDo;
        }
        try {
            $sExcel = new SExcel();
            $data = $sExcel->importExcel($path);
            if (!$data) {
                throw new \Exception('EXCEL 数据错误');
            }
            $users = $this->userMode->users(['status' => 1]);
            $attendanceMode = new model_pinping_userAttendance();
            $attendData = [];
            foreach ($data as $key => $userInfo) {
                if ($key == 0) {
                    continue;
                }
                $userName = trim($userInfo['姓名']);
                if (!$userName) {
                    continue;
                }
                $userId = array_search($userName, $users);
                $userId = $userId ? $userId : 0;
                if (!$userId) {
                    throw new \Exception('用户:' . $userName . '不存在，请核对');
                }
                $info = [
                    'month' => strtotime($date),
                    'attendance_days' => $userInfo[$this->excelTitle['attendance_days']],
                    'work_days' => $userInfo[$this->excelTitle['work_days']],
                    'probation_days' => $userInfo[$this->excelTitle['probation_days']],
                    'user_name' => $userName,
                    'role_id' => $userId,
                    'file_id' => $fileId,
                    'create_time' => time(),
                ];
                if($info['attendance_days'] < $info['work_days']){
                    throw new \Exception("员工：{$userName} 的 {$this->excelTitle['work_days']} 不能高于 {$this->excelTitle['attendance_days']}");
                }
                $attendWhere = ['role_id' => $userId, 'month' => strtotime($date)];

                $isEsit = $attendanceMode->info($attendWhere);
                if ($isEsit) {
                    $attendanceMode->updateInfo($attendWhere, $info);
                } else {
                    $attendData[$userId] = $info;
                }

            }
            $attendData = array_values($attendData);
            if ($attendData) {
                $res = $attendanceMode->addAll($attendData);
                if (!$res) {
                    throw new \Exception("数据导入失败");
                }
            }

        } catch (\Exception $e) {
            $resultDo->message = $e->getMessage();
            return $resultDo;
        }
        $resultDo->success = true;
        $resultDo->code = 200;
        $resultDo->message = '成功';
        return $resultDo;
    }

    /**
     * @desc 获取节假日和周末上班日期
     * @param string $moth
     * @return array
     */
    private function getWeekDay($moth = '') {
        $data = model_pinping_user::getWeekDay($moth);
        return $data;
    }
}