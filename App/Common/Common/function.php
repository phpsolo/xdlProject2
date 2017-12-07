<?php
/*自定义函数库*/

/**
 * 邮件发送函数
 */
function sendMail($to, $subject, $content) {
	//导入vender\PHPMailer\classphpmailer.php
	//注意：用vender函数导入的是.php的文件！！！！
	Vendor('PHPMailer.classphpmailer');
	$mail = new \PHPMailer(); /*实例化*/
	$mail->IsSMTP(); /*启用SMTP*/
	$mail->Host 		=	C('MAIL_HOST'); /*smtp服务器的名称*/

	// $mail->SMTPDebug 	=	C('MAIL_DEBUG'); /*开启调试模式，显示信息*/
	$mail->Port 		=	C('MAIL_PORT'); /*smtp服务器的端口号*/
	$mail->SMTPSecure 	=	C('MAIL_SECURE'); /*注意：要开启PHP中的openssl扩展,smtp服务器的验证方式*/

	$mail->SMTPAuth 	= 	C('MAIL_SMTPAUTH'); /*启用smtp认证*/
	$mail->Username 	= 	C('MAIL_USERNAME'); /*你的邮箱名*/
	$mail->Password 	= 	C('MAIL_PASSWORD') ; /*邮箱密码*/
	$mail->From 		= 	C('MAIL_FROM'); /*发件人地址（也就是你的邮箱地址）*/
	$mail->FromName 	= 	C('MAIL_FROMNAME'); /*发件人姓名*/
	$mail->AddAddress($to,"name");
	$mail->WordWrap 	= 	50; /*设置每行字符长度*/
	$mail->IsHTML(C('MAIL_ISHTML')); /* 是否HTML格式邮件*/
	$mail->CharSet 		=	C('MAIL_CHARSET'); /*设置邮件编码*/
	$mail->Subject 		=	$subject; /*邮件主题*/
	$mail->Body 		= 	$content; /*邮件内容*/
	$mail->AltBody 		= 	"This is the body in plain text for non-HTML mail clients"; /*邮件正文不支持HTML的备用显示*/
	if($mail->Send()) {
		return "ok";
	} else {
		return "邮件发送失败: " . $mail->ErrorInfo;
	}
}

/**
 * 该函数用于检测前台用户最近30min密码错误次数
 * $uid 用户id
 * $min 锁定时间
 * $wtime 错误次数
 */
function checkPassWrongTime($uid,$min=30,$wtime=5){
	if(empty($uid)){
		throw new \Exception('用户名不存在');
	}
	//用户登陆ip
	$ip = bindec(decbin(ip2long($_SERVER['REMOTE_ADDR'])));
	//最近时间
	$time = time();
	//最近30min之内
	$prevTime = time()-$min*60;
	$user = M('LoginInfo');
	//查询在最近30分钟之内错误状态为2的次数，次数大于等于5次则禁止登陆
	$res = $user->query("select * from shop_login_info where uid={$uid} and pass_wrong_status = 2 and UNIX_TIMESTAMP(login_time) between {$prevTime} and {$time} and login_ip = {$ip} ");
	//统计错误次数
	$wrongTime = count($res);
	//判断错误次数是否超过限制次数
	if($wrongTime >= $wtime){
		return false;
	} 
	//没有超过则返回错误次数
	return $wrongTime;
}


/**
 * 该函数用于记录前台登录错误密码输出信息
 */
function recordPassWrongTime($uid){
	//获取当前IP，转换为二进制，再转换为十进制，用于存储在数据库中
	$ip = bindec(decbin(ip2long($_SERVER['REMOTE_ADDR'])));
	//获取当前时间
	$time =  date('Y-m-d H:i:s');
	$user = M ('LoginInfo');
	//准备插入数据
	$date['uid'] = $uid;
	$date['login_ip'] = $ip;
	$date['login_time'] = $time;
	$date['pass_wrong_status'] = 2;
	//最后记录错误信息
	$user->add($date);
}
/**
 * 该函数用于记录后台管理员登录信息
 */
function recordCorrectTime($uid){
	//获取当前IP，转换为二进制，再转换为十进制，用于存储在数据库中
	$ip = bindec(decbin(ip2long($_SERVER['REMOTE_ADDR'])));
	//获取当前时间
	$time =  date('Y-m-d H:i:s');
	$user = M ('LoginInfo');
	//准备插入数据
	$date['uid'] = $uid;
	$date['login_ip'] = $ip;
	$date['login_time'] = $time;
	$date['pass_wrong_status'] = 0;
	//最后记录错误信息
	$user->add($date);
}
/**
 * 该函数用于记录前台登录正确密码输出信息
 */
function recordTime($uid){
	//获取当前IP，转换为二进制，再转换为十进制，用于存储在数据库中
	$ip = bindec(decbin(ip2long($_SERVER['REMOTE_ADDR'])));
	//获取当前时间
	$time =  date('Y-m-d H:i:s');
	$user = M ('LoginInfo');
	//准备插入数据
	$date['uid'] = $uid;
	$date['login_ip'] = $ip;
	$date['login_time'] = $time;
	$date['pass_wrong_status'] = 1;
	//最后插入记录信息
	$user->add($date);
}

/**
 * 用于生成唯一订单号
 */
function orderNum(){
    return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))),0, 8);
}