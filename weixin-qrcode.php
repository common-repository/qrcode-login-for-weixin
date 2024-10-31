<?php 
/**
 * Plugin Name: QRCode Login For Weixin
 * Description: 通过认证微信服务号生成带参数二维码来实现用户注册及登陆功能。为容易忘记密码的人而生。
 * Plugin URI: http://markchen.me/weixin-qrcode-login
 * Author: Mark
 * Author URI: http://markchen.me
 * Version: 1.3
 * License: GPL2
 * Text Domain: qrlogin
 */



define('QRCODE_LOGIN_PLUGIN_URL', plugins_url('', __FILE__));
define('QRCODE_LOGIN_PLUGIN_DIR', plugin_dir_path(__FILE__));

include QRCODE_LOGIN_PLUGIN_DIR . 'settings.php';
include QRCODE_LOGIN_PLUGIN_DIR . 'class-weixin.php';

add_action("login_enqueue_scripts", function(){
	wp_enqueue_style( 'qrcode_css', QRCODE_LOGIN_PLUGIN_URL . "/style.css" );
	wp_print_scripts( array( 'wp-util' ) );
	wp_enqueue_script( 'qrcode_js', QRCODE_LOGIN_PLUGIN_URL . "/custom.js", array('jquery'), false, true );
	$args = array( 'nonce' => wp_create_nonce("jsnonce") );
	wp_localize_script("qrcode_js", "jsobj" , $args );
});


add_action('wp_ajax_qrcode_login_action', 'qrcode_login_action');
add_action('wp_ajax_nopriv_qrcode_login_action', 'qrcode_login_action');
function qrcode_login_action(){
	check_ajax_referer('jsnonce', 'security');
	$vcode  = isset($_POST['vcode']) ? sanitize_text_field($_POST['vcode']) : false;
	$ticket = isset($_POST['ticket']) ? sanitize_text_field($_POST['ticket']) : false;
	
	$code   = get_transient( $ticket );
	$openid = get_transient( "code_". $code );

	delete_transient( "code_". $code  );
	delete_transient( $ticket);

	if($vcode == $code && $openid ){
		$user = get_user_by("login", $openid);
		if( ! is_object( $user )){

			$userInfo = QR_Login::getInstance()->getUserInfo($openid);
			if($userInfo){
				$userdata = [
					'user_login'    => $openid,
					'user_email'	=> $openid."@wx.com",
					'user_nicename' => $userInfo['nickname'],
					'display_name'  => $userInfo['nickname'],
					'nickname'      => $userInfo['nickname']
				];

			}else{
				$user_nickname = 'U'. random_int(1000, 9999999);
				$userdata = [
					'user_login'    => $openid,
					'user_email'	=> $openid."@wx.com",
					'user_nicename' => $user_nickname,
					'display_name'  => $user_nickname,
					'nickname'      => $user_nickname
				];	
			}
			
			$res = wp_insert_user($userdata);	
			if(is_wp_error( $res ) ){
				wp_send_json_error(['error' => 'userRegisterFail'] );
			}else{
				$user = get_user_by("login", $openid);
				update_user_meta($res, 'wx_info', $userInfo);
			}
		}	

		wp_set_auth_cookie($user->ID, true);
		wp_send_json_success(['errcode' => 0, 'errmsg' => 'login success']);
		
	}else{
		wp_send_json_error(['code_invalid']);
	}

	wp_send_json( array( 'status' => 'error', 'msg' => '未定义动作'));
}

add_action("login_form", function($str){
	$imgData = QR_Login::getInstance()->get_qrcode();
?>
<p class="qrcode-item qrcode-img">
	微信扫一扫登陆
	<?php if( $imgData ){ ?>
	<img src="<?php echo $imgData['img']; ?>">	
	<?php }else{ ?>
		<div class="error">抱歉：你的微信登陆设置好像有点小问题</div>
	<?php } ?>
</p>

<p class="qrcode-item">
	<label for="vcode"><?php _e( '验证码' ); ?><br />
		<?php
			$ticket = ''; 
			if(isset($imgData['ticket']) ){
				$ticket = sanitize_text_field( $imgData['ticket'] );
			}
		?>
		<input type="hidden" id="qrcode-key" name="ticket" value="<?php echo $ticket; ?>">	
		<input type="text" name="vcode" id="vcode" class="input" size="20" />
	</label>
</p>
<p class="qrcode-item switchBtn">切换登陆方式</p>
<?php 
	return $str;
});

add_action("parse_request", function(){
	if(isset( $_GET['timestamp'], $_GET['nonce'], $_GET['signature'], $_GET['ql'] )){
		$signature = sanitize_text_field( $_GET['signature'] );
		$timestamp = sanitize_text_field( $_GET['timestamp'] );
		$nonce     = sanitize_text_field( $_GET['nonce'] );

		if(isset($_GET['echostr'])){
			$check = QR_Login::checkSignature($signature, $timestamp, $nonce);
			echo $check ? esc_html($_GET['echostr']) : '';
		}else{
			$data = QR_Login::getInstance()->get_data();
			if(isset( $data['event'], $data['eventKey'], $data['fromUserName'] )){
				if( 
					($data['event'] == 'SCAN' && $data['eventKey'] == 'login'  && $data['fromUserName']) 
					||
					( $data['event'] == 'subscribe' && $data['eventKey'] == 'qrscene_login'  && $data['fromUserName'] )
				){
					$code = random_int(1000, 9999);
					set_transient( $data['ticket'], $code, MINUTE_IN_SECONDS);
					set_transient( 'code_' . $code , $data['fromUserName'], MINUTE_IN_SECONDS );
					$data = QR_Login::getInstance()->reply_code( $data['fromUserName'], $data['toUserName'], $code);
					echo $data;
				}else{

				}
			}else{
				// 数据解析失败
			}
		}
		exit;
	}
}, 8);


add_filter("get_avatar_data", function($args, $id_or_email){
	$wx = false;
	
	if( is_string( $id_or_email) && is_email( $id_or_email)){
		$user = get_user_by('email', $id_or_email);
		$wx = get_user_meta( $user->ID , "wx_info", true);
	}else if(is_numeric( $id_or_email )){
		$user = get_user_by('ID', $id_or_email);
		$wx = get_user_meta( $user->ID , "wx_info", true);
	}

	if( is_array($wx) && isset( $wx['headimgurl'] ) ){
		$args['url'] = $wx['headimgurl'];
	}

	return $args;
}, 10, 2);

