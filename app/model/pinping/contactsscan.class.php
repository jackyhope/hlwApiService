<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/2
 * Time: 9:32
 */

class model_pinping_contactsscan extends hlw_components_basemodel
{
    //ʱ������
    protected $daysLimit = [1 => 15, 2 => 30];
    //�鿴����
    protected $numbersLimit = [1 => 50, 2 => 0];
    //û��Ȩ�����Ƶ�roleId
    protected $superRoleId = [1];

    public function primarykey() {
        return 'id';
    }

    public function tableName() {
        return 'mx_contacts_scan_his';
    }

    /**
     * ��ѯ�Ƿ���Բ鿴��Ϣ[$itemId]
     * @param $itemId
     * @param $userRoleId
     * @param int $type 1:������Ϣ 2���ͻ���Ϣ
     * @return bool
     */
    public function isScan($itemId, $userRoleId, $type = 1) {
        //����ID
        if ($this->superRoleId && in_array($userRoleId, $this->superRoleId)) {
            return true;
        }
        if (!isset($this->daysLimit[$type])) {
            $this->setError(500, '���ʹ���');
            return false;
        }
        //����ѯ����
        $limitDays = $this->daysLimit[$type];
        //����ѯ����
        $limitCount = $this->numbersLimit[$type];

        $where = "user_role_id = {$userRoleId} and item_id = {$itemId} and item_type = {$type}";
        $info = $this->selectOne($where, '', '', 'add_time desc ');
        if ($info) {
            //�Ƿ�����������,����ڿ��Բ鿴
            $limitTimestamp = $limitDays * 86400;
            if ($info['add_time'] > (time() - $limitTimestamp)) {
                return true;
            }
            //�ͻ���Ϣֻ��30���ڿ��Բ鿴,û�в鿴��������
            if ($limitCount <= 0) {
                $this->setError(400, "��Ϣֻ��{$limitDays}���ڲ鿴");
                return false;
            }
        }
        //�Ƿ������ѯ��������
        if ($limitCount > 0) {
            //2����ѯ����鿴�����Ƿ��Ѿ�������50��1�졿
            $startTime = strtotime(date('Y-m-d 00:00:00', time()));
            $where = $where . " and add_time >= {$startTime} and add_time <= " . time();
            $counts = $this->selectOne($where, 'count(*) as counts');
            if ($counts['counts'] >= $limitCount) {
                $this->setError(400, "����鿴�����Ѿ�����{$limitCount}����");
                return false;
            }
        }
        return true;
    }

    /**
     * @desc �����Ϣ
     * @param $itemId
     * @param $userRoleId
     * @param int $type 1:������Ϣ 2���ͻ���Ϣ
     * @return bool
     */
    public function scanInfo($itemId, $userRoleId, $type = 1) {
        //��ѯȨ���ж�
        if (!$this->isScan($itemId, $userRoleId, $type)) {
            $this->setError(400, '���û������������Ϣ�� -' . $this->getError());
            return false;
        }
        //��ѯ����
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