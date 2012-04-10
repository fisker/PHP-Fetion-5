<?php
/**
 * PHP飞信发送类
 * @author fisker lionkay@gmail.com
 * @version 1.0.0
 * based on PHPFetion http://code.google.com/p/phpfetion/
 */
class PHPFetion5 {
	/**
	 * 飞信账号
	 * @var string
	 */
	protected $_username;
	
	/**
	 * 飞信密码
	 * @var string
	 */
	protected $_password;
	
	/**
	 * Cookie字符串
	 * @var string
	 */
	protected $_cookie = '';
	
	/**
	 * 构造函数
	 * @param string $username 用户名(发送者)
	 * @param string $password 飞信密码
	 */
	public function __construct( $username, $password, $login = FALSE ) {
		if( !$username || !$password ) {
			return false;
		}
		
		$this -> _username = $username;
		$this -> _password = $password;
		if( $login ){
			$this -> login();
		}
		
	}
	
	/**
	 * 析构函数
	 */
	public function __destruct() {
		//$this->logout();
	}
	
	/**
	 * 登录
	 * @return string
	 */
	public function login( $foceLogin = FALSE ) {
		if( !$foceLogin && $this -> _cookie ){
			return TRUE;
		}
		$uri = '/im5/login/loginHtml5.action';
		$data = 'm='.$this->_username.'&pass='.urlencode($this->_password).'&loginstatus=4';

		$result = $this->_postWithCookie($uri, $data);
		if( !preg_match( '/\/im5\/index\/html5\.action/', $result, $matches ) ){
			return FALSE;
		}

		// 解析Cookie
		if( preg_match_all( '/.*?\r\nSet-Cookie: (.*?);.*?/si', $result, $matches ) ){
			$this->_cookie = implode('; ', $matches[1]);
		}
		return $result;
	}

	/**
	 * cookie
	 * @return string
	 */
	public function cookie( $cookie = "" ) {
		if( $cookie ){
			$this->_cookie = $cookie;
		}
		return $this->_cookie;
	}	

	/**
	 * 退出飞信
	 * @return string
	 */
	public function logout() {
		$uri = '/im5/index/logoutsubmit.action';
		$result = $this -> _postWithCookie($uri, '');
		$this -> _cookie = '';
		return $result;
	}	
	/**
	 * 添加好友
	 * @param string $cell 手机号(接收者)
	 * @return boolean
	 */
	public function addFriend( $cell ) {
		if( !$cell ) {
			return FALSE;
		}
		if( !$this -> login() ){
			return FALSE;
		}
		$uri = '/im5/user/addFriendSubmit.action?number=' . $cell ;
		$result = $this -> _postWithCookie($uri, '');
		return strpos( $result, '发送成功' ) !== FALSE;
	}

