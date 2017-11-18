<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * discuz!金典的加密函数
 * @param string $string 明文 或 密文
 * @param string $operation DECODE表示解密,其它表示加密
 * @param string $key 密匙
 * @param int $expiry 密文有效期
 */
function authcode($string, $operation = 'ENCODE', $key = '', $expiry = 0) {
	// 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
	$ckey_length = 4;

	// 密匙
	$key = md5($key ? $key : C('AUTH_KEY')); // AUTH_KEY 项目配置的密钥

	// 密匙a会参与加解密
	$keya = md5(substr($key, 0, 16));
	// 密匙b会用来做数据完整性验证
	$keyb = md5(substr($key, 16, 16));
	// 密匙c用于变化生成的密文
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	// 参与运算的密匙
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);
	// 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
	// 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);
	$result = '';
	$box = range(0, 255);
	$rndkey = array();
	// 产生密匙簿
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	// 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	// 核心加解密部分
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		// 从密匙簿得出密匙进行异或，再转成字符
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		// substr($result, 0, 10) == 0 验证数据有效性
		// substr($result, 0, 10) - time() > 0 验证数据有效性
		// substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
		// 验证数据有效性，请看未加密明文的格式
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		// 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
		// 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码

		return $keyc.str_replace('=', '', base64_encode($result));
	}
}

/**
 * 字符串截取，支持中文和其他编码
 * static
 * access public
 * @param string $str 需要转换的字符串
 * @param string $length 截取长度
 * @param string $start 开始位置
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * return string
 */
function msubstr($str,$length, $start=0, $charset="utf-8", $suffix=true) {
	if(function_exists("mb_substr")){
		$slice = mb_substr($str, $start, $length, $charset);
		$strlen = mb_strlen($str,$charset);
	}elseif(function_exists('iconv_substr')){
		$slice = iconv_substr($str,$start,$length,$charset);
		$strlen = iconv_strlen($str,$charset);
	}else{
		$re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		preg_match_all($re[$charset], $str, $match);
		$slice = join("",array_slice($match[0], $start, $length));
		$strlen = count($match[0]);
	}
	if($suffix && $strlen>$length)$slice.='...';
	return $slice;
}

/**
 * 生成随机字符串
 * @param string $to 收件人
 * @param string $subject 主题
 * @param string $body 正文
 * @param string $attachment_dir 附件地址
 * @return boolean
 */
function get_rand_str($len=6,$type=0){ 
	$data = '';		
	if($type == 0){
		$data = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
	}elseif($type == 1){
		$data = '0123456789'; 
	}elseif($type == 2){
		$data = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	}

	$str = ''; 
	for ( $i = 0; $i < $len; $i++ ){ 
		$str .= $data[ mt_rand(0, strlen($data) - 1) ]; 
	} 
	return $str; 
} 

/**
 * 使用phpmailer发送邮件
 * @param string $to 收件人
 * @param string $subject 主题
 * @param string $body 正文
 * @param string $attachment_dir 附件地址
 * @return boolean
 */
function send_mail($to,$subject,$body='',$attachment_dir=''){
	require_once __ROOT__.COMMON_PATH.'Plugin/PHPMailer/class.phpmailer.php';
	$mail = new PHPMailer;
	$mail->CharSet ="utf-8";//设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
	$mail->IsSMTP(); // 设定使用SMTP服务
	$mail->SMTPDebug  = 1;// 启用SMTP调试功能1 = errors and messages 2 = messages only
	$mail->SMTPAuth   = true;                  // 启用 SMTP 验证功能
	$mail->SMTPSecure = "smtp";                 // 安全协议
	$mail->Host       = C('email.host');      // SMTP 服务器
	$mail->Port       = C('email.port');                   // SMTP服务器的端口号
	$mail->Username   = C('email.username');  // SMTP服务器用户名
	$mail->Password   = C('email.password');            // SMTP服务器密码
	$mail->SetFrom(C('email.address'),C('base.site_name'));
	$mail->AddReplyTo(C('email.address'),C('base.site_name'));//增加回复标签，参数1地址，参数2名称

	//组装新的格式    标题 收件人 内容  附件
	$mail->Subject = $subject;  //主题
	$mail->MsgHTML($body); //正文  支持html格式
	$mail->AddAddress($to, " ");//增加收件人 参数1为收件人邮箱，参数2为收件人称呼
	if(!empty($attachment_dir)){
		$mail->AddAttachment($attachment_dir);//附件的路径和附件名
	}

	return $mail->Send();
}