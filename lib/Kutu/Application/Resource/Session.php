<?php
class Kutu_Application_Resource_Session extends Zend_Application_Resource_ResourceAbstract
{
	protected $_saveHandler;
	
	public function init()
    {
		
		// timeout value for the cookie
		$cookie_timeout = 60 * 60 * 24; // in seconds
		//$cookie_timeout = 1440;

		// timeout value for the garbage collector
		// we add 300 seconds, just in case the user's computer clock
		// was synchronized meanwhile; 600 secs (10 minutes) should be
		// enough - just to ensure there is session data until the
		// cookie expires
		$garbage_timeout = $cookie_timeout + 600; // in seconds

		// set the PHP session id (PHPSESSID) cookie to a custom value
		session_set_cookie_params($cookie_timeout);

		// set the garbage collector - who will clean the session files -
		// to our custom timeout
		ini_set('session.gc_maxlifetime', $garbage_timeout);
		// ini_set('session.gc_probability', '1');
		// ini_set('session.gc_divisor', '1');
		//die('identity:'.ini_get('session.gc_maxlifetime'));
		
		$options = array_change_key_case($this->getOptions(), CASE_LOWER);
	
		
		if(isset($options['adapter']))
		{
			$adapter = $options['adapter'];
			//unset($options['savehandler']);
		}
	
		switch (strtolower($adapter))
		{
			case 'remote':
			case 'proxydb':
				$sessionHandler = new Kutu_Session_SaveHandler_Remote();
				Zend_Session::setSaveHandler($sessionHandler);
				$this->_saveHandler = $sessionHandler;
				return $sessionHandler;
				break;
			default:
			case 'directdb':
				require_once('Kutu/Session/SaveHandler/DirectDb.php');
				$sessionHandler = new Kutu_Session_SaveHandler_DirectDb($options['db']['adapter'], $options['db']['params']);
				Zend_Session::setSaveHandler($sessionHandler);
				$this->_saveHandler = $sessionHandler;
				return $sessionHandler;
				break;
		}

    }

	//[TODO] somehow if i use function setSaveHandler, it will be executed otomatically... i don't know... need investigation
	//ternyata otomatis diexecute ini dikarenakan di application ini, kita nulisnya resources.session.savehandler = XXX. nah,
	// inilah yang mentrigger fungsi setSaveHandler di jalankan.
	public function setSaveHandlerXXX($value)
	{
		//echo 'test';
		//Zend_Session::setSaveHandler($this->_saveHandler);
	}
}
?>