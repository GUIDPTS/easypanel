<?php
class SsoControl extends Control
{
	public function hello()
	{
		session_unset();
		$base_passwd = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_0123456789';
		$base_len = strlen($base_passwd);
		$len = 16;
		$sess_key = '';
		$i = 0;

		while ($i < $len) {
			$sess_key .= $base_passwd[rand() % $base_len];
			++$i;
		}

		$url = ep_safe_header_url(isset($_REQUEST['url']) ? $_REQUEST['url'] : '');

		if ($url === '') {
			exit('invalid url');
		}

		$url = $url . '&r=' . $sess_key;
		$_SESSION['sess_key'] = $sess_key;
		header('Location: ' . $url);
		exit();
	}

	public function login()
	{
		if (empty($_SESSION['sess_key'])) {
			exit('error,sess_key is empty');
		}

		$name = ep_request('name');
		$skey = daocall('setting', 'get', array('skey'));

		if (!$skey) {
			exit('skey error');
		}

		$str = ep_request('r') . $name . $_SESSION['sess_key'] . $skey;
		$md5str = md5($str);
		$sign = ep_request('s');
		if (ep_hash_equals(strtolower($md5str), strtolower($sign)) && $sign != '') {
			registerRole('admin', 'admin');
			header('Location: ?c=vhost&a=showVhost&name=' . rawurlencode($name));
			exit();
			return NULL;
		}

		exit('login failed');
	}
}
