<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/2
 * Time: 9:32
 */

class model_pinping_contactsscan extends hlw_components_basemodel
{
    //时间限制
    protected $daysLimit = [1 => 15, 2 => 30];
    //查看次数
    protected $numbersLimit = [1 => 50, 2 => 0];
    //没有权限限制的roleId
    protected $superRoleId = [1];

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'mx_contacts_scan_his';
    }

    /**
     * 查询是否可以查看信息[$itemId]
     * @param $itemId
     * @param $userRoleId
     * @param int $type 1:简历信息 2：客户信息
     * @return bool
     */
    public function isScan($itemId, $userRoleId, $type = 1) {
        //特殊ID
        if ($this->superRoleId && in_array($userRoleId, $this->superRoleId)) {
            return true;
        }
        if (!isset($this->daysLimit[$type])) {
            $this->setError(500, '类型错误！');
            return false;
        }
        //最大查询天数
        $limitDays = $this->daysLimit[$type];
        //最大查询次数
        $limitCount = $this->numbersLimit[$type];

        $where = "user_role_id = {$userRoleId} and item_id = {$itemId} and item_type = {$type}";
        $info = $this->selectOne($where, '', '', 'add_time desc ');
        if ($info) {
            //是否在有限期内,如果在可以查看
            $limitTimestamp = $limitDays * 86400;
            if ($info['add_time'] > (time() - $limitTimestamp)) {
                return true;
            }
            //客户信息只能30天内可以查看,没有查看次数限制
            if ($limitCount <= 0) {
                $this->setError(400, "信息只能{$limitDays}天内查看");
                return false;
            }
        }
        //是否满足查询次数限制
        if ($limitCount > 0) {
            //2、查询当天查看个数是否已经超量【50个1天】
            $startTime = strtotime(date('Y-m-d 00:00:00', time()));
            $where = $where . " and add_time >= {$startTime} and add_time <= " . time();
            $counts = $this->selectOne($where, 'count(*) as counts');
            if ($counts['counts'] >= $limitCount) {
                $this->setError(400, "当天查看个数已经超量{$limitCount}个了");
                return false;
            }
        }
        return true;
    }

    /**
     * @desc 浏览信息
     * @param $itemId
     * @param $userRoleId
     * @param int $type 1:简历信息 2：客户信息
     * @return bool
     */
    public function scanInfo($itemId, $userRoleId, $type = 1) {
        //查询权限判断
        if (!$this->isScan($itemId, $userRoleId, $type)) {
            $this->setError(400, '该用户不能浏览该信息啦 -' . $this->getError());
            return false;
        }
        //查询操作
        $where = ['user_role_id' => $userRoleId, 'item_id' => $itemId, 'item_type' => $type];
        $info = $this->selectOne($where, '', '', 'add_time desc ');
        try {
            if ($info) {
                $id = $info['id'];
                $scanNum = $info['scan_num'] + 1;
                return $this->update(['id' => $id, ['last_scan_time' => time(), 'scan_num' => $scanNum]]);
            }
            $where['add_time'] = time();
            $where['scan_num'] = 1;
            $this->insert($where);
        } catch (Exception $e) {
            $this->select(500, $e->getMessage());
        }
        $id = $this->lastInsertId();
        return $id > 0;
    }
}