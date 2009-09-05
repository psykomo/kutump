<?php
class Kutu_Application_Resource_Mail extends Zend_Application_Resource_ResourceAbstract
{
	public function init()
    {
		/*$options = array_change_key_case($this->getOptions(), CASE_LOWER);
		
		//print_r($options);
		
		switch (strtolower($options['adapter']))
		{
			case 'phpgacl':
				$aclAdapter = new Kutu_Acl_Adapter_PhpGacl($options['db']['adapter'], $options['db']['params']);
				return $aclAdapter;
			default :
				throw new Zend_Exception('Kutu_Acl does not support adapter: '. $config->acl->adapter. '. Please check your configuration.', 101);
		}*/
		
		$config = new Zend_Config_Ini(CONFIG_PATH.'/mail.ini', 'general');
		$options = array('auth' => $config->mail->auth,
		                'username' => $config->mail->username,
		                'password' => $config->mail->password);
		
		if(!empty($config->mail->ssl))
		{
			$options = array('auth' => $config->mail->auth,
							'ssl' => $config->mail->ssl,
			                'username' => $config->mail->username,
			                'password' => $config->mail->password);
		}
			
		//echo $config->mail->host.'--'; die();
		$transport = new Zend_Mail_Transport_Smtp($config->mail->host, $options);
		return $transport;

    }
}
?>