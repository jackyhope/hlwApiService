<?php

use com\hlw\ks\interfaces\PracticeServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\practice\PracticeDTO;
use com\hlw\ks\dataobject\practice\QbankDTO;

class api_PracticeService extends api_Abstract implements PracticeServiceIf 
{

    public function practice(QbankDTO $userDo)
    {
        $result = new ResultDO();
        try {
            $id = $userDo->id ? hlw_lib_BaseUtils::getStr($userDo->id, 'int') : '';
            $isdelete = $userDo->admin_reg ? hlw_lib_BaseUtils::getStr($userDo->isdelete, 'int') : '0';
            $status = $userDo->admin_reg ? hlw_lib_BaseUtils::getStr($userDo->status, 'int') : '1';
            $admin_reg = $userDo->admin_reg ? hlw_lib_BaseUtils::getStr($userDo->admin_reg, 'string') : '';
			$offset = $userDo->offset ? hlw_lib_BaseUtils::getStr($userDo->offset, 'int') : 0;
			$num = $userDo->num ? hlw_lib_BaseUtils::getStr($userDo->num, 'int') : 10;
			$page = $offset*$num;
            $questiondb = new model_newexam_qbank();
            if (!empty($admin_reg)){
                $admin_reg = explode(',',$admin_reg);
               foreach ($admin_reg as &$value){
                   $value = "'".$value."'";
               }
                $admin_reg = implode(',',$admin_reg);
            }
            $where = array(
                'id'=>$id,
                'isdelete'=>$isdelete,
                'status'=>$status,
                "admin_reg in ({$admin_reg})",
            );
            if($id){
                unset($where[0]);
            }else{
                unset($where['id']);
            }
            
            $res = $questiondb->select($where ,'*', '', 'order by id desc limit '.$page.','.$num)->items;
            if ($res) {
                $result->code = 1;
                $result->data = $res;
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
        $result->notify_time = time();
        return $result;
    }

    public function knows(PracticeDTO $userDo) 
    {
        $result = new ResultDO();
        try {
            $questiondb = new model_newexam_knows();
            $res = $questiondb->select($userDo->where)->items;
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
        $result->notify_time = time();
        return $result;
    }

    public function questype(PracticeDTO $userDo)
    {
        $result = new ResultDO();
        try {
            $questype = new model_newexam_questype();
            $res = $questype->select($userDo->where)->items;
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
        $result->notify_time = time();
        return $result;
    }

    public function sections(PracticeDTO $userDo) 
    {
        $result = new ResultDO();
        try {
            $sections = new model_newexam_sections();
            $res = $sections->select($userDo->where)->items;
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
        $result->notify_time = time();
        return $result;
    }

    public function contrast(PracticeDTO $userDo)
    {
        $result = new ResultDO();
        try {
            $contrast = new model_newexam_contrast();
            if ($userDo->insert) {
                $data = unserialize($userDo->insert);
                $res = $contrast->insert($data['data'])->items;
            }
            if ($userDo->where) {
                $res = $contrast->select($userDo->where)->items;
            }
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

    public function contrast_practice(PracticeDTO $userDo)
    {
        $result = new ResultDO();
        try {
            $practice = new model_newexam_contrastpractice();
            if ($userDo->insert) {
                $data = unserialize($userDo->insert);
                $res = $practice->insert($data['data'])->items;
            }
            if ($userDo->where) {
                $res = $practice->select($userDo->where, '', '', 'order by id asc')->items;
            }
            if ($userDo->update) {
                $data = unserialize($userDo->update);
                $res = $practice->update($userDo->where, $data['data'])->items;
            }
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
        $result->notify_time = time();
        return $result;
    }

    public function collection(PracticeDTO $userDo) 
    {
        $result = new ResultDO();
        try {
            $practice = new model_newexam_collection();
            if ($userDo->insert) {
                $data = unserialize($userDo->insert);
                $res = $practice->insert($data['data'])->items;
            }
            if ($userDo->where) {
                $res = $practice->select($userDo->where)->items;
            }
            if ($userDo->update) {
                $data = unserialize($userDo->update);
                $res = $practice->update($userDo->where, $data['data'])->items;
            }
            if ($userDo->deleteid) {
                $practice->delete($userDo->deleteid);
            }
            $result->data = $res;
            if ($res) {
                $result->code = 1;
            } else {
                $result->code = 0;
            }
            $result->success = true;
            $result->message = json_encode($practice);
            return $result;
        } catch (Exception $e) {
            $result->success = false;
            $result->code = $e->getCode();
            $result->message = $e->getMessage();
        }
        return $result;
    }

}
