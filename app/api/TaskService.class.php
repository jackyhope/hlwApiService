<?php

use com\hlw\ks\interfaces\TaskServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\task\TaskDTO;
use com\hlw\ks\dataobject\taskuser\TaskUserDTO;
date_default_timezone_set("PRC");

class api_TaskService extends api_Abstract implements TaskServiceIf
{

    //根据任务ID 获取任务基本信息
    public function getTaskInfo(TaskDTO $task){
        $result = new ResultDO();
        $id = $task->id ? gdl_lib_BaseUtils::getStr($task->id,'int') : 0;
        $identity_id = $task->identity_id ? gdl_lib_BaseUtils::getStr($task->identity_id,'int') : 0;
        if (!$id || $identity_id==0) {
            $result->success = false;
            $result->code = 0;
            $result->message = '缺少参数ID';
            return $result;
        }
        try{
            $taskmodel = new model_newexam_task();
            $taskConditionModel = new model_newexam_taskcondition();
            $taskTypeModel = new model_newexam_tasktype();
            $taskConditionRelationModel = new model_newexam_taskconditionrelation();//任务条件关联
            $taskInfo = [];
            //获取任务基本信息
            $condition = "ex_task.id='{$task->id}'";
            $field =  'count(ex_task_user.identity_id) as is_get_task,ex_task.task_classify,ex_task_classify_url_relation.url,ex_task.task_name,ex_task_type.type_name,ex_task.task_type,ex_task_type.id as task_type_id,ex_task.task_describe,ex_task.task_start_time,ex_task.task_end_time';
            $res = $taskmodel->selectOne(
                $condition,
                $field,
                '',
                '',
                array("ex_task_type" => "ex_task.task_type = ex_task_type.id",
                    "ex_task_classify_url_relation" => "ex_task_classify_url_relation.classify_id = ex_task.task_classify",
                    "ex_task_user" => "ex_task.id = ex_task_user.task_id and ex_task_user.identity_id= {$identity_id}",
                    )
            );

            if ($res) {
                $taskInfo['is_get_task'] = $res['is_get_task'];
                $taskInfo['task_classify'] = $res['task_classify'];
                $taskInfo['url'] = $res['url'];
                $taskInfo['task_type'] = $res['task_type'];
                $taskInfo['task_name'] = $res['task_name'];
                $taskInfo['task_type_id'] = $res['task_type_id'];
                $taskInfo['type_name'] = $res['type_name'];
                $taskInfo['task_describe'] = $res['task_describe'];
                $taskInfo['task_start_time'] = $res['task_start_time'];
                $taskInfo['task_end_time'] = $res['task_end_time'];
                //获取该任务的完成条件
                //ex_task_reward.reward_type,ex_task_reward.reward_parm
                $conditionList = $taskConditionRelationModel->select(
                    "ex_task_condition_relation.task_id='{$task->id}'",
                    'condition_name,condition_describe,ex_task_condition.id as condition_id,ex_task_condition_relation.condition_type',
                    '',
                    '',
                    array("ex_task_condition" => "ex_task_condition_relation.condition_id = ex_task_condition.id"
                    )
                )->items;

                $conditionids = [];
                foreach ($conditionList as $cv){
                    $conditionids[] = $cv['condition_id'];
                }
                $conditionids = implode(',',$conditionids);

                $rewardConditionLists = $taskConditionModel->select(
                    "ex_task_condition.id in ({$conditionids}) ",
                    'ex_task_reward.reward_type,ex_task_reward.reward_parm,ex_task_reward.reward_describe,ex_task_condition.id as condition_id',
                    '',
                    '',
                    array("ex_task_condition_reward_relation" => "ex_task_condition.id = ex_task_condition_reward_relation.condition_id",
                        "ex_task_reward" => "ex_task_reward.id = ex_task_condition_reward_relation.reward_id"
                        )
                )->items;

                //获取每个任务的类型信息
                $taskTypeLists = $taskTypeModel->select(
                    "id in ({$taskInfo['task_type']}) and status=1 and isdelete=0 ",
                    'id,type_name,type_describe',
                    '',
                    ''
                )->items;

                if ($taskTypeLists){
                    $taskInfo['task_type_name'] = $taskTypeLists[0]['type_name'];
                    $taskInfo['task_type_describe'] = $taskTypeLists[0]['type_describe'];
                }


                foreach ($conditionList as &$item) {
                    foreach ($rewardConditionLists as $cc){
                        if ($item['condition_id']==$cc['condition_id']){
                            switch ($item['condition_type']){
                                case 1:
                                    $item['condition_type'] = "基础条件";
                                    $item['condition_typeid'] = 1;
                                    break;
                                case 2:
                                    $item['condition_type'] = "进阶条件";
                                    $item['condition_typeid'] = 2;
                                    break;
                                default:
                                    $item['condition_type'] = "其他条件";
                                    $item['condition_typeid'] = 0;
                                    break;
                            }
                            switch ($cc['reward_type']){
                                case 1:
                                    $item['reward'] = "奖励".$cc['reward_parm']."干币";
                                    break;
                                case 2:
                                    $item['reward'] = "奖励".$cc['reward_parm']."学分";
                                    break;
                                case 3:
                                    $item['reward'] = "奖励".$cc['reward_describe'];
                                    break;
                                default:
                                    $item['reward'] = "无奖励";
                                    break;
                            }
                        }

                    }
                }
                //组装条件数组
                $conditionLists = [];
                foreach ($conditionList as $k=>$cLi){
                    $conditionLists[$k]['条件名称'] = $cLi['condition_name'];
                    $conditionLists[$k]['条件描述'] = $cLi['condition_describe'];
                    $conditionLists[$k]['条件类型'] = $cLi['condition_type'];
                    $conditionLists[$k]['条件奖励'] = $cLi['reward'];
                }
                $taskInfo['condition_list'] = json_encode($conditionLists);
                $result->data[] = $taskInfo;
                $result->code = 1;
            } else {
                $result->code = 0;
            }
//            $result->message = json_encode($conditionList);
            $result->success = true;
            return $result;
        }catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 根据身份ID 及其其他参数获取任务列表
     * @param TaskUserDTO $taskUser
     * @return ResultDO
     */
    public function getTaskList(TaskUserDTO $taskUser){

        $result = new ResultDO();

        $identity_id = $taskUser->identity_id ? gdl_lib_BaseUtils::getStr($taskUser->identity_id,'int') : 0;
        $is_complete = $taskUser->is_complete ? gdl_lib_BaseUtils::getStr($taskUser->is_complete) :0;//是否完成
        $adminreg = $taskUser->adminreg ? gdl_lib_BaseUtils::getStr($taskUser->adminreg) :'';//
        $lose_efficacy = $taskUser->lose_efficacy ? gdl_lib_BaseUtils::getStr($taskUser->lose_efficacy,'int') : 0;//是否失效
        $page = $taskUser->page ? gdl_lib_BaseUtils::getStr($taskUser->page) : 1;
        $center = $taskUser->center ? gdl_lib_BaseUtils::getStr($taskUser->center,'int') : 0;
        if (!$identity_id) {
            $result->success = false;
            $result->code = 0;
            $result->message = '缺少参数ID';
            return $result;
        }
        $taskModel = new model_newexam_task();
        $taskUserModel = new model_newexam_taskuser();
        $taskTypeModel = new model_newexam_tasktype();
        $conditionRelationModel = new model_newexam_taskconditionrelation();
        $base_data = [];
        if ($center==1){
            //获取该平台下的所有任务
            try{
                $time = time();
                $taskInfo = [];
                //获取任务基本信息 只获取基本任务
                $condition = " ((task_end_time>$time and ex_task.task_type not in (1,4)) OR ex_task.task_type in (1,4)) AND ex_task.status = 1 AND ex_task.admin_reg in ('$adminreg') ";
                $condition .= " group by ex_task.id order by ex_task.is_stick desc,ex_task.id desc";
                $startCount=($page-1)*5; ///////////////////////分页开始
                $limit="$startCount,5"; //
                $condition .= ' limit '.$limit;
                $field =  'ex_task_condition_relation.condition_id,ex_task.id task_id,ex_task.task_type,ex_task.task_name,ex_task.task_describe,ex_task.task_start_time,ex_task.task_end_time,ex_task.is_stick,count(distinct(ex_task_condition_relation.condition_id)) as condition_num';
                $results1 = $taskModel->select(
                    $condition,
                    $field,
                    '',
                    '',
                    array("ex_user_company" => "ex_user_company.admin_reg = ex_task.admin_reg",
                         "ex_task_condition_relation" => "ex_task_condition_relation.task_id = ex_task.id"
                    )
                )->items;

                $conditionids = [];
                $task_typeids = [];
                foreach ($results1 as $cv){
                    $conditionids[] = $cv['condition_id'];
                    $task_typeids[] = $cv['task_type'];
                }
                if (!empty($conditionids)) {
                	$conditionids = array_unique($conditionids);
                	$conditionids = implode(',',$conditionids);
                	$conditionids = trim($conditionids,',');
                }
               
                if (!empty($task_typeids)) {
                	$task_typeids = array_unique($task_typeids);
                	$task_typeids = implode(',',$task_typeids);
                	$task_typeids = trim($task_typeids,',');
                }

                //获取每个条件的奖励信息
                $rewardConditionLists = $conditionRelationModel->select(
                    "ex_task_condition_relation.condition_id in ({$conditionids}) ",
                    'ex_task_reward.reward_type,ex_task_reward.reward_parm,ex_task_reward.reward_describe,ex_task_condition_reward_relation.condition_id',
                    '',
                    '',
                    array("ex_task_condition_reward_relation" => "ex_task_condition_relation.condition_id = ex_task_condition_reward_relation.condition_id",
                        "ex_task_reward" => "ex_task_reward.id = ex_task_condition_reward_relation.reward_id"
                    )
                )->items;

                //获取每个任务的类型信息
                $taskTypeLists = $taskTypeModel->select(
                    "id in ({$task_typeids}) and status=1 and isdelete=0 ",
                    'id,type_name,type_describe',
                    '',
                    ''
                )->items;

                foreach ($results1 as &$item) {
                    foreach ($rewardConditionLists as $cc){
                        if ($item['condition_id']==$cc['condition_id']){
                            switch ($cc['reward_type']){
                                case 1:
                                    $item['reward'] = "奖励".$cc['reward_parm']."干币";
                                    break;
                                case 2:
                                    $item['reward'] = "奖励".$cc['reward_parm']."学分";
                                    break;
                                case 3:
                                    $item['reward'] = "奖励".$cc['reward_describe'];
                                    break;
                                default:
                                    $item['reward'] = "奖励".$cc['reward_describe'];
                                    break;
                            }
                        }
                    }
                    foreach ($taskTypeLists as $ttl){
                        if ($item['task_type']==$ttl['id']){
                            $item['task_type_name'] = $ttl['type_name'];
                            $item['task_type_describe'] = $ttl['type_describe'];
                        }
                    }
                }

                if ($results1){
                    //根据身份ID获取 人员已经领取的任务
                    $resu = $taskUserModel->select(
                        " ex_task_user.identity_id = {$identity_id} group by ex_task_user.task_id order by ex_task_user.task_id desc",
                        'ex_task_user.task_id,task_completeness,is_complete',
                        '',
                        ''
                    )->items;

                    foreach ($results1 as $kr=>&$vs){//全部任务
                        foreach ($resu as $r){//已经领取的任务
                            if ($vs['task_id']==$r['task_id']){
                                $vs['is_get_task'] = 1;
                                $vs['task_completeness'] = $r['task_completeness'];
                                if ($r['is_complete']==1){
                                    unset($results1[$kr]);
                                }
                                $vs['is_complete'] = $r['is_complete'];
                            }
                        }
                        if ($vs['is_get_task']!=1){
                            $vs['is_get_task'] = 0;
                            $vs['task_completeness'] = 0;
                            $vs['is_complete'] = 0;
                        }
                    }

                    if ($results1) {
                    	foreach ($results1 as $key => $value) {
                    		$base_data[] = $value;
                    	}
                    }

                    $result->code = 1;
                }else{
                    $result->code = 0;
                }
                $result->data = $base_data;
              // $result->message = json_encode($conditionRelationModel);
                $result->success = true;
                return $result;
            }catch (Exception $e) {
                $result->success = false;
                $result->code = $e->getCode();
                $result->message = $e->getMessage();
            }
        }
        else{
            //已经领取的任务列表
            try{
                $time = time();
                $taskInfo = [];
                //获取任务基本信息 ex_task ex_task_user ex_task_condition_relation
                $condition = " ex_task_user.identity_id='{$identity_id}' ";
                if (is_numeric($is_complete) && $is_complete!=3)
                {
                    if ($is_complete==1){//已完成
                        $condition.=" AND ex_task_user.is_complete = '{$is_complete}' AND ((task_end_time>$time and ex_task.task_type not in(1,4)) OR ex_task.task_type in (1,4)) AND ex_task.status = 1";//1 完成的任务 0未完成的任务
                    }elseif($is_complete==0){//未完成
                        $condition.=" AND ex_task_user.is_complete = '{$is_complete}' AND ((task_end_time>$time and ex_task.task_type not in(1,4)) OR ex_task.task_type in (1,4)) AND ex_task.status = 1";//1 完成的任务 0未完成的任务
                    }
                }

                if (is_numeric($lose_efficacy) && $lose_efficacy!=3) $condition .= " AND (task_end_time<$time OR ex_task.status = 0) ";//失效任务
                $condition .= " group by ex_task.id order by ex_task.id desc";
                $startCount=($page-1)*5; ///////////////////////分页开始
                $limit="$startCount,5"; //
                $condition .= ' limit '.$limit;
                $field =  'ex_task.task_classify,ex_task_classify_url_relation.url,ex_task.id task_id,ex_task.task_name,ex_task.task_type,ex_task.task_describe,ex_task.task_start_time,ex_task.task_end_time,ex_task_user.task_completeness,ex_task_user.is_complete';
                $res = $taskUserModel->select(
                    $condition,
                    $field,
                    '',
                    '',
                    array("ex_task" => "ex_task.id = ex_task_user.task_id",
                        "ex_task_classify_url_relation" => "ex_task_classify_url_relation.classify_id = ex_task.task_classify",
                        "ex_user_company" => "ex_user_company.admin_reg = ex_task.admin_reg",

                    )
                )->items;

                if ($res){
                    $datas = [];
                    //根据任务ID获取 任务奖励
                    foreach ($res as $value){
                        $datas[] = $value;
                        $task_ids[]=$value['task_id'];
                        $task_typeids[]=$value['task_type'];
                    }
                    $task_ids = implode(',',$task_ids);
                    $task_typeids = implode(',',$task_typeids);
                    $resu = $conditionRelationModel->select(
                        " task_id in ({$task_ids})  group by ex_task_condition_relation.task_id order by ex_task_condition_relation.task_id desc",
                        'ex_task_condition_relation.task_id,ex_task_reward.reward_type,ex_task_reward.reward_describe,reward_parm,count(ex_task_condition_relation.task_id) as condition_num',
                        '',
                        '',
                        array("ex_task_condition_reward_relation" => "ex_task_condition_reward_relation.condition_id = ex_task_condition_relation.condition_id",
                            "ex_task_reward" => "ex_task_reward.id = ex_task_condition_reward_relation.reward_id",
                        )
                    )->items;

                    //获取每个任务的类型信息
                    $taskTypeLists = $taskTypeModel->select(
                        "id in ({$task_typeids}) and status=1 and isdelete=0 ",
                        'id,type_name,type_describe',
                        '',
                        ''
                    )->items;
                    foreach ($datas as &$vs){
                        foreach ($resu as $r){
                            if ($vs['task_id']==$r['task_id']){
                                $vs['condition_num'] = $r['condition_num'];
                                switch ($r['reward_type']){
                                    case 1:
                                        $vs['reward'] = "奖励".$r['reward_parm']."干币";
                                        break;
                                    case 2:
                                        $vs['reward'] = "奖励".$r['reward_parm']."学分";
                                        break;
                                    case 3:
                                        $vs['reward'] = "奖励".$r['reward_describe'];
                                        break;
                                    default:
                                        $vs['reward'] = "奖励".$r['reward_describe'];
                                        break;
                                }
                            }
                        }
                        foreach ($taskTypeLists as $ttl){
                            if ($vs['task_type']==$ttl['id']){
                                $vs['task_type_name'] = $ttl['type_name'];
                                $vs['task_type_describe'] = $ttl['type_describe'];
                            }
                        }
                    }
                    foreach ($datas as &$vs){//全部任务
                        foreach ($res as $r){//已经领取的任务
                            if ($vs['task_id']==$r['task_id']){
                                $vs['is_get_task'] = 1;
                                $vs['task_completeness'] = $r['task_completeness'];
                                $vs['is_complete'] = $r['is_complete'];
                            }

                        }
                    }
                    $result->code = 1;
                }else{
                    $result->code = 0;
                }
                $result->data = $datas;
//                $result->message = json_encode($datas);
                $result->success = true;
                return $result;
            }catch (Exception $e) {
                $result->success = false;
                $result->code = $e->getCode();
                $result->message = $e->getMessage();
            }

        }

        return $result;
    }

    /**
     * 领取任务
     * @param TaskUserDTO $taskUser
     */
    public function receiveTask(TaskUserDTO $taskUser){
        $result = new ResultDO();
        $task_id     = $taskUser->task_id ? gdl_lib_BaseUtils::getStr($taskUser->task_id,'int') : 0;
        $identity_id = $taskUser->identity_id ? gdl_lib_BaseUtils::getStr($taskUser->identity_id,'int') : 0;
        $task_type = $taskUser->task_type ? gdl_lib_BaseUtils::getStr($taskUser->task_type,'int') : 0;
        $taskUserModel = new model_newexam_taskuser();
        try{
            $tUser = $taskUserModel->selectOne(" identity_id = {$identity_id} AND task_id = {$task_id} and status=1");
            if ($tUser){
                $result->code = 0;
                $result->message = '任务领取失败,请勿重复领取';
            }else{
                $userTaskData = [
                    'identity_id'=>$identity_id,
                    'task_id'=>$task_id,
                    'task_type'=>$task_type,
                    'task_completeness'=>0,
                    'is_complete'=>0,
                    'status'=>1,
                    'create_time'=>time(),
                ];
                $res = $taskUserModel->insert($userTaskData);
                if ($res){
                    $result->code = 1;
                    $result->message = '任务领取成功';
                }else{
                    $result->code = 0;
                    $result->message = '任务领取失败';
                }
            }
            $result->success = true;
        }catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 检测用户是否存在任务 并且 提交任务
     */
    public function checkUserTaskAndPutTask(TaskUserDTO $taskUser){
        $result = new ResultDO();
        $identity_id = $taskUser->identity_id ? gdl_lib_BaseUtils::getStr($taskUser->identity_id,'int') : 0;
        $task_classify = $taskUser->task_classify ? gdl_lib_BaseUtils::getStr($taskUser->task_classify,'int') : 0;//试卷分类
        $put_data = $taskUser->data ? gdl_lib_BaseUtils::reMoveXss($taskUser->data) : '';
        if (!$identity_id) {
            $result->success = false;
            $result->code = 0;
            $result->message = '缺少参数ID';
            return $result;
        }
        try{
            $taskUserModel = new model_newexam_taskuser();
//            $serviceRule = new service_rule();
            $taskModel = new model_newexam_task();
            $taskConditionRuleModelRelationModel = new model_newexam_taskconditionrulemodelrelation();
            $taskConditionRelationModel = new model_newexam_taskconditionrelation();//任务条件关联
            $taskInfo = [];
            $time = time();//当前时间戳
            $zero_time = strtotime(date('Y-m-d'));//凌晨时间戳
            //查询用户操作的行为是否有 有效任务
            $taskIdentityList = $taskUserModel->select(
                " ex_task_user.identity_id in ({$identity_id}) and ex_task.status=1 and task_classify={$task_classify}",
                'ex_task_user.task_id,ex_task_user.update_time,ex_task_user.is_complete,ex_task.task_type,task_start_time,task_end_time,ex_task_condition_relation.condition_id,ex_task_condition_relation.condition_type',
                '',
                'order by ex_task.task_type',
                array("ex_task" => "ex_task.id = ex_task_user.task_id and ex_task.status=1 and ex_task.task_classify={$task_classify}",
                    "ex_task_condition_relation" => "ex_task_condition_relation.task_id = ex_task.id ",
                )
            )->items;
//            $result->message = json_encode($taskUserModel);
//            return $result;
            if ($taskIdentityList){
//                $taskIdentityList = array_column($taskIdentityList,'task_id');
//                $taskIdentityList = implode(',',$taskIdentityList);
                $put_data = unserialize($put_data);
                    $taskTypeList = [];

                    foreach ($taskIdentityList as $vak){
                        $taskTypeList[$vak['task_type']][] = $vak;
                    }

                    //根据任务类型去完成任务
                    $type_keys = array_keys($taskTypeList);
                //            $result->message = json_encode($taskIdentityList);
//            return $result;
                     foreach ($type_keys as $io){
                         switch ($io){
                             case 1;//日常任务
                                //获取用户 今天是否完成任务
                                $task_list_info = $taskTypeList[1];
                                //删除今天已经完成的日常任务 start
                                $yes_task_list = [];//有效任务集合
                                foreach ($task_list_info as $kt=>$ccval){
                                    if ($ccval['update_time'] < $zero_time){//如果是今天没有做过
                                        $yes_task_list[$kt] = $ccval;
                                    }else{//今天已经做过任务
                                        if ($ccval['is_complete']==0){//还未完成
                                            $yes_task_list[$kt] = $ccval;
                                        }
                                    }
                                }
//                                 $result->message = json_encode($yes_task_list);
//                                 return $result;
                                 //删除今天已经完成的日常任务 end
                                $cc = $this->dealTasks($yes_task_list,$put_data,$identity_id,$task_classify);
                                if ($cc==false){
                                    break;
                                }
//
//
//                                 $result->message = json_encode($finish_task_ids);
//                                 return $result;
                                 break;
                             case 2;//限时任务

                                 //获取限时任务
                                 $task_list_info = $taskTypeList[2];
                                 //判断完成时间是否在限制时间之内
                                 $yes_task_list = [];//有效任务集合
                                 foreach ($task_list_info as $kt=>$ccval){

                                     if (($ccval['task_start_time'] < $time) && ($ccval['task_end_time'] > $time) && $ccval['is_complete']==0){//如果在规定时间
                                         $yes_task_list[$kt] = $ccval;
                                     }
                                 }
                                 //删除今天已经完成的限时任务 end
                                 $cc = $this->dealTasks($yes_task_list,$put_data,$identity_id,$task_classify);
                                 if ($cc==false){
                                     break;
                                 }
                                 break;
                             case 3;//活动任务
                                //获取活动任务
                                 $task_list_info = $taskTypeList[3];
                                 //判断完成时间是否在限制时间之内
                                 $yes_task_list = [];//有效任务集合
                                 foreach ($task_list_info as $kt=>$ccval){

                                     if (($ccval['task_start_time'] < $time) && ($ccval['task_end_time'] > $time) && $ccval['is_complete']==0){//如果在规定时间
                                         $yes_task_list[$kt] = $ccval;
                                     }
                                 }
                                 //删除今天已经完成的活动任务 end
                                 $cc = $this->dealTasks($yes_task_list,$put_data,$identity_id,$task_classify);
                                 if ($cc==false){
                                     break;
                                 }
                                 break;
                             case 4;//必做任务
                                //获取必做任务
                                 $task_list_info = $taskTypeList[4];
                                 //判断完成时间是否在限制时间之内
                                 $yes_task_list = [];//有效任务集合
                                 foreach ($task_list_info as $kt=>$ccval){
                                     if ($ccval['is_complete']==0){//如果在规定时间
                                         $yes_task_list[$kt] = $ccval;
                                     }
                                 }
                                 //删除今天已经完成的必做任务 end
                                 $cc = $this->dealTasks($yes_task_list,$put_data,$identity_id,$task_classify);
                                 if ($cc==false){
                                     break;
                                 }
                                 break;
                         }
                     }
                     //end 0811代码结束 0 0814代码结束
                $result->success = true;
                $result->code = 1;
            }else{
//                $result->message = '任务不存在';
                $result->code = 0;
            }
            $result->success = true;
        }catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

    /**
     * 根据有效任务列表 比对用户是否完成任务
     * @param $task_array 、有效任务列表
     * @param $put_data 、 用户上传数据
     * @param $identity_id 、
     * @return bool
     */
    public static function dealTasks($task_array,$put_data,$identity_id,$type=1){
        $taskUserModel = new model_newexam_taskuser();
        $yes_task_list = $task_array;
        $condition_ids = array_column($yes_task_list,'condition_id');

        //根据任务ID 获取任务是否完成

        //获取规则模块 并判断是否完成
        $serviceRule = new service_rule();
        $res_rule    = $serviceRule->getRuleModelByConditionId($condition_ids);
        $a           = $serviceRule->deModelRule($res_rule,$put_data,$type);

        $task_finish_base_list = [];
        //判断基础条件是否完成 任务是否通过  start-------------
        foreach ($yes_task_list as $key=>$value){
            $condition_id = $value['condition_id'];
            if ($value['condition_type']==1){//判断任务基础条件是否完成
                if ($a[$condition_id]==1){//任务完成
                    $task_finish_base_list[$value['task_id']] = $condition_id;
                }
            }
        }
        //根据条件ID 发放奖励
        if (empty($task_finish_base_list)){//如果没有完成的任务判断其他任务类型
            return false;
        }
        //改变用户完成状态 根据条件ID 获取奖励
        $finish_task_ids  = array_keys($task_finish_base_list);
        $finish_task_ids = implode(',',$finish_task_ids);

        $finish_task_conditionids  = array_values($task_finish_base_list);
        $serviceReward = new service_reward();
        $serviceUserscores = new service_userscores();

        $data = [
            'task_completeness'=>1,
            'is_complete'=>1,
            'update_time'=>time(),
        ];
        $taskUserModel->update(" identity_id in ({$identity_id}) and task_id in ($finish_task_ids)",$data);
        //根据条件ID 发放奖励
        $aReward = $serviceReward->getRewardByConditionId($finish_task_conditionids);
        if (!empty($aReward)){
            foreach ($aReward as $aval){
                switch ($aval['reward_type']){
                    case 1://干币
                        break;
                    case 2://学分
                        $serviceUserscores->setUserScoresIdentityId($identity_id,$aval['reward_parm']);
                        break;
                    case 3://实物
                        break;
                }
            }
        }
        //判断基础条件是否完成 任务是否通过  end-------------------

        //判断进阶条件是否完成 任务是否通过  start-------------------
        $task_finish_list = [];
        //判断进阶条件是否完成
        foreach ($yes_task_list as $keys=>$values){
            $condition_id = $values['condition_id'];
            if ($values['condition_type']==2){//判断任务进阶条件是否完成
                if ($a[$condition_id]==1 && $task_finish_base_list[$values['task_id']]==1){//完成基础条件 并且进阶条件完成
                    $task_finish_list[$values['task_id']] = $condition_id;
                }
            }
        }

        $finish_task_ids  = array_keys($task_finish_list);
        $finish_task_ids = implode(',',$finish_task_ids);
        $finish_task_conditionids  = array_values($task_finish_list);
        $data = [
            'task_completeness'=>2,
            'is_complete'=>1,
            'update_time'=>time(),
        ];
        $taskUserModel->update(" identity_id in ({$identity_id}) and task_id in ($finish_task_ids)",$data);
        //根据条件ID 发放奖励
        $aReward = $serviceReward->getRewardByConditionId($finish_task_conditionids);
        if (!empty($aReward)){
            foreach ($aReward as $aval){
                switch ($aval['reward_type']){
                    case 1://干币
                        break;
                    case 2://学分
                        $serviceUserscores->setUserScoresIdentityId($identity_id,$aval['reward_parm']);
                        break;
                    case 3://实物
                        break;
                }
            }
        }
        //判断进阶条件是否完成 任务是否通过  end-------------------
        return true;
    }
}
