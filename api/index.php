<?php
function verificationSkey()
{
	$r = ep_request('r');
	$a = ep_request('a');
	$s = ep_request('s');

	if ($r == '' || $a == '' || $s == '') {
		return false;
	}

	$skey = daocall('setting', 'get', array('skey'));

	if (!$skey) {
		return false;
	}

	$urls = $a . $skey . $r;

	if (ep_hash_equals(md5($urls), $s)) {
		return true;
	}

	return false;
}

function whm_dump($ret)
{
	if (is_array($ret)) {
		$str = '';
		foreach ($ret as $k => $v) {
			$tag = ep_xml_tag($k);
			$str .= '<' . $tag . '>' . whm_dump($v) . '</' . $tag . ">\n";
		}

		return $str;
	}

	return ep_xml_escape($ret);
}

function whm_return($status, $ret = null)
{
	$json_flag = ep_request('json');
	$fmt = ep_request('fmt');

	if ($json_flag == 1 || $fmt == 'json') {
		$json = array();
		$json['result'] = $status;

		if ($ret) {
			if (is_array($ret)) {
				foreach ($ret as $k => $v) {
					$json[$k] = $v;
				}
			}
			else {
				$json['msg'] = $ret;
			}
		}

		exit(json_encode($json));
	}

	header('Content-Type: text/xml; charset=utf-8');
	$str = '<?xml version="1.0" encoding="utf-8"?>';
	$whm_call = ep_xml_tag(ep_request('a'), 'result');
	$str .= '<' . $whm_call . ' whm_version="1.0">';
	$str .= '<result status=\'' . ep_xml_escape($status) . '\'>';

	if (is_array($ret)) {
		foreach ($ret as $k => $v) {
			$tag = ep_xml_tag($k);
			if (is_array($v)) {
				foreach ($v as $sv) {
					$str .= '<' . $tag . '>' . whm_dump($sv) . '</' . $tag . ">\n";
				}
			}
			else {
				$str .= '<' . $tag . '>' . ep_xml_escape($v) . '</' . $tag . ">\n";
			}
		}
	}

	$str .= '</result>';
	$str .= '</' . $whm_call . '>';
	exit($str);
}

date_default_timezone_set('Asia/Shanghai');
define('APPLICATON_ROOT', dirname(__FILE__));
define('SYS_ROOT', dirname(dirname(__FILE__)) . '/framework');
define('DEFAULT_CONTROL', 'index');
include SYS_ROOT . '/runtime.php';

if (!verificationskey()) {
	whm_return(403, '权限错误,请检查通信安全码是否正确');
	exit();
}

$tpl = TPL::singleton();
$tpl->assign('title', getTitle());
startFramework();
