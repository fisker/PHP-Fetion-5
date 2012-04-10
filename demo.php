<?php
date_default_timezone_set("Asia/Shanghai");
mb_internal_encoding("UTF-8");

require_once( 'PHPFetion5.php' );

//方法1
$fetion = new PHPFetion5( FETION_USER, FETION_PWD );
//$fetion -> login(); send方法会自动调用
$s = $fetion->send( '13888888888', '$msg' );	// 接收人手机号、飞信内容
$fetion -> logout();

//方法2
$fetion2 = new PHPFetion5( FETION_USER, FETION_PWD );
$fetion -> login();
$cookie = $fetion -> cookie();
**savecookie( $cookie ); //自行编写
$s = $fetion->send( '13888888888', '$msg' );

//已有cookie
$fetion2 = new PHPFetion5( FETION_USER, FETION_PWD );
$cookie = **readcookie(); //自行编写
$fetion -> cookie( $cookie );
$s = $fetion->send( '13888888888', '$msg' );