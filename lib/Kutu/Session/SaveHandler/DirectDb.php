<?php
class Kutu_Session_SaveHandler_DirectDb extends Zend_Session_SaveHandler_DbTable 
{
	public function __construct($adapter, $dbParams)
    {
    	//$registry = Zend_Registry::getInstance();
    	//$configIni = $registry->get('config');
    	//$db = Zend_Db::factory($configIni->session->config->db->adapter, $configIni->session->config->db->param->toArray());

		$db = Zend_Db::factory($adapter, $dbParams);
    	
    	$config = array(
		    'name'           => 'KutuSession',
		    'primary'        => 'sessionId',
		    'modifiedColumn' => 'sessionModified',
		    'dataColumn'     => 'sessionData',
		    'lifetimeColumn' => 'sessionLifetime',
    		'db' => $db
		);

        parent::__construct($config);
    }
}
?>