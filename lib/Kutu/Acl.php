<?php
/**
 * module Access Control List (ACL) 
 * 
 * @author Himawan Anindya Putra <putra@langit.biz>
 * @package Kutu
 * 
 */

class Kutu_Acl
{
	static function manager()
	{
		$registry = Zend_Registry::getInstance(); 
		$application = $registry->get(ZEND_APP_REG_ID);
		
		//ensure resource Session has/is initialized;
		$application->getBootstrap()->bootstrap('acl');
		
	 	return $application->getBootstrap()->getResource('acl');
	}
}