<?php
class ErrorController extends Zend_Controller_Action
{
public function errorAction()
{
	// Grab the error object from the request
	$errors = $this->_getParam('error_handler') ;
	
	// $errors will be an object set as a parameter of the request object, type is a property
	switch ($errors->type)
	{
		case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
		case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
 
			// 404 error -- controller or action not found 
				$this->getResponse()->setHttpResponseCode(404) ;
				$this->view->message = 'Page not found' ;
				break ;
  
		default:
				// application error 
				$this->getResponse()->setHttpResponseCode(500) ;
				$this->view->message = 'Application error' ;
				break ;
	}
  
	// pass the actual exception object to the view
	$this->view->exception = $errors->exception ;
  
	// pass the request to the view
	$this->view->request = $errors->request ;
	}
}
?>