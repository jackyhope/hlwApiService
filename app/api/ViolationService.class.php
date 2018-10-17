<?php

use com\hlw\ks\interfaces\ViolationServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\violation\ViolationDTO;

class api_ViolationService extends api_Abstract implements ViolationServiceIf
{
    /**
     * 违规查询
     * @param EngprojectRequestDTO $violationDo
     * @return ResultDO
     */
    public function violat(ViolationDTO $violationDo)
    {
        $result = new ResultDO();

        $id = $violationDo->id ? gdl_lib_BaseUtils::getStr($violationDo->id) : 0;
        $field = 'behavior,person_id,person_name,create_time';

        try {
            $eventmonitor = new model_newexam_eventmonitor();
            $res_event_monitor = @$eventmonitor->selectOne('eventid = ' . $id, 'serial_number,aisle');
            
            if ($res_event_monitor['serial_number'] > 0) {
                $record = new model_cl_behaviorrecord();
                $res = $record->select("nvr_sn='" . $res_event_monitor['serial_number'] . "' and device_ipc_id in(" . $res_event_monitor['aisle'] . ')', $field, '', 'order by id desc limit 10')->items;
                $result->data = $res;
            } else {
                $result->message = '未查到相关数据';
            }
            
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
