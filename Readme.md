PHPFetion5 by fisker
base on php-fetion


（一）使用说明
1. 需要包含进你的程序的文件只有一个：PHPFetion5.php。如： 
require 'PHPFetion5.php';
2. 调用方法如： 
$fetion = new PHPFetion5('13500001111', '123123'); // 手机号、飞信密码
$fetion->send('13500001111', 'Hello Fetion!'); // 接收人手机号、飞信内容

（二）实现原理
1. 用PHP发送HTTP请求模拟登录WAP版的飞信，并模拟发送飞信。实现原理可查看：http://blog.quanhz.com/archives/118 


（三）其他
1. wap飞信登录地址：http://f.10086.cn 
