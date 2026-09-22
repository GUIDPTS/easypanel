<?php
needRole('admin');
class DnsdomainControl extends Control
{
	public function dnsdomainAddFrom()
	{
		return $this->_tpl->fetch('dnsdomain/from.html');
	}

	public function domainAdd()
	{
		$json = array();
		$json['code'] = 400;
		$arr = array();
		$arr['name'] = trim(ep_request('domain'));

		if (!preg_match('/^[0-9a-zA-Z][0-9a-zA-Z_.-]+?[.][a-z0-9]{1,5}$/', $arr['name'])) {
			$json['msg'] = '域名不合法';
			exit(json_encode($json));
		}

		if ($arr['name'] == '') {
			$json['msg'] = '域名不能为空';
			exit(json_encode($json));
		}

		$arr['passwd'] = trim(ep_request('passwd'));
		$arr['max_record'] = intval($_REQUEST['max_record']);
		$arr['status'] = 0;

		if ($_REQUEST['server']) {
			$arr['server'] = trim(ep_request('server'));
		}

		if ($_REQUEST['salt']) {
			$arr['salt'] = trim(ep_request('salt'));
		}

		if (@apicall('domain', 'domainAdd', array($arr))) {
			$json['code'] = 200;
		}

		exit(json_encode($json));
	}

	public function imLoginDomain()
	{
		$domain = trim(ep_request('domain'));
		registerRole('dns', $domain);
		header('Location:/dns/index.php');
		exit();
	}

	public function domainInit()
	{
		$domain = trim(ep_request('domain'));
		$json['code'] = 400;

		if (@apicall('bind', 'writeZoneFile', array($domain))) {
			$json['code'] = 200;
		}

		exit(json_encode($json));
	}

	public function domainDel()
	{
		$domain = trim(ep_request('domain'));
		$json['code'] = 400;

		if (@apicall('domain', 'domainDel', array($domain))) {
			$json['code'] = 200;
		}

		exit(json_encode($json));
	}

	public function dnsdomainPageList()
	{
		$page = intval($_REQUEST['page']);

		if ($page <= 0) {
			$page = 1;
		}

		$page_count = 20;
		$count = 0;
		$where = array();

		if ($_REQUEST['mode']) {
			switch ($_REQUEST['mode']) {
			case 'name':
				$where['name'] = trim(ep_request('mode_value'));
				break;

			case 'server':
				$where['server'] = trim(ep_request('mode_value'));
				break;

			default:
				break;
			}
		}

		$order = $_REQUEST['order'] ? $_REQUEST['order'] : null;
		$list = daocall('domains', 'domainPageList', array($page, $page_count, &$count, $where, $order));
		$total_page = ceil($count / $page_count);

		if ($total_page <= $page) {
			$page = $total_page;
		}

		if ($order) {
			$this->_tpl->assign('order', $order);
		}

		$this->_tpl->assign('count', $count);
		$this->_tpl->assign('total_page', $total_page);
		$this->_tpl->assign('page', $page);
		$this->_tpl->assign('page_count', $page_count);
		$this->_tpl->assign('list', $list);
		return $this->_tpl->fetch('dnsdomain/pagelist.html');
	}
}
