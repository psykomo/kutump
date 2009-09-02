<?php
class Kutu_Auth_Adapter_Factory
{
	public function __construct()
	{
	
	}
	public function getAdapter($adapter, $params=array())
	{
		switch ($adapter)
		{
			/*case 'db-httptunnel':
				$authAdapter = new Kutu_Auth_Adapter_Remote($config->auth->config->remote->url,$this->_identity,$this->_credential);
				break;*/
			//case 'ldap':
			//case 'openid':
			case 'direct':
			case 'directdb':
			case 'db-direct':
			case 'p2p':
			default:
				//$db = Zend_Db::factory($adapter, $config->auth->config->db->param->toArray());
				$db = Zend_Db::factory($adapter, $params);
				$authAdapter = new Kutu_Auth_Adapter_DbTable($db,'KutuUser','username','password');
				break;

		}
		return $authAdapter;
	}
}
?>