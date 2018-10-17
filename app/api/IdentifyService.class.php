<?php

use com\hlw\ks\interfaces\IdentifyServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\identify\IdentifyDTO;

class api_IdentifyService extends api_Abstract implements IdentifyServiceIf
{

 
	/*
	*identify 人脸识别
	*/
    public function testaaa(IdentifyDTO $identify)
    {
        $result = new ResultDO();
        try {
			$res = array();
			$res = ['name'=>$identify->name,'pwd'=>$identify->id];
			
            $result->data[0] = $res;
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

