<?php
class AccessAPI extends API
{
	/**
	 * 
	 * Enter description here ...
	 * @param string $type
	 * @param string $version
	 */
	public function checkAccess($type = null, $version = null)
	{
		if (!$_SESSION['user'][getRole('vhost')]['access']) {
			return '您的空间不支持该功能，请联系管理员';
		}

		return true;
	}

	public function checkEntAccess()
	{
		return true;
	}
}
