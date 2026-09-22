<?php
needRole('admin');
define('CDN_TABLE', '!cdn_table');
define('ACTION', 'table:!cdn_table');
define('BEGIN', 'BEGIN');
define('PROT', '80');
class HostcdnControl extends control
{
	public function __construct()
	{
		parent::__construct();
		load_lib('pub:access');
	}

	public function hostcdn()
	{
		$this->addTable();
		$access = new Access();
		$result = $access->listChain(CDN_TABLE, 1);
		$id = 0;
		$cdns = array();

		if ($result !== false) {
			foreach ($result->children() as $chain) {
				$row = array('v' => '', 'host' => '', 'port' => '', 'id' => $id++);
				foreach ($chain->children() as $name => $model) {
					if ($name === 'acl_host') {
						$row['v'] = trim((string) $model['v'], '|');
					} elseif ($name === 'mark_host') {
						$row['host'] = (string) $model['host'];
						$row['port'] = (string) $model['port'];
					}
				}
				$cdns[] = $row;
			}
		}

		$this->assign('cdns', $cdns);
		return $this->_tpl->display('hostcdn/hostcdn.html');
	}

	public function addHostcdn()
	{
		$arr = array();
		$arr['action'] = 'continue';
		$v = trim(ep_str(isset($_REQUEST['v']) ? $_REQUEST['v'] : ''));
		$host = trim(ep_str(isset($_REQUEST['host']) ? $_REQUEST['host'] : ''));
		$port = intval(isset($_REQUEST['port']) ? $_REQUEST['port'] : 80);
		if (!$host || !$v) {
			return header('Location: ?c=hostcdn&a=hostcdn');
		}
		if ($port < 1 || $port > 65535) {
			exit('端口范围必须为1-65535');
		}

		$models = array();
		$models['acl_host'] = array('v' => $v);
		$models['mark_host'] = array('host' => $host, 'port' => $port, 'proxy' => 1);
		$access = new Access();

		if ($access->addChain(CDN_TABLE, $arr, $models)) {
			if (!$access->findChain(BEGIN, CDN_TABLE)) {
				$access->addChain(BEGIN, array('action' => ACTION, 'name' => CDN_TABLE)) || exit('不能增加链，请重试');
			}

			return header('Location: ?c=hostcdn&a=hostcdn');
		}

		exit('增加失败');
	}

	public function delHostcdn()
	{
		$access = new Access();
		$id = intval(isset($_REQUEST['id']) ? $_REQUEST['id'] : -1);
		if ($id < 0) {
			exit('参数错误');
		}

		if (!$access->delChain(CDN_TABLE, $id)) {
			$this->_tpl->assign('msg', '删除失败!');
			return $this->fetch('msg.html');
		}

		return header('Location: ?c=hostcdn&a=hostcdn');
	}

	private function addTable()
	{
		$access = new Access();
		$tables = $access->listTable();
		$table_finded = false;

		foreach (ep_iter($tables) as $table) {
			if ($table == CDN_TABLE) {
				$table_finded = true;
				break;
			}
		}

		if (!$table_finded) {
			if (!$access->addTable(CDN_TABLE)) {
				exit('不能增加表,请重试');
			}
		}
	}
}
