<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-12
 * Time: 17:17
 */

class model_pinping_user extends hlw_components_basemodel
{

    protected $rankModel;
    protected $positionModel;
    protected $roleModel;
    protected $roleDepartmentModel;

    static $types = [
        0 => '无',
        1 => '面试快',
        2 => '入职快',
        3 => '专业猎头',
        4 => '猎萝卜',
        5 => 'BD',
        6 => '其他'
    ];


    public function primarykey() {
        return 'user_id';
    }

    public function tableName() {
        return 'mx_user';
    }

    public function __construct($pkid = false) {
        parent::__construct($pkid);
        $this->rankModel = new model_pinping_jobRank();
        $this->positionModel = new model_pinping_position();
        $this->roleModel = new model_pinping_role();
        $this->roleDepartmentModel = new model_pinping_roleDepartment();
    }

    /**
     * @desc 用户列表获取
     * @param $day string 月份
     * @param $name string 查询字段名字
     * @param $type number 员工业态
     * @param $department number 上级部门ID
     * @param $roleIds string 权限IDS
     * @return array
     */
    public function userReachList($day, $name, $type, $department,$roleIds) {
        $where = "status = 1 and profession_type in (1,2,3,4,5)";
        //获取部门员工IDs
        $roles = '';
        $department > 0 && $roles = $this->departmentRoles($department);
        $roles && $where .= " and role_id in ({$roles})";
        $name && $where .= " and full_name like '%{$name}%' ";
        $type && $where .= " and profession_type = {$type} ";
        $roleIds && $where .= " and role_id in ({$roleIds}) ";

        $item = "user_id,full_name,role_id,status,type,job_rank,profession_type,name,full_name,entry,graduation_time";
        $list = $this->select($where, $item, '', "order by user_id desc");
        $counts = $list->totalSize;
        $currentPage = $list->page;
        $pageSize = $list->pageSize;
        $list = $list->items ? $list->items : [];

        //职位部门关系
        $roleDeparts = $this->roleModel->roleDeparts();
        $parentDeparts = $this->roleDepartmentModel->pList();
        //原始业绩
        $target = $this->target();
        //等级列表
        $ranks = $this->rankModel->ranks();
        //产品类型
        $types = self::$types;
        //实际业绩
        $achievement = $this->achievements($day);
        //考勤数据
        $attendDay = $this->workDays($day);

        foreach ($list as &$info) {
            $roleId = $info['role_id'];
            $userId = $info['user_id'];
            $rankId = $info['job_rank'];
            $graduationTime = $info['graduation_time'];
            $professionType = $info['profession_type'];
            $targetKey = $professionType . "_" . $rankId;
            //产品
            $info['profession_type'] = isset($types[$professionType]) ? $types[$professionType] : '无';
            //职级
            $info['rank'] = isset($ranks[$rankId]) ? $ranks[$rankId] : '无';
            //1、部门，事业部
            $info['department_name'] = $roleDeparts[$roleId]['name'];
            $info['department_id'] = $roleDeparts[$roleId]['id'];
            $info['p_department_name'] = $parentDeparts[$roleDeparts[$roleId]['id']]['name'];
            $info['p_department_id'] = $parentDeparts[$roleDeparts[$roleId]['id']]['id'];
            //2、毕业时间维护
            $this->graduateTime($info['entry'], $roleId, $graduationTime);
            //是否豁免期内
            $isTraining = $this->isTraining($graduationTime, $professionType);
            //3、出勤
            $info['work_day'] = $attendDay[$roleId]['work_days'] ? $attendDay[$roleId]['work_days'] : 0;
            //4、原始业绩
            $info['target'] = isset($target[$targetKey]) ? $target[$targetKey] : '';
            //5、折算业绩
            $info['discount'] = $this->discountAchieve($info['target'], $attendDay[$roleId], $isTraining);
            //6、实际业绩 计算当月个人所有分配业绩之和
            $info['achievement'] = isset($achievement[$userId]) ? $achievement[$userId] : 0;
            //7、达成率    实际业绩/折算后晋升业绩；如果该员工在豁免期内，则显示为“豁免期”
            $info['achievement_rate'] = $this->achievementRate($info['discount'], $info['achievement'], $isTraining);
        }
        $list = $this->buildData($list);
        return [
            'list' => $list,
            'current_page' => $currentPage,
            'counts' => $counts,
            'listrows' => $pageSize
        ];
    }

