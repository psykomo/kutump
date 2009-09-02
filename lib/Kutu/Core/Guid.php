<?php

/**
 * module guid for universal prefix
 * 
 * @author Himawan Anindya Putra
 * @package Kutu
 * 
 */

class Kutu_Core_Guid
{
	public function generateGuid($prefix=null)
	{	
		$registry = Zend_Registry::getInstance(); 
		$application = $registry->get(ZEND_APP_REG_ID);
		$aGuidConfig =  $application->getOption('guid');
		$prefix = $aGuidConfig['prefix'];
		//die($prefix);
		return uniqid($prefix);
	}	
}
