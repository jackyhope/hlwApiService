<?php

use com\hlw\ks\interfaces\AdminroleServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\adminrole\AdminroleDTO;

date_default_timezone_set('PRC');

class api_AdminroleService extends api_Abstract implements AdminroleServiceIf {

    /**
     * 行为列表
     * @param AdminroleDTO $adminrole
     * @return ResultDO
     */
    public function behaviorlog(AdminroleDTO $adminrole) {
        $result = new ResultDO();

        $id = $adminrole->id ? gdl_lib_BaseUtils::getStr($adminrole->id, 'int') : 0;
        $field = $adminrole->field ? gdl_lib_BaseUtils::getStr($adminrole->field) : '*';
        $limit = $adminrole->limit ? gdl_lib_BaseUtils::getStr($adminrole->limit, 'int') : 0;
        $reg = $adminrole->reg ? gdl_lib_BaseUtils::getStr($adminrole->reg) : 0;



        try {


            $adminuser = new model_newexam_adminuser();
            $userid = $adminuser->select("reg='{$reg}'", 'id,realname')->items;

            $uid = [0];
            $username = array();
            foreach ($userid as $v) {
                $uid[] = $v['id'];
                $username[$v['id']] = $v['realname'];
            }

            $taskorders = new model_newexam_weblogall();
            $condition = "module='admin' and uid in(" . implode(',', $uid) . ") and uid>1";

            $condition .= " order by id desc";
            if ($limit) {
                $condition .= ' limit ' . $limit;
            }
            $web_log_all = $taskorders->select($condition, $field)->items;
            $tree = self::getAccessList();
            $list = array();
            foreach ($web_log_all as $vc) {

                $list[] = date('m月d日 H:i', $vc['create_at']) . '，' . $username[$vc['uid']] . "&nbsp;<span class='record-wz'>" . $tree[1][$vc['controller']]['title'] . $tree[$tree[1][$vc['controller']]['id']][$vc['action']]['title'] . '</span>';
            }

            $result->data[] = $list;
            #$result->data[][] = $limit;
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

    public function roomproject(AdminroleDTO $adminrole)
    {
        $result = new ResultDO();
        $result->code = 0;
        $result->success = true;
        return $result;
    }

    /*     * **临时*** */

    public function userlist(AdminroleDTO $adminrole) {
        $result = new ResultDO();
        try {
            $modeluser = new model_newexam_user();
            $name = gdl_lib_BaseUtils::getStr($adminrole->name, 'string');
            $field = $adminrole->field ? $adminrole->field : '*';

            $res = $modeluser->select("username='{$name}' or idcard='{$name}'", $field)->items;


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

    /*     * **临时end*** */



    /*     * ******内部调用****** */

    /**
     * 获取所有节点关系
     * @param $role_id
     * @return array
     */
    public function getAccessList() {

        $adminnode = new model_newexam_adminnode();
        $node = $adminnode->select("status=1 and isdelete=0", "id,pid,group_id,name,title,level,type")->items;

        $node_tree = [];
        foreach ($node as $v) {
            $node_tree[$v['pid']][$v['name']] = [
                "id" => $v['id'],
                "pid" => $v['pid'],
                "title" => $v['title'],
                'name' => $v['name'],
            ];
        }

        $node_tree[1]['Pub'] = ['id' => '1a', 'pid' => '1', 'title' => '', 'name' => 'Pub'];
        $node_tree[1]['Upload'] = ['id' => '2a', 'pid' => '1', 'title' => '上传', 'name' => 'Upload'];
        $node_tree[1]['PapersInfo'] = ['id' => '3a', 'pid' => '1', 'title' => '', 'name' => 'PapersInfo'];
        $node_tree[1]['EventBack'] = ['id' => '4a', 'pid' => '1', 'title' => '下达任务', 'name' => 'EventBack'];
        $node_tree[1]['EventTask'] = ['id' => '5a', 'pid' => '1', 'title' => '查看用户反馈', 'name' => 'EventTask'];

        $node_tree['1a'] = [
            'index' => ['id' => '1a', 'pid' => '1a', 'title' => '首页', 'name' => 'index'],
            'logout' => ['id' => '1a', 'pid' => '1a', 'title' => '退出登陆', 'name' => 'logout'],
            'checklogin' => ['id' => '1a', 'pid' => '1a', 'title' => '登陆', 'name' => 'checkLogin'],
            'password' => ['id' => '1a', 'pid' => '1a', 'title' => '修改密码', 'name' => 'password'],
            'profile' => ['id' => '1a', 'pid' => '1a', 'title' => '查看用户信息', 'name' => 'profile'],
            'login' => ['id' => '1a', 'pid' => '1a', 'title' => '登陆首页', 'name' => 'login'],
        ];
        $node_tree['2a'] = [
            'index' => ['id' => '1a', 'pid' => '1a', 'title' => '首页', 'name' => 'index'],
            'excel' => ['id' => '1a', 'pid' => '1a', 'title' => 'excel', 'name' => 'excel'],
            'upload' => ['id' => '1a', 'pid' => '1a', 'title' => '文件', 'name' => 'upload'],
            'uploadexcel' => ['id' => '1a', 'pid' => '1a', 'title' => '题目导入', 'name' => 'uploadexcel'],
            'uploadfile' => ['id' => '1a', 'pid' => '1a', 'title' => '文件', 'name' => 'uploadfile'],
        ];
        $node_tree['3a'] = [
            'index' => ['id' => '1a', 'pid' => '1a', 'title' => '首页', 'name' => 'index'],
            'exports' => ['id' => '1a', 'pid' => '1a', 'title' => '导出excel', 'name' => 'exports'],
            'edit' => ['id' => '1a', 'pid' => '1a', 'title' => '编辑', 'name' => 'edit'],
            'monitor' => ['id' => '1a', 'pid' => '1a', 'title' => '查看监控页面', 'name' => 'monitor'],
        ];

        $node_tree['4a'] = [
            'index' => ['id' => '1a', 'pid' => '1a', 'title' => '首页', 'name' => 'index'],
            'details' => ['id' => '1a', 'pid' => '1a', 'title' => '详情', 'name' => 'details'],
            'edit' => ['id' => '1a', 'pid' => '1a', 'title' => '-编辑', 'name' => 'edit'],
        ];
        $node_tree['6a'] = [
            'index' => ['id' => '1a', 'pid' => '1a', 'title' => '', 'name' => 'index'],
            'lookUser' => ['id' => '1a', 'pid' => '1a', 'title' => '查看人员', 'name' => 'lookUser'],
            'addUser' => ['id' => '1a', 'pid' => '1a', 'title' => '人员导入界面', 'name' => 'addUser'],
        ];

        //生成树

        return $node_tree;
    }

}
