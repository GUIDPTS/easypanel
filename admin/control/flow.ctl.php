<?php
needRole('admin');
load_lib('pub:flow');
class FlowControl extends control
{
	public function flowSort()
	{
		$flowobj = new flow('global.db');

		if (!is_object($flowobj)) {
			trigger_error('没有这个库文件');
			return false;
		}

		$time = date('YmdH', time());

		switch (ep_request('t')) {
		case 'day':
			$t = substr($time, 0, 8);
			$table = 'flow_day';
			$data = '当天';
			break;

		case 'month':
			$t = substr($time, 0, 6);
			$table = 'flow_month';
			$data = '当月';
			break;

		default:
			$t = substr($time, 0, 8);
			$table = 'flow_day';
			$data = '当天';
			break;
		}

		$page = intval(ep_request('page'));
		$count = intval(ep_request('count'));

		if ($page <= 0) {
			$page = 1;
		}

		if ($count <= 0) {
			$count = 25;
		}
		$flows = ep_iter($flowobj->getAll($table, $t, $count));
		$this->_tpl->assign('data', $data);
		$this->_tpl->assign('t', ep_request('t'));
		$this->_tpl->assign('flows', $flows);
		$this->_tpl->assign('date', (isset($flows[0]) && is_array($flows[0]) && isset($flows[0]['t'])) ? $flows[0]['t'] : '');
		return $this->_tpl->display('flow/sort.html');
	}
}
