<?php
class Kutu_Application_Resource_Acl extends Zend_Application_Resource_ResourceAbstract
{
	public function init()
    {
		$options = array_change_key_case($this->getOptions(), CASE_LOWER);
		
		//print_r($options);
		
		switch (strtolower($options['adapter']))
		{
			case 'phpgacl':
				$aclAdapter = new Kutu_Acl_Adapter_PhpGacl($options['db']['adapter'], $options['db']['params']);
				return $aclAdapter;
			default :
				throw new Zend_Exception('Kutu_Acl does not support adapter: '. $config->acl->adapter. '. Please check your configuration.', 101);
		}

    }
}
?>