    /**
     * @desc  数据组装
     * @param $list
     * @return array
     */
    private function buildData($list) {
        if (!$list) {
            return [];
        }
        $departs = [];
        foreach ($list as &$info) {
            //部门数据
            $departmentId = $info['department_id'];
            if (!$departmentId) {
                continue;
            }
            $departs[$departmentId]['name'] = $info['department_name'];
            $departs[$departmentId]['id'] = $info['department_id'];
            $departs[$departmentId]['p_id'] = $info['p_department_id'];
            $departs[$departmentId]['p_name'] = $info['p_department_name'];
            $departs[$departmentId]['count'] += 1; //人数
            $departs[$departmentId]['sum'] += $info['achievement']; //实际业绩
            $departs[$departmentId]['discount'] += $info['discount']; //折扣业绩
            $departs[$departmentId]['target'] += $info['target']; //期望业绩
            $departs[$departmentId]['achievement_rate'] = round($departs[$departmentId]['sum'] / $departs[$departmentId]['discount']); //部门达成率
            $departs[$departmentId]['average'] = round($departs[$departmentId]['sum'] / $departs[$departmentId]['count']); //人均产值
            $departs[$departmentId]['list'][] = $info;
        }

        if (!$departs) {
            return $departs;
        }
        $departs = array_values($departs);
        $pList = [];
        foreach ($departs as $pInfo) {
            $pId = $pInfo['p_id'];
            if (!$pId) {
                continue;
            }
            $pList[$pId]['name'] = $pInfo['p_name'];
            $pList[$pId]['id'] = $pInfo['p_id'];
            $pList[$pId]['p_id'] = 0;
            $pList[$pId]['p_name'] = '';
            $pList[$pId]['count'] += $pInfo['count'];
            $pList[$pId]['sum'] += $pInfo['sum'];
            $pList[$pId]['discount'] += $pInfo['discount'];
            $pList[$pId]['target'] += $pInfo['target'];
            $pList[$pId]['achievement_rate'] += round($pList[$pId]['sum'] / $pList[$pId]['discount']);
            $pList[$pId]['average'] = round($pList[$pId]['sum'] / $pList[$pId]['count']);
            $pList[$pId]['list'][] = $pInfo;
        }
        return array_values($pList);
    }

    /**
     * @desc  实际业绩/折算后晋升业绩；
     *        如果该员工在豁免期内，则显示为“豁免期”
     * @param $discount
     * @param $achievement
     * @param $isTraining
     * @return float|int|string
     */
    private function achievementRate($discount, $achievement, $isTraining) {
        if ($isTraining) {
            return '豁免期';
        }
        $rate = $achievement / $discount;
        return $rate ? $rate : 0;

    }

    /**
     * @desc 获取用户数组
     * @param $where
     * @return array
     */
    public function users($where) {
        $users = $this->select($where, "role_id,user_id,full_name", '', ['user_id' => 'desc']);
        $list = $users->items;
        $return = [];
        foreach ($list as $info) {
            $return[$info['role_id']] = trim($info['full_name']);
        }
        return $return;
    }

    /**
     * @desc 获取部门下面的员工roles
     * @param $departmentId
     * @return array
     */
    private function departmentRoles($departmentId) {
        $departments = $this->roleDepartmentModel->getSubDepartmentBrId($departmentId);
        $positionIds = $this->positionModel->positionIds($departments);
        $positionIds = implode(',', $positionIds);
        return $this->roleModel->getRolesByPosition($positionIds);
    }

    /**
     * @desc 出勤数据 由行政部每个月导入一次出勤数据
     * @param $day
     * @return array
     */
    private function workDays($day) {
        $attendanceModel = new model_pinping_userAttendance();
        $list = $attendanceModel->lists($day);
        return $list;
    }

    /**
     * @desc  原始晋级目标 原始晋级业绩：由人力部门制定，系统设置
     * @return array
     */
    private function target() {
        $targetModel = new model_pinping_ranktarget();
        return $targetModel->lists();
    }