	/**
	 * 是否好友
	 * @param string $cell 手机号(接收者)
	 * @return boolean
	 * 此函数返回不是很准,有的好友也搜索不到
	 */
	public function isFriend( $cell ) {
		if( !$cell ) {
			return FALSE;
		}
		if( !$this -> login() ){
			return FALSE;
		}
		$uri = '/im5/index/searchFriendsByQueryKey.action';
		$data = 'queryKey='. $cell ;
		$result = $this -> _postWithCookie( $uri, $data );

		//不存在: 空
		//自己: 空
		//未通过:{"total":1,"contacts":[{"idContact":1103459157,"localName":"","relationStatus":0,"contactType":0,"isBlocked":-1,"permission":"","idFetion":0,"mobileNo":"13888888888","email":"","carrier":"","carrierStatus":-1,"basicServiceStatus":0,"services":"","smsOnlineStatus":"","presenceBasic":"","presenceDesc":"","deviceType":"","uri":"tel:13888888888","nickname":"","impresa":"","impresaLength":0,"newMsgNums":0,"portraitCrc":""}]}
		//成功{"total":1,"contacts":[{"idContact":501516316,"localName":"陈汉威","relationStatus":1,"contactType":0,"isBlocked":0,"permission":"identity\u003d1;","idFetion":850357966,"mobileNo":"13888888888","email":"","carrier":"CMCC","carrierStatus":0,"basicServiceStatus":1,"services":"99","smsOnlineStatus":"0.0:0:0","presenceBasic":"100","presenceDesc":"","deviceType":"PC","uri":"sip:850357966@fetion.com.cn;p\u003d5040","nickname":"靓号","impresa":"","impresaLength":0,"newMsgNums":0,"portraitCrc":"1617640559"}]}
		if( !preg_match( '/{(.*)+}/', $result, $matches ) ){
			return FALSE;
		}
		$result = json_decode( $matches[0], TRUE );
		if( $result && 
			isset($result['contacts']) && $result['contacts'] &&
			isset($result['contacts'][0]) && $result['contacts'][0] &&
			isset($result['contacts'][0]['idFetion']) && $result['contacts'][0]['idFetion']
			){
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 向指定的手机号发送飞信
	 * @param string $mobile 手机号(接收者)
	 * @param string $message 短信内容
	 * @return boolean
	 */
	public function send( $to, $message ) {
		if( !$message ) {
			return FALSE;
		}
		if( !$this -> login() ){
			return FALSE;
		}
		// 判断是给自己发还是给好友发
		if( $to == $this -> _username ) {
			return $this -> _toMyself($message);
		} else {
			return $this -> _toFriend( $to, $message );
		}
	}

	public function cellToId( $cell ) {
		if( !$this -> login() ){
			return FALSE;
		}
		$uri = '/im5/user/searchFriendByPhone.action?number='.$cell;
		$data = '';
		$result = $this->_postWithCookie($uri, $data);
		if( !preg_match( '/{(.*)+}/', $result, $matches ) ){
			return NULL;
		}
		$result = json_decode( $matches[0], TRUE );
		//自己 : {"tip":"对不起,不能加自己为好友.","type":0}
		//未注册 : {"tip":"","userinfo":{"idFetion":-1,"mobileNo":"13888888888","email":"","nickname":"无"},"type":0}
		//成功 : {"tip":"","userinfo":{"idFetion":1111,"mobileNo":"13888888888","email":"","nickname":"靓号"},"type":0}
		if( !$result || !isset($result['tip']) ){
			return NULL;
		}
		if( !isset($result['userinfo']) || 
			!$result['userinfo'] ||
			!isset($result['userinfo']['idFetion']) || 
			!$result['userinfo']['idFetion'] ||
			$result['userinfo']['idFetion'] < 0
			 ){
			return NULL;
		}
		return $result['userinfo']['idFetion'];
	}
	/**
	 * 给别人发飞信
	 * @param string $to 手机号
	 * @return boolean
	 */
	protected function _toFriend( $to, $message ) {
		if( preg_match( '/^(\d){13,13}$/', $to, $matches ) ){
			$to = $this -> cellToId( $cell );
		}
		if( !$to ){
			return FALSE;
		}
		$uri = '/im5/chat/sendNewMsg.action';
		$data = 'touserid='.urlencode($to).'&msg='.urlencode($message);
		$result = $this->_postWithCookie($uri, $data);
		return strpos( $result, '成功' ) !== FALSE;
	}
	
	/**
	 * 给自己发飞信
	 * @param string $message
	 * @return boolean
	 */
	protected function _toMyself($message) {
		$uri = '/im5/user/sendMsgToMyselfs.action';
		$result = $this -> _postWithCookie($uri, 'msg='.urlencode($message));
		//飞信永远显示失败..实际能收到
		return TRUE;
	}
	
	/**
	 * 携带Cookie向f.10086.cn发送POST请求
	 * @param string $uri
	 * @param string $data
	 */
	protected function _postWithCookie( $uri, $data='' ) {
		$fp = fsockopen( 'f.10086.cn', 80 );
		fputs( $fp, "POST $uri HTTP/1.1\r\n" );
		fputs( $fp, "Host: f.10086.cn\r\n" );
		fputs( $fp, "Cookie: {$this->_cookie}\r\n" );
		fputs( $fp, "Content-Type: application/x-www-form-urlencoded\r\n" );
		fputs( $fp, "Content-Length: ".strlen($data)."\r\n" );
		fputs( $fp, "Connection: close\r\n\r\n" );
		fputs( $fp, $data );

		$result = '';
		while( !feof( $fp ) ) {
			$result .= @fgets( $fp, 1024 );
		}
		fclose( $fp );
		return $result;
	}

}
