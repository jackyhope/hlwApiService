<?php

class api_Abstract
{
    protected $_time = null;
            
    function __construct()
    {
        $this->_time = time();
        $this->_ymd = date('Y-m-d', $this->_time);
    }

    /**
     * @param $msg
     * @param $code
     * @param bool $is_success
     * @param bool $data
     * @return actionResultDO
     */
    protected function actionResultObject($msg, $code, $is_success = false, $data = false, $id_list = false)
    {
        $rtn->is_success = (bool) $is_success;
        $rtn->code = $code;
        $rtn->msg = $msg;
        if ($data !== false) {
            $rtn->data = $data;
        }
        if ($id_list) {
            $rtn->id_list = $id_list;
        }
        return $rtn;
    }

}
