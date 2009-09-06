<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initConfigLegacy()
	{
		/*$config = new Zend_Config_Ini(CONFIG_PATH.'/config.ini', 'general'); 
		$registry = Zend_Registry::getInstance(); 
		$registry->set('config', $config);*/ 
		$registry = Zend_Registry::getInstance();
		$registry->set('files', $_FILES);
	}
	protected function _initAppModelsAutoload()
	{
	    $autoloader = new Zend_Application_Module_Autoloader(array(
	        'namespace' => 'App',
	        'basePath' => dirname(__FILE__),
	    ));
	//echo dirname(__FILE__);
	    return $autoloader;
	}
	
}
?>