    /**
     * @desc  实际业绩数据
     * 计算当月个人所有分配业绩之和
     * @return array
     */
    private function achievements($moth) {
        $achievementModel = new model_pinping_achievement();
        $list = $achievementModel->lists($moth);
        return $list;
    }

    /**
     * @desc 折算后晋级业绩：
     * 计算公式为：原始晋级业绩*（当月出勤天数/当月工作日），如果该员工在豁免期内，则显示为“0”
     * @param $target
     * @param $attendDay
     * @param bool $isTrain
     * @return float|int
     */
    private function discountAchieve($target, $attendDay, $isTrain = false) {
        if ($isTrain) {
            return 0;
        }
        $attendDays = $attendDay['attend_day'];//应出勤天数 工作日
        $workDays = $attendDay['work_days']; //实际出勤天数
        if (!$attendDays || !$workDays) {
            return 0;
        }
        return $target * ($workDays / $attendDays);
    }

    /**
     * @desc 豁免期
     * 豁免期：入职快、猎萝卜、专业猎头、BD这4种产品形态有2个月豁免期，豁免期间不统计折算后晋升业绩和达成率，计算方式：
     * 豁免期：入职快、猎萝卜、专业猎头、BD这4种产品形态有2个月豁免期，豁免期间不统计折算后晋升业绩和达成率，计算方式：
     * （1）景升学院毕业时间在自然月15日及之前，当月和次月为豁免期
     * （2）景升学院毕业时间在自然月15日之后，次月和下下月为豁免期
     * 景升学院毕业时间=入职时间+7天工作日
     * 面试快新入职折算后晋升业绩出勤时间：员工从景升学院毕业时间+1开始计算
     * //产品类型1面试快2入职快3专业猎头4猎萝卜5BD6其它
     * @param $graduationTime
     * @param int $type
     * @return bool
     */
    private function isTraining($graduationTime, $type = 1) {
        if (!$graduationTime) {
            return true;
        }
        !is_numeric($graduationTime) && $graduationTime = strtotime($graduationTime);
        if ($graduationTime > time()) {
            return true;
        }

        if ($type == 1) {
            return false;
        }
        $day = date('d', $graduationTime);
        if ($day >= 15) {
            $month = 3;
        } else {
            $month = 2;
        }
        $trainDay = strtotime(date("Y-m-01", strtotime("+{$month} month", $graduationTime)));
        if ($trainDay > time()) {
            return true;
        }
        return false;
    }

    /**
     * @desc 获取入职数据
     * @param $trainDays
     * @param $enterTime
     * @param $thisMoth
     * @return array
     */
    private function enterTime($trainDays, $enterTime, $thisMoth) {
        //计算豁免期前7天 和 后7天时间
        $enterTime = strtotime($enterTime);
        if ($enterTime > $trainDays[13]) {
            return ['enter_time' => $enterTime, 'trainDays' => 0];
        }
        //培训期账号
        $end = $enterTime;
        $workDays = 0;
        if ($enterTime > $trainDays[0] && $enterTime < $trainDays[13]) {
            //计算培训时间
            $times = 0;
            foreach ($trainDays as $day => $days) {
                if ($days < $enterTime) {
                    continue;
                }
                if ($days > $thisMoth) {
                    $workDays++;
                }
                $times++;
                $end = $days;
                if ($times == 7) {
                    break;
                }
            }
        } else {
            $workDays = "豁免期";
        }
        return ['enter_time' => $end, 'trainDays' => $workDays];
    }

    /**
     * @desc  毕业时间
     * @param $enterTime
     * @param $roleId
     * @param $graduationTime
     * @return false|string
     */
    private function graduateTime($enterTime, $roleId, $graduationTime) {
        $daysCount = 7;
        if ($graduationTime) {
            return;
        }
        if (!is_numeric($enterTime)) {
            $enterTime = strtotime($enterTime);
        }

        if (!$enterTime || $enterTime > strtotime('-7 day')) {
            return;
        }

        $enterMoth = date("Y-m", $enterTime);
        $holidays = $this->getHolidayDay($enterMoth, true);
        $holiday = $holidays['holiday'];
        $workWeek = $holidays['workWeek']; //调休
        $enterDay = date('Y-m-d', $enterTime);
        $days = 0;
        $thisDay = $enterDay;
        while ($days < $daysCount) {
            $thisDay = date("Y-m-d", strtotime("+1 day", strtotime($thisDay)));
            if (array_search($thisDay, $holiday)) {
                continue;
            }
            $week = date('w', strtotime($thisDay));
            if (($week == 0 || $week == 6) && !array_search($thisDay, $workWeek)) {
                continue;
            }
            $days++;
        }
        //维护
        $thisDay && $this->update(['role_id' => $roleId], ['graduation_time' => $thisDay]);
        return $thisDay;

    }

