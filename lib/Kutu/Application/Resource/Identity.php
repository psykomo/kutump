<?php
class Kutu_Application_Resource_Identity extends Zend_Application_Resource_ResourceAbstract
{
	public function init()
    {
		$options = array_change_key_case($this->getOptions(), CASE_LOWER);
		
		$identity = new Kutu_Identity();
		$identity->loginUrl = $options['url']['login'];
		$identity->logoutUrl = $options['url']['logout'];
		$identity->rememberMeDuration = $options['rememberme']['duration'];
		$identity->remembermeEnable = $options['rememberme']['enable'];
		
		//print_r($options);
		
		$sAuthAdapter = $options['auth']['adapter'];
		switch (strtolower($sAuthAdapter))
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
					$db = Zend_Db::factory($options['auth']['db']['adapter'], $options['auth']['db']['params']);
					$authAdapter = new Kutu_Auth_Adapter_DbTable($db,'KutuUser','username','password');
					break;

			
		}
		$identity->authAdapter =  $authAdapter;
		
		return $identity;

    }
}
?>