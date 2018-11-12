<?php
use com\hlw\ks\interfaces\TestServiceIf;
class api_TestService extends api_Abstract implements TestServiceIf
{
    public function test($string)
    {
        echo $string.'122345';
    }
}