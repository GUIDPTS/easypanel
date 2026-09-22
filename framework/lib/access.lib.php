<?php
class Access
{
	private $vh;
	private $access;
	private $whm;

	public function __construct($vh = null, $access = 'request')
	{
		$this->vh = $vh;
		$this->access = $access;
		$this->whm = apicall('nodes', 'makeWhm', array('localhost'));
	}

	public function listTable()
	{
		$result = $this->call('list_table', null);
		if (!$result || $result->getCode() != 200) {
			return false;
		}

		$tables = $result->getAll('table');
		if (!is_array($tables)) {
			return array();
		}
		$ret = array();
		foreach ($tables as $table) {
			// kangle 3.6 returns structured table data for the new console,
			// while the long-standing WHM protocol returned a plain name.
			$ret[] = isset($table->name) ? (string) $table->name : (string) $table;
		}
		return $ret;
	}

	public function addTable($table)
	{
		$result = $this->call('add_table', array('table_name' => $table));
		if (!$result || $result->getCode() != 200) {
			return false;
		}

		return true;
	}

	public function emptyTable($table)
	{
		$result = $this->call('empty_table', array('table_name' => $table));
		if (!$result || $result->getCode() != 200) {
			return false;
		}

		return true;
	}

	public function delTable($table)
	{
		$result = $this->call('del_table', array('table_name' => $table));
		if (!$result || $result->getCode() != 200) {
			return false;
		}

		return true;
	}

	public function findChain($table, $name)
	{
		$result = $this->call('list_chain', array('table_name' => $table, 'name' => $name, 'detail' => 1));
		if (!$result || $result->getCode() != 200) {
			return false;
		}

		return $this->normalizeLegacyChainValues($result->get('table_info')->children());
	}

	/**
	 * $detail为1时得到的是详细的信息，否则为概要
	 * Enter description here ...
	 * @param  $table
	 * @param  $detail
	 */
	public function listChain($table, $detail = 1)
	{
		$result = $this->call('list_chain', array('table_name' => $table, 'detail' => $detail));
		if (!$result || $result->getCode() != 200) {
			return false;
		}

		return $this->normalizeLegacyChainValues($result->get('table_info')->children());
	}

	/**
	 * kangle 3.6 serializes model parameters as attributes. Older WHM output
	 * also exposed each model's primary value as its element text, which the
	 * existing EasyPanel controllers use for list-page display. Restore that
	 * representation while retaining every attribute for newer callers.
	 */
	private function normalizeLegacyChainValues($table)
	{
		if (!$table) {
			return $table;
		}

		$primary_attributes = array(
			'acl_file_ext' => 'v',
			'acl_header' => 'val',
			'acl_host' => 'v',
			'acl_meth' => 'meth',
			'acl_path' => 'path',
			'acl_referer' => 'referer',
			'acl_reg_path' => 'path',
			'acl_self_ports' => 'v',
			'acl_src' => 'ip',
			'acl_srcs' => 'v',
			'acl_url' => 'url',
			'acl_wide_host' => 'v',
			'mark_content' => 'content',
			'mark_gspeed_limit' => 'limit',
			'mark_host' => 'host',
			'mark_host_rewrite' => 'host',
			'mark_ip_speed_limit' => 'speed_limit',
			'mark_param' => 'value',
			'mark_post_file' => 'filename',
			'mark_redirect' => 'dst',
			'mark_rewrite' => 'dst',
			'mark_speed_limit' => 'limit',
			'mark_url_rewrite' => 'dst',
		);

		foreach ($table->children() as $chain) {
			foreach ($chain->children() as $model) {
				if ((string) $model !== '') {
					continue;
				}
				$name = $model->getName();
				if (!isset($primary_attributes[$name])) {
					continue;
				}
				$value = (string) $model[$primary_attributes[$name]];
				if ($value !== '') {
					$model[0] = $value;
				}
			}
		}
		return $table;
	}

	public function editChain($table, $arr, $models = null)
	{
		return $this->replaceChain('edit_chain', $table, $arr, $models);
	}

	public function addChain($table, $arr, $models = null)
	{
		return $this->replaceChain('add_chain', $table, $arr, $models);
	}

	private function replaceChain($callName, $table, $arr, $models = null)
	{
		$arr['table_name'] = $table;
		$whmCall = new WhmCall('core.whm', $callName);
		$whmCall->multi_param = true;
		$whmCall->addParam('access', $this->access);

		if ($this->vh) {
			$whmCall->addParam('vh', $this->vh);
		}

		foreach ($arr as $k => $v) {
			$whmCall->addParam($k, $v);
		}

		if ($models) {
			foreach ($models as $name => $val) {
				if (strpos($name, '#') !== false) {
					$name = explode('#', $name)[0];
				}
				if (isset($val[0])) {
					foreach ($val as $v) {
						$this->setModel($whmCall, $name, $v);
					}
				}
				else {
					$this->setModel($whmCall, $name, $val);
				}
			}
		}

		$result = $this->whm->call($whmCall);
		if (!$result || $result->getCode() != 200) {
			return false;
		}

		return true;
	}

	private function setModel(&$whmCall, $name, $val)
	{
		$whmCall->addParam('begin_sub_form', $name);

		foreach ($val as $k => $v) {
			$whmCall->addParam($k, $v);
		}

		$whmCall->addParam('end_sub_form', 1);
	}

	public function delChainByName($table, $name)
	{
		$result = $this->call('del_chain', array('table_name' => $table, 'name' => $name));
		if (!$result || $result->getCode() != 200) {
			return false;
		}

		return true;
	}

	public function delChain($table, $id)
	{
		$result = $this->call('del_chain', array('table_name' => $table, 'id' => $id));
		if (!$result || $result->getCode() != 200) {
			return false;
		}

		return true;
	}

	private function call($callname, $arr)
	{
		$whmCall = new WhmCall('core.whm', $callname);
		$whmCall->multi_param = true;
		$whmCall->addParam('access', $this->access);

		if ($this->vh) {
			$whmCall->addParam('vh', $this->vh);
		}

		if ($arr) {
			foreach ($arr as $k => $v) {
				$whmCall->addParam($k, $v);
			}
		}

		return $this->whm->call($whmCall);
	}
}
