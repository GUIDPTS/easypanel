<?php
needRole('admin');
define('DENY_IP_TABLE', '!deny_ip_table');
define('DENY_FILTER_TABLE', '!deny_filter');
define('ACTION', 'table:!deny_filter');
define('BEGIN', 'BEGIN');
define('PROT', '80|443');
class AccessControl extends Control
{
	public function __construct()
	{
		parent::__construct();
		load_lib('pub:access');
	}

	public function saveFile()
	{
		header('Content-Disposition: attachment; filename=filter.txt');
		header('Content-Type: application/octet-stream');
		$list = daocall('filter', 'listFilter', array());

		foreach (ep_iter($list) as $key) {
			echo $key['value'] . "\r\n";
		}

		exit();
	}

	public function readFile()
	{
		if ($_FILES['upfile']['name'] == '') {
			exit('没有选择文件');
		}

		$lines = file($_FILES['upfile']['tmp_name']);

		if (!$lines) {
			exit('导入失败');
		}

		foreach ($lines as $key) {
			$key = trim($key);
			$this->filterr();
			$this->insertResponse($key);
		}

		header('Location: ?c=access&a=filterFrom');
		exit();
	}

	public function emptyFilter()
	{
		$access = new Access(null, 'response');
		$tables = $access->listTable();
		if ($tables === false) {
			$this->assign('msg', '清空失败');
			return $this->fetch('msg.html');
		}

		if (in_array(DENY_FILTER_TABLE, $tables, true)) {
			$chain = $access->findChain(BEGIN, DENY_FILTER_TABLE);
			if ($chain !== false && !$access->delChainByName(BEGIN, DENY_FILTER_TABLE)) {
				$this->assign('msg', '清空失败');
				return $this->fetch('msg.html');
			}
			if (!$access->emptyTable(DENY_FILTER_TABLE) || !$access->delTable(DENY_FILTER_TABLE)) {
				$this->assign('msg', '清空失败');
				return $this->fetch('msg.html');
			}
		}

		daocall('filter', 'clear');

		header('Location: ?c=access&a=filterFrom');
		exit();
	}

	public function startFilter()
	{
		$access = new Access(null, 'response');
		$chain = $access->findChain(BEGIN, DENY_FILTER_TABLE);
		$status = intval($_REQUEST['status']);

		switch ($status) {
		case 0:
			$access->delChainByName(BEGIN, DENY_FILTER_TABLE);
			header('Location: ?c=access&a=filterFrom');
			exit();
		case 1:
			if ($chain == false) {
				$access->addTable(DENY_FILTER_TABLE);
				$models = array();
				$models['acl_self_ports'] = array('v' => PROT, 'split'=>'|');
				$access->addChain(BEGIN, array('action' => ACTION, 'name' => DENY_FILTER_TABLE), $models);
				header('Location: ?c=access&a=filterFrom');
				exit();
			}
		default:
			header('Location: ?c=access&a=filterFrom');
			exit();
		}
	}

	private function insertResponse($filter)
	{
		daocall('filter', 'add', array($filter));
		$keyword = daocall('filter', 'listFilter', array());
		$content = apicall('utils', 'mergeKeyword', array($keyword));
		$arr = array();
		$arr['action'] = 'deny';
		$models['acl_header'] = array('header' => 'content-type', 'val' => 'text/*', 'regex' => 1);
		$models['mark_content'] = array('content' => $content, 'charset' => 'utf-8');
		$access = new Access(null, 'response');
		$access->delChain(DENY_FILTER_TABLE, 0);
		$access->addChain(DENY_FILTER_TABLE, $arr, $models);
	}

	public function addfilter()
	{
		$this->filterr();
		$filter = $_REQUEST['filter'];
		$filter = trim($filter, '(');
		$filter = trim($filter, ')');
		$this->insertResponse($filter);
		header('Location: ?c=access&a=filterFrom');
		exit();
	}

	public function filterFrom()
	{
		$access = new Access(null, 'response');
		$keywords = daocall('filter', 'listFilter', array());
		$keyword = apicall('utils', 'mergeKeyword', array($keywords));
		$chain = $access->findChain(BEGIN, DENY_FILTER_TABLE);

		$name = $chain !== false ? DENY_FILTER_TABLE : null;

		$this->assign('key', $keyword);
		$this->assign('name', $name);
		return $this->fetch('filter/filter.html');
	}

	private function filterr()
	{
		$access = new Access(null, 'response');
		$tables = $access->listTable();
		$table_finded = false;

		foreach (ep_iter($tables) as $table) {
			if ($table == DENY_FILTER_TABLE) {
				$table_finded = true;
				break;
			}
		}

		if (!$table_finded) {
			if (!$access->addTable(DENY_FILTER_TABLE)) {
				exit('不能增加表');
			}

			$models['acl_self_ports'] = array('v' => PROT, 'split'=>'|');

			if (!$access->addChain(BEGIN, array('action' => ACTION, 'name' => DENY_FILTER_TABLE), $models)) {
				exit('不能增加链');
			}
		}
	}

	public function ip()
	{
		$access = new Access();
		$tables = $access->listTable();
		$table_finded = false;

		foreach (ep_iter($tables) as $table) {
			if ($table == DENY_IP_TABLE) {
				$table_finded = true;
				break;
			}
		}

		if (!$table_finded) {
			if (!$access->addTable(DENY_IP_TABLE)) {
				exit('不能增加表');
			}

			if (!$access->addChain('BEGIN', array('action' => 'table:' . DENY_IP_TABLE, 'name' => DENY_IP_TABLE))) {
				exit('不能增加链');
			}
		}
		else {
			$result = $access->listChain(DENY_IP_TABLE, 1);
			$id = 0;
			$ips = array();

			if ($result) {
				foreach ($result->children() as $chain) {
					$ips[] = array('ip' => (string) $chain->children(), 'id' => $id++);
				}
			}

			$this->assign('ips', $ips);
		}

		return $this->fetch('access/ip.html');
	}

	public function addip()
	{
		$ip = trim(ep_str(isset($_REQUEST['ip']) ? $_REQUEST['ip'] : ''));
		if (!$this->checkIp($ip)) {
			exit('请输入正确的IP地址');
		}
		$arr['begin_sub_form'] = 'acl_src';
		$arr['ip'] = $ip;
		$arr['end_sub_form'] = 1;
		$arr['action'] = 'deny';
		$access = new Access();
		$access->addChain(DENY_IP_TABLE, $arr);
		header('Location: ?c=access&a=ip');
		exit();
	}

	private function checkIp($ip)
	{
		$parts = explode('/', $ip, 2);
		if (!filter_var($parts[0], FILTER_VALIDATE_IP)) {
			return false;
		}

		if (count($parts) === 1) {
			return true;
		}

		$max_prefix = strpos($parts[0], ':') === false ? 32 : 128;
		return $parts[1] !== '' && ctype_digit($parts[1]) && intval($parts[1]) <= $max_prefix;
	}

	public function delip()
	{
		$access = new Access();
		$id = $_REQUEST['id'];

		if (!$access->delChain(DENY_IP_TABLE, $id)) {
			$this->_tpl->assign('msg', '删除失败!');
			return $this->fetch('msg.html');
		}

		header('Location: ?c=access&a=ip');
		exit();
	}
}
