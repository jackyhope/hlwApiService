<?php

use com\hlw\ks\interfaces\WrongtopicServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\wrongtopic\WrongtopicDTO;

class api_WrongtopicService extends api_Abstract implements WrongtopicServiceIf
{

    public function wrongtopic(WrongtopicDTO $userDo)
    {
        $result = new ResultDO();
        try {
            $questiondb= new model_newexam_contrastpractice();
            if($userDo->where){
                $res = $questiondb->select($userDo->where)->items;
            }
            if($userDo->update){
                $data = unserialize($userDo->update);
                $questiondb->update($userDo->where,$data['data'])->items;
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
}
