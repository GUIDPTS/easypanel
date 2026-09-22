<?php
needRole('admin');
class SettingControl extends Control
{
	public function setFrom()
	{
		return $this->fetch('setting/setFrom.html');
	}

	public function index()
	{
		@load_conf('pub:settingrule');
		@load_conf('pub:setting');
		$sub = ep_request('sub');
		$info = (isset($GLOBALS['settingrule']) && is_array($GLOBALS['settingrule']) && isset($GLOBALS['settingrule'][$sub])) ? $GLOBALS['settingrule'][$sub] : null;
		$this->assign('env', $info);
		$this->assign('val', $GLOBALS['setting_cfg']);
		$this->assign('sub', $sub);
		return $this->fetch('setting/show.html');
	}

	public function add()
	{
		daocall('setting', 'add', array($_REQUEST['name'], $_REQUEST['value']));
	}

	public function set()
	{
		@load_conf('pub:settingrule');
		$names = isset($_REQUEST['name']) ? $_REQUEST['name'] : array();

		if (!is_array($names)) {
			$names = array();
		}

		$sub = ep_request('sub');
		$rules = (isset($GLOBALS['settingrule'][$sub]) && is_array($GLOBALS['settingrule'][$sub])) ? $GLOBALS['settingrule'][$sub] : array();

		foreach ($names as $name) {
			if (!empty($rules[$name]['password'])) {
				if ($_REQUEST[$name] == '') {
					continue;
				}
			}

			$ret = apicall('tplenv', 'checkEnv', array($name, isset($_REQUEST[$name]) ? $_REQUEST[$name] : '', $rules));

			if ($ret != ENV_CHECK_SUCCESS) {
				$this->_tpl->assign('msg', '设置:' . $GLOBALS['lang']['zh_CN'][$name] . ' 失败');
				$list = daocall('setting', 'getAll');
				apicall('utils', 'writeConfig', array($list, 'name', 'setting'));
				return $this->index();
			}

			daocall('setting', 'add', array($name, $_REQUEST[$name]));
		}

		$list = daocall('setting', 'getAll');
		apicall('utils', 'writeConfig', array($list, 'name', 'setting'));
		return $this->index();
	}
}
