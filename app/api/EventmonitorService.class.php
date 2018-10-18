<?php

use com\hlw\ks\interfaces\EventmonitorServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\eventmonitor\EventprojectDTO;


class api_EventmonitorService extends api_Abstract implements EventmonitorServiceIf
{

   

    /**
     * ����� ��Ƶͷͼ 
     * @param EngprojectRequestDTO $engprojectDo
     * @return ResultDO
     */
    public function eventlist(EventprojectDTO $eventmonitor)
    {
        $result = new ResultDO();
        try {
            $eventuser = new model_newexam_eventmonitor();
            $eventid = $eventmonitor->eventid ? (int)$eventmonitor->eventid : 0;
			$pages = $eventmonitor->pages ? (int)$eventmonitor->pages : 1;
			$offage = $eventmonitor->lim ? (int)$eventmonitor->lim : 3;
			$page = ($pages-1)*$offage;

            $res = @$eventuser->select('eventid = ' . $eventid . ' limit '.$page.','.$offage, 'id,chan_name,chan_address,datime,header_img')->items;
			
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
	* ��ȡ��Ƶ����
	* @param $vid ��Ƶid
	*/
	public function details($vid){
		$result = new ResultDO();

		try{
				$evendb = new model_newexam_eventmonitor();
				$getvid = $vid? (int)$vid : 0;

				$res = @$evendb->select('id = ' . $getvid , 'id,chan_name,chan_address,datime,header_img')->items; 
	            $result->data = $res;
				if($res){
					$result->code = 1;
				}else{
					$result->code = 0;
				}
				$result->success = true;  
		} catch(Exception $exp){
			$result->success = false;
			$result->code = $exp->getCode();
			$result->message = $exp->getMessage();
		}
		
		return $result;

	}
	/**
	* �˹������б��Ƿ�Υ�����
	*/

   

}
