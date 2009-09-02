<?php
class Test_IndexController extends Kutu_Controller_Action
{
	function preDispatch() 
    { 
		
    }
    public function passarrayAction()
    {
		//try this:
		// http://localhost/kutump-enhanced/test/index/passarray/param1/1/param2/2/param1/1a
		
		$r = $this->getRequest();
		print_r($r->getParams());
		die();
	}
	public function testcookieAction()
	{
		Zend_Session::start();
		
		setcookie('PHPSESSID','test',0,'/','localhost');
		setcookie('aku','test2',time()+3600,'/','.test.com');
		setcookie('PHPSESSID','test',0,'/','127.0.0.1');
		setcookie('PHPSESSID','test',0,'/','10.0.1.194');
		die();
	}
	public function setcookieremoteAction()
	{
		$url = 'http://10.0.1.194/kutump-enhanced/test/index/setsession';
		$ret = '<script language="javascript" type="text/javascript" src="' . $url . '?' .
		                    'PHPSESSID=' . 'iniphpsessid';
		echo $ret;
		die();
	}
	public function setsessionAction()
	{
		header('Content-Type: text/javascript; charset=' . 'iso-8859-1');
		
		$r - $this->getRequest();
		$sessid = $r->getParam('PHPSESSID');
		Zend_Session::setId($sessid);
		Zend_Session::start();
		die();
	}
	public function testSendEmailAction()
	{
		echo "testing email uuk";
		
		$config = new Zend_Config_Ini(KUTU_ROOT_DIR.'/application/configs/mail.ini', 'general');

		/*$options = array('auth' => $config->mail->auth,
						'ssl' =>'tls',
						'port' => 587,
		                'username' => $config->mail->username,
		                'password' => $config->mail->password);
						 //'ssl' => 'ssl');*/
						
		$options = array('auth' => $config->mail->auth,
						'ssl' =>'ssl',
		                'username' => $config->mail->username,
		                'password' => $config->mail->password);
						 //'ssl' => 'ssl');
	
		/*$options = array('auth' => $config->mail->auth,
		                'username' => $config->mail->username,
		                'password' => $config->mail->password);*/
		
		
		//$transport = new Zend_Mail_Transport_Smtp($config->mail->host, $options);mail.lgslaw.co.id
		//$transport = new Zend_Mail_Transport_Smtp("202.59.206.85", $options);
		//$transport = new Zend_Mail_Transport_Smtp("74.125.45.25", $options);
		$transport = new Zend_Mail_Transport_Smtp("smtp.gmail.com", $options);

		$mail = new Zend_Mail();
		$mail->setBodyText("Test email.");
		$mail->setFrom($config->mail->sender->support->email, $config->mail->sender->support->name);
		//$mail->setFrom($config->mail->username, $config->mail->sender->support->name);
		$mail->addTo("ninjok@gmail.com", "Himawan Putra");
		$mail->setSubject('[KUTU] GOOGLE Test Sending Email');
		
		try 
		{
			//echo $config->mail->auth;
			//die();
			$mail->send($transport);
		}
		catch (Zend_Exception $e)
		{
			//no need to do anything. The error is only about sending email.
			//maybe, we may set status in table user indicating that we never send
			// the user with welcome email.
			echo $e->getMessage();
		}
		
		
		die();
	}
	public function sendSocketAction()
	{
		/*$errorNum = 0;
        $errorStr = '';

        // open connection
        $_socket = @stream_socket_client("tcp://mail.lgslaw.co.id:25", $errorNum, $errorStr, self::TIMEOUT_CONNECTION);

        if ($this->_socket === false) 
		{
            if ($errorNum == 0) {
                $errorStr = 'Could not open socket';
            }
		}
		print_r ($_socket);*/
		$httpcheck = @fsockopen("74.125.127.100", "80", $errno, $errstr, 5); // @ will mute any errors. This is needed so that should the port be down then it will not show any errors.
		if($httpcheck){ // Check if $httpcheck is true.
			echo "http on google.com is running."; // Echo Message if port is up.
		}else{ // If $httpcheck finds the port down
			echo "Error: ".$errstr."(".$errstr.")"; // Echo Message if port is down
		}
		
		die();
	}
	public function testIndexingAction()
	{
		$registry = Zend_Registry::getInstance(); 
		$application = $registry->get(ZEND_APP_REG_ID);
		
		//ensure resource Session has/is initialized;
		$application->getBootstrap()->bootstrap('indexing');
		
	 	$indexing =  $application->getBootstrap()->getResource('indexing');
		die();
	}
	public function testAppAction()
	{
		$resource = Kutu_Application::getResource('acl');
		var_dump($resource);
		die();
	}
	public function testMailerAction()
	{
		//$this->Mailer(3, 'admin-paypal', 'admin');
		//$this->Mailer(1, 'user-paypal', 'XXX');
		
		$mod = new App_Model_Store_Mailer();
		$mod->test();
		
		die();
	}
	
	public function Mailer($idOrder, $key, $userTo){
        $mail = new PaymentGateway_HtmlMail();
		
		$tblSetting = new Kutu_Core_Orm_Table_PaymentSetting();
		$template = $tblSetting->fetchAll($tblSetting->select()->where("settingKey = '$key'"));
		
		$tblOrder = new Kutu_Core_Orm_Table_Order;
        $tblOrderDetail = new Kutu_Core_Orm_Table_OrderDetail;

        $rowset = $tblOrder->getOrderAndStatus($idOrder);
		//echo '<pre>';
		//print_r($_SESSION['_orderIdNumber']);
        $rowsetDetail = $tblOrderDetail->fetchAll($tblOrderDetail->select()->where("orderId = ". $idOrder));
		$tblConfirm = new Kutu_Core_Orm_Table_PaymentConfirmation;
		
		$unConfirmed = $tblConfirm->fetchAll($tblConfirm->select()->where("confirmed =0 AND orderId = ". $idOrder));
		
		$detail = "ORDER ID : ".$idOrder.'<br/>'
					.'Detail : <br/><blockquote><ol>';
		foreach($rowsetDetail as $row){
				$detail .= '<li><ul>
							<li>Document Name: '.$row->documentName.'</li>
							<li>Quantity : '.$row->qty.'</li>
							<li>Price : USD '.number_format($row->price,2).' </li>
							<li>Tax : '.number_format($row->tax,2).' %</li>
							<li>Final Price : '.number_format($row->finalPrice,2).'</li>
							</ul></li>';
		}
		$detail .= '</ol></blockquote>';
		
        //$userId=$rowset[0]['userId'];
        //echo $userId;
		
		$auth =  Zend_Auth::getInstance();
		$userId = $auth->getIdentity()->guid;
        $tblUser= new Kutu_Core_Orm_Table_User();
        $this->_userDetailInfo=$tblUser->find($userId)->current();
        
		$sMailSource=$template[0]->note;
        
		$tblSetting = new Kutu_Core_Orm_Table_PaymentSetting();
		$adminMail = $tblSetting->fetchAll($tblSetting->select()->where("settingKey = 'paypalBusiness'"));
        if( $userTo == 'admin')
		{
			
			
			$sMailEmailTo= $adminMail[0]->settingValue;
			//die($this->_userDetailInfo->email);
			$sMailEmailFrom= $this->_userDetailInfo->email;
			
			$link = '<a href="'.KUTU_ROOT_URL.'/admin/store/detailOrder/id/'.$idOrder.'">here</a>';
		}else{
			$sMailEmailTo= $this->_userDetailInfo->email;
			$sMailEmailFrom= $adminMail[0]->settingValue;
			$link = '<a href="'.KUTU_ROOT_URL.'/site/store_payment/detail/id/'.$idOrder.'">here</a>';
		}
        $sMailSubject="Confirmation for user payment";
        $sMailHeader='';
        $aMailDataSet=array('PAYMENTDATE' 	=> @$unConfirmed[0]->paymentDate,
                            'PAYMENT'	=> $rowset[0]->paymentMethod,
							'DESCRIPTION'	=> $detail,
							'TOTALORDER'	=> $rowset[0]->orderTotal,
							'ORDERTIME'	=> $rowset[0]->datePurchased,
							'INVOICE'	=>	$rowset[0]->invoiceNumber,
							'METHOD' =>$rowset[0]->paymentMethod,
							'LINK' => $link);
        $mail->SendFileMail($sMailSource, $sMailEmailTo, $sMailSubject, $sMailEmailFrom, $sMailHeader, $aMailDataSet);
    }
	function testxmlcountryAction()
	{
		//var_dump($this); die();
		$layout = Zend_Layout::getMvcInstance();
		$layout->disableLayout();
		$config = new Zend_Config_Xml(CONFIG_PATH.'/countries.xml','countries');
		//var_dump($config->database->params->get(0));
		//var_dump($config->get('country')->get(0));
		foreach($config->get('country') as $country)
			echo $country->name." ($country->alpha2)<br>";
			
		//$view = App_
		//die();
	}
}
?>