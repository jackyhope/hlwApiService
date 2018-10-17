<?php

use com\hlw\ks\interfaces\TaskorderServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\taskorder\TaskorderDTO;
date_default_timezone_set("PRC");

class api_TaskorderService extends api_Abstract implements TaskorderServiceIf
{

    /**
     * 获取对应培训室任务列表
     */
    public function tasklist(TaskorderDTO $taskorder) 
    {
        $result = new ResultDO();

        $id = $taskorder->id ? gdl_lib_BaseUtils::getStr($taskorder->id,'int') : 0;
		$field = $taskorder->field ? gdl_lib_BaseUtils::getStr($taskorder->field) : '*';
		$limit = $taskorder->limit ? gdl_lib_BaseUtils::getStr($taskorder->limit) : 0;
		$mark = $taskorder->mark ? gdl_lib_BaseUtils::getStr($taskorder->mark,'int') : 1;
		$sign = $taskorder->sign ? gdl_lib_BaseUtils::getStr($taskorder->sign,'int') : 0;

        if (!$id) {
            $result->success = false;
            $result->code = 0;
            $result->message = '缺少参数ID';
            return $result;
        }

        try {
			$tim = $taskorder->datime ? $taskorder->datime : time();
			$tim_end = $tim+86400;
			$taskorders = new model_newexam_taskorders();
			$condition = "mark={$mark} and isdelete=0 and status=1 and UNIX_TIMESTAMP(end_time) >".$tim_end;
			if($sign==3){
				$tim = $tim-1;
				$condition .= " and UNIX_TIMESTAMP(release_time)>".$tim."  and UNIX_TIMESTAMP(start_time) < ".time();
			} else if($sign==2){
				$tim = $tim-1;
				$condition .= " and UNIX_TIMESTAMP(release_time)=".$tim."  and UNIX_TIMESTAMP(start_time) < ".$tim;;
			} else {
				$condition .= "  and UNIX_TIMESTAMP(start_time) < ".$tim;
			}
			$condition .= " order by id desc";
			if($limit){
				$condition .= ' limit '.$limit;
			} 
            $res = $taskorders->select($condition, $field)->items;

            $result->data = $res;
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
	/**
     * 获取对应培训室任务详情
     */
    public function taskdetails(TaskorderDTO $taskorder) 
    {
        $result = new ResultDO();

        $id = $taskorder->id ? gdl_lib_BaseUtils::getStr($taskorder->id,'int') : 0;
		$field = $taskorder->field ? gdl_lib_BaseUtils::getStr($taskorder->field) : '*';


        if (!$id || !pid) {
            $result->success = false;
            $result->code = 0;
            $result->message = '缺少参数ID';
            return $result;
        }

        try {

			
			$taskorders = new model_newexam_taskorders();
			$condition = "id='{$id}'";

            $res = $taskorders->select($condition, $field)->items;

            $result->data = $res;
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
	
	///公告
	public function bulletinlist(TaskorderDTO $taskorder) 
    {
        $result = new ResultDO();

        $id = $taskorder->id ? gdl_lib_BaseUtils::getStr($taskorder->id,'int') : 0;
		$admin_reg = $taskorder->admin_reg ? $taskorder->admin_reg : 0;
		$limit = $taskorder->limit ? gdl_lib_BaseUtils::getStr($taskorder->limit) : 10;
		$field = $taskorder->field ? gdl_lib_BaseUtils::getStr($taskorder->field) : '*';

        if (!$id) {
            $result->success = false;
            $result->code = 0;
            $result->message = '缺少参数ID';
            return $result;
        }

        try {
			if($admin_reg){
				$bulletin = new model_newexam_bulletin();
				$condition = "admin_reg in({$admin_reg}) and isdelete=0 and status=1";

				$condition .= " order by id desc";
				if($limit){
					$condition .= ' limit '.$limit;
				}
				$res = $bulletin->select($condition, $field)->items;

				$result->data = $res;
			} else {
				
				$result->data = [[]];
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
