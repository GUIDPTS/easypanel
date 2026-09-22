<?php
needRole('vhost');
class RecordControl extends Control
{
	public function recordAddFrom()
	{
		return $this->_tpl->fetch('record/from.html');
	}

	public function recordAdd()
	{
		$domain = trim(ep_request('domain'));
		$name = trim(ep_request('name'));

		if (!$name) {
			$name = '@';
		}

		$type = trim(ep_request('type'));
		$value = trim(ep_request('value'));
		$view = trim(ep_request('view'));
		$ttl = intval($_REQUEST['ttl']);
	}

	public function recordDel()
	{
	}

	public function recordUpdate()
	{
	}
}
