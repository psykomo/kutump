<?php
class Admin_IndexingController extends Zend_Controller_Action
{
	function preDispatch() 
    { 
		$this->_helper->layout()->setLayout('layout-fb2');
		
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
			
			$acl = Kutu_Acl::manager();
			if(!($acl->checkAcl("site",'all','user', $username, false,false)))
			{
				$this->_helper->redirector('restricted', "error", 'admin');
			}
		}
		
    }
	public function indexAction()
	{
		
	}
	public function reindexallcatalogAction()
	{
		$indexing = Kutu_Application::getResource('indexing');
		$indexing->reIndexAllCatalog();
	}
}
?>