    /**
     * @desc  获取这个月的培训期
     * @param $weekDay
     * @param $thisMoth
     * @return false|string
     */
    private function getTrainEnd($weekDay, $thisMoth) {
        $holiday = $weekDay['holiday'];
        $workWeek = $weekDay['workWeek']; //调休
        //前7天
        $thisMoth = date('Y-m-01', $thisMoth);
        $days = 0;
        $thisDay = $thisMoth;
        $trainEnds = [];
        while ($days < 7) {
            $thisDay = date("Y-m-d", strtotime("+1 day", strtotime($thisDay)));
            if (array_search($thisDay, $holiday)) {
                continue;
            }
            $week = date('w', strtotime($thisDay));
            if (($week == 0 || $week == 6) && !array_search($thisDay, $workWeek)) {
                continue;
            }
            $trainEnds[$thisDay] = strtotime($thisDay);
            $days++;
        }
        return $trainEnds;
    }

    /**
     * @desc  获取这个月的培训期
     * @param $weekDay
     * @param $thisMoth
     * @return false|string
     */
    private function getTrainStart($weekDay, $thisMoth) {
        $holiday = $weekDay['holiday'];
        $workWeek = $weekDay['workWeek']; //调休
        //前7天
        $thisMoth = date('Y-m-01', $thisMoth);
        $days = 7;
        $thisDay = $thisMoth;
        $trainEnds = [];
        while ($days > 0) {
            $thisDay = date("Y-m-d", strtotime("-1 day", strtotime($thisDay)));
            if (array_search($thisDay, $holiday)) {
                continue;
            }
            $week = date('w', strtotime($thisDay));
            if (($week == 0 || $week == 6) && !array_search($thisDay, $workWeek)) {
                continue;
            }
            $trainEnds[$thisDay] = strtotime($thisDay);
            $days--;
        }
        return $trainEnds;
    }

    /**
     * @desc 获取节假日和周末上班日期
     * @param string $moth
     * @param string $isCurrent
     * @return array
     */
    public static function getHolidayDay($moth = '', $isCurrent = false) {
        !$moth && $moth = date('Y-m-01', time());
        $moth = strtotime($moth);
        $year = date('Y', $moth);
        $day = date('Y-m', $moth);

        $lastMoth = date('Y-m', strtotime("-1 month"));
        $holidayApi = "http://timor.tech/api/holiday/year/{$day}";

        $response = hlw_lib_BaseUtils::file_get_contents_safe($holidayApi);
        $response = json_decode($response, true);
        $holiday = $response['holiday'];

        $holidayApiLast = "http://timor.tech/api/holiday/year/{$lastMoth}";
        $responseLast = hlw_lib_BaseUtils::file_get_contents_safe($holidayApiLast);
        $responseLast = json_decode($responseLast, true);
        $holidayLast = $responseLast['holiday'];
        if (!$holiday || !$holidayLast) {
            return [];
        }
        $holidays = [];
        $workWeek = [];
        if ($holiday) {
            foreach ($holiday as $key => $days) {
                $isHoliday = $days['holiday'];
                $dateDay = $year . '-' . $key;
                if ($isHoliday) {
                    $holidays[$key] = $dateDay;
                } else {
                    $workWeek[$key] = $dateDay;
                }
            }
        }
        if (!$isCurrent) {
            //上个月
            if ($holidayLast) {
                foreach ($holidayLast as $key => $days) {
                    $isHoliday = $days['holiday'];
                    $dateDay = $year . '-' . $key;
                    if ($isHoliday) {
                        $holidays[$key] = $dateDay;
                    } else {
                        $workWeek[$key] = $dateDay;
                    }
                }
            }
        }

        return ['holiday' => $holidays, 'workWeek' => $workWeek];
    }

}