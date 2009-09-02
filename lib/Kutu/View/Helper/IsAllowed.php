<?php

/**
 * check if User is allowed to view
 * 
 * @author Himawan Putra <ninjok@gmail.com>
 * @package Kutu
 * 
 */

class Kutu_View_Helper_IsAllowed
{
	public function isAllowed($itemGuid, $action, $section='content')
	{
		$auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()) { 
            return false;
        }
		
		$front = Zend_Controller_Front::getInstance();
		$aclMan = $front->getParam('bootstrap')->getResource('acl');
		
		return $aclMan->isAllowed($auth->getIdentity()->username, $itemGuid, $action, $section);
	}
}

?>