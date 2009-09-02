<?php
class Site_Bootstrap extends Zend_Application_Module_Bootstrap
{
	/*public function __construct($application)
    {
        /*$configPath = dirname(__FILE__) . '/configs/config.ini';
        if (true) {
            $config = new Zend_Config_ini($configPath, $application->getEnvironment());
            $this->setApplication($application)
                 ->setOptions($config->toArray())
                 ->initResourceLoader();
        } else {
            parent::__construct($application);
        }
		parent::__construct($application);
    }*/
	/*public function _initJcart()
	{
		include KUTU_ROOT_DIR.'/lib/jcart/jcart.php';
	}
	
	protected function _initAppAutoload()
	{
	    $autoloader = new Zend_Application_Module_Autoloader(array(
	        'namespace' => 'Site',
	        'basePath' => dirname(__FILE__),
	    ));
	//echo dirname(__FILE__);
	    return $autoloader;
	}*/
}
?>