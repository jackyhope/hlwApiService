<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-04
 * Time: 14:29
 */

use \com\hlw\gg\interfaces\TestTodaystudyServiceIf;
class api_TestTodaystudyService extends api_Abstract implements TestTodaystudyServiceIf
{
    public function getDemo($param1)
    {
        // TODO: Implement getDemo() method.
        return 'TestTodaystudy的方法getDemo-----'.$param1;
    }

    public function getFine($param2)
    {
        // TODO: Implement getFine() method.
        return 'TestTodaystudy的方法getFine-----'.$param2;
    }
}