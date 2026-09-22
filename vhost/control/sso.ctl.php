<?php
class SsoControl extends Control
{
	public function hello()
	{
		header('p3p: CP="CAO DSP COR CUR ADM DEV TAI PSA PSD IVAi IVDi CONi TELo OTPi OUR DELi SAMi OTRi UNRi PUBi IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE GOV"');
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
		header('Cache-Control: no-cache,no-store');
		header('Location: ' . ep_safe_header_url($url));
		exit();
	}

	public function login()
	{
		if (empty($_SESSION['sess_key'])) {
			exit('error,sess_key is empty');
		}

		$name = trim(ep_request('name'));

		if (!$name) {
			exit('param error');
		}

		$skey = daocall('setting', 'get', array('skey'));

		if (!$skey) {
			exit('skey error');
		}

		$str = ep_request('r') . $name . $_SESSION['sess_key'] . $skey;
		$md5str = md5($str);
		$sign = ep_request('s');
		if (ep_hash_equals(strtolower($md5str), strtolower($sign)) && $sign != '') {
			registerRole('vhost', $name);
			$_SESSION['login_from'] = 'vhost';
			header('Location: index.php');
			exit();
		}

		exit('login failed');
	}
}
