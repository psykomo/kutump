<?php
class Site_Myaccount_NewsletterController extends Zend_Controller_Action
{
	function preDispatch() 
    { 
		$this->_helper->layout()->setLayout('layout-final-inside');
		
		$saveHandlerManager = new Kutu_Session_SaveHandler_Manager();
		$saveHandlerManager->setSaveHandler();
		Zend_Session::start();
		
		$sReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$sReturn = urlencode($sReturn);
		$this->view->returnTo = $sReturn;
		
		$auth =  Zend_Auth::getInstance();
		if(!$auth->hasIdentity())
		{
			$this->_redirect(KUTU_ROOT_URL.'/helper/sso/login'.'?returnTo='.$sReturn);
		}
		else
		{
			// [TODO] else: check if user has access to admin page
			$username = $auth->getIdentity()->username;
			$this->view->username = $username;
		}
		
    }
	function subscribeAction()
	{
		//echo $this->_getParam('email');
		
		$base = 'http://groups.google.com/group/lgsonline/boxsubscribe?email='. $this->_getParam('email');
		
		$client = new Zend_Http_Client($base, array( 
		    'maxredirects'     => 100, 
		    'timeout'          => 30, 
		  'keepalive'        => true 
		));
		
		$response = $client->request('GET'); 
		
		//echo $response->getBody();
	}
}