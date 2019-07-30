<?php
define('PASSWORD_KEY', 'hlwPASSWORD');

define('hlw_SYSUSERKEY', 'localhostAddfdfasdf234249AasdfsfFG!@$%%^^&%#!##$%^#@@#@sdfdfdG');

define('APPID', '53c8e7a69a682');
define('SECRET', 'e738617d618b5c42a771e077312df248');
define('CENV', 'dev');
if (strpos($_SERVER['SERVER_ADDR'], '192.168') !== FALSE || strpos($_SERVER['SERVER_ADDR'], '127.0') !== FALSE) {
    define('OA_ROLE', 88);
} else {
    define('OA_ROLE', 1);
}
