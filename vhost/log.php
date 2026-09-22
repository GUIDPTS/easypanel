<?php
date_default_timezone_set('Asia/Shanghai');
define('APPLICATON_ROOT', dirname(__FILE__));
define('SYS_ROOT', dirname(dirname(__FILE__)) . '/framework');
$url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$a = strrchr($url, '/');

if ($a === false) {
	$a = '';
}

$p = strpos($a, '?');

if ($p !== false) {
	$a = substr($a, 0, $p);
}

$file = substr($a, 1);

if ($file === '' || $file === 'log.php') {
	$file = 'index.html';
}

if (strpos($file, '..') !== false || strpos($file, '/') !== false || strpos($file, '\\') !== false) {
	exit('invalid file');
}

$_REQUEST['c'] = 'webalizer';
$_REQUEST['a'] = 'showLog';
$_REQUEST['file'] = $file;
include SYS_ROOT . '/runtime.php';
$tpl = TPL::singleton();
$tpl->assign('title', getTitle());
startFramework();
