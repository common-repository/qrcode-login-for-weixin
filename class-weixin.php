<?php 

if ( ! defined( 'ABSPATH' ) )  die( 'Invalid request.' );

/**
 * qrcodew
 */

class QR_Login
{
	
	const  ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
	const  USER_INFO        = 'https://api.weixin.qq.com/cgi-bin/user/info';
	const  QR_TEMP          = 'https://api.weixin.qq.com/cgi-bin/qrcode/create';
	
	private $appid;
	private $secret;
	private static $instance;

	function __construct(){
		$this->appid = get_option('wx_appid');
		$this->secret = get_option('wx_secret');
	}

	static function getInstance(){
		if( self::$instance == null){
			self::$instance = new self();
		}
		return self::$instance;
	}


	static function reply_code ($openid, $toUserName, $code) {
		$time = time();
		$msg = "您好，\n\n您本次的登陆验证码是：" .$code ;
		$msg .= "\n\n有效期为60秒，请尽快登陆！";
		return '<xml>
				  <ToUserName><![CDATA['.$openid.']]></ToUserName>
				  <FromUserName><![CDATA['.$toUserName.']]></FromUserName>
				  <CreateTime>'.$time.'</CreateTime>
				  <MsgType><![CDATA[text]]></MsgType>
				  <Content><![CDATA['.$msg.']]></Content>
				</xml>';
	}

	function getUserInfo($openid){
		
		$args = [
			'access_token' => $this->getAccessToken(),
			'openid'       => $openid,
			'lang'         => 'zh_CN'
		];
		$url = add_query_arg($args, self::USER_INFO);
		$resp = wp_remote_get($url);
		if(is_wp_error($resp)) return $resp;
		$body = wp_remote_retrieve_body($resp);
		$data = json_decode( $body, true );
		if(isset( $data['openid'], $data['nickname'] )){
			return $data;
		}else{
			return false;
		}
	}

	static function checkSignature( $signature, $timestamp, $nonce ){
		$tmpArr = array($timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		return true;
		
	}

	function get_data(){
		$xmltext = file_get_contents("php://input");

		$xml = new DOMDocument();
		$xml->loadXML($xmltext);
		
		$objToUserName   = $xml->getElementsByTagName('ToUserName');
		$objFromUserName = $xml->getElementsByTagName('FromUserName');
		$objCreateTime   = $xml->getElementsByTagName('CreateTime');
		$objMsgType      = $xml->getElementsByTagName('MsgType');
		$objEvent        = $xml->getElementsByTagName('Event');
		$objEventKey     = $xml->getElementsByTagName('EventKey');
		$objTicket       = $xml->getElementsByTagName('Ticket');
		
		$toUserName      = $objToUserName->item(0)->nodeValue;
		$fromUserName    = $objFromUserName->item(0)->nodeValue;
		$createTime      = $objCreateTime->item(0)->nodeValue;
		$msgType         = $objMsgType->item(0)->nodeValue;
		$event           = $objEvent->item(0)->nodeValue;
		$eventKey        = $objEventKey->item(0)->nodeValue;
		$ticket          = $objTicket->item(0)->nodeValue;

		return compact( 'toUserName', 'fromUserName', 'createTime', 'msgType', 'event', 'eventKey', 'ticket'  );

	}

	function getAccessToken(){
		
		$access_token = get_transient("qrcode_access_token");
		if($access_token === false){
			$args = [
				'grant_type' => 'client_credential',
				'appid'      => $this->appid,
				'secret'     => $this->secret 
			];
			$url = add_query_arg( $args, self::ACCESS_TOKEN_URL);
			
			$resp = wp_remote_get($url);
			
			if(is_wp_error($resp)) return $resp;
			$body = wp_remote_retrieve_body($resp);
			$data = json_decode( $body, true );
			if(isset($data['access_token'], $data['expires_in'])){
				$access_token = $data['access_token'];
				set_transient( "qrcode_access_token" , $access_token, $data['expires_in'] - MINUTE_IN_SECONDS);
			}else{
				return false;
			}
		}
		
		return $access_token;
	}

	function get_qrcode(){
		$url = add_query_arg( ['access_token' => $this->getAccessToken()], self::QR_TEMP );
		
		$data = [
			'expire_seconds' => MINUTE_IN_SECONDS,
			'action_name'    => 'QR_STR_SCENE',
			'action_info'    => [ 'scene' => [ 'scene_str' => 'login' ] ]
		];

		$args = [ 'body' => json_encode($data) ];
		$resp = wp_remote_post($url, $args);
		if(is_wp_error($resp)) return $resp;
		$body = wp_remote_retrieve_body($resp);
		$data = json_decode( $body, true );
		if(isset( $data['ticket']) ){
			return [ 
					'ticket' => $data['ticket'], 
					'img' => 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode( $data['ticket'] ) 
				];
		}else{
			return false;
		}
	}
}