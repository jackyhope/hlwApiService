<?php
define('PASSWORD_KEY', 'hlwPASSWORD');

define('hlw_SYSUSERKEY', 'localhostAddfdfasdf234249AasdfsfFG!@$%%^^&%#!##$%^#@@#@sdfdfdG');

define('APPID', '53c8e7a69a682');
define('SECRET', 'e738617d618b5c42a771e077312df248');
define('CENV', 'dev');
if (strpos($_SERVER['SERVER_ADDR'], '192.168') !== FALSE || strpos($_SERVER['SERVER_ADDR'], '127.0') !== FALSE) {
    define('OA_ROLE', 88);
} else {
    define('OA_ROLE', 1271);
}
/**2019-08-08  add **/
define('new_price',array(
        'communicate_base_position'=>300,
        'communicate_expert_position'=>500,
        'interview'=>array(
            'base'=>array(
                'price'=>1000,
                'interval'=>'0-20',
                'giving'=>0.5,
                'start_buy'=>10000
            ),
            'Intermediate'=>array(
                'price'=>1000,
                'interval'=>'20-50',
                'giving'=>0.5,
                'start_buy'=>10000
            ),
            'expert'=>array(
                'price'=>1000,
                'interval'=>'50-9999999',
                'giving'=>0.5,
                'start_buy'=>10000
            )
        ),
    )
);
/**2019-08-08  add **/