<?php
class App_Model_Store_Mailer
{
	private $_view;
	
	public function __construct()
	{
		$view = new Zend_View();
		$view->setScriptPath(dirname(__FILE__));
		$this->_view = $view;
	}
	
	public function test()
	{
		echo "INI TEST";
		
		echo $this->_view->render('send-invoice.phtml');
	}
	public function mailer($idOrder, $key, $userTo)
	{
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
		$rowOrder = $tblOrder->find($idOrder)->current();
	
		$userId = $rowOrder->userId;
        

		$tblUser= new Kutu_Core_Orm_Table_User();
        $userDetailInfo=$tblUser->find($userId)->current();
        
		$sMailSource=$template[0]->note;
        
		$tblSetting = new Kutu_Core_Orm_Table_PaymentSetting();
		$adminMail = $tblSetting->fetchAll($tblSetting->select()->where("settingKey = 'paypalBusiness'"));
		
        if( $userTo == 'admin')
		{	
			$sMailEmailTo= $adminMail[0]->settingValue;
			$sMailEmailFrom= $userDetailInfo->email;
			
			$link = '<a href="'.KUTU_ROOT_URL.'/admin/store/detailOrder/id/'.$idOrder.'">here</a>';
		}else{
			$sMailEmailTo= $userDetailInfo->email;
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
	
	public function mailerAdmin($idOrder, $key, $userTo)
	{
		$mail = new PaymentGateway_HtmlMail();
		
		$tblSetting = new Kutu_Core_Orm_Table_PaymentSetting();
		$template = $tblSetting->fetchAll($tblSetting->select()->where("settingKey = '$key'"));
		
		$tblOrder = new Kutu_Core_Orm_Table_Order;
		$tbluser = new Kutu_Core_Orm_Table_User;
        $tblOrderDetail = new Kutu_Core_Orm_Table_OrderDetail;
        $tblSetting = new Kutu_Core_Orm_Table_PaymentSetting();
        $lgsMail = $tblSetting->fetchAll($tblSetting->select()->where("settingKey = 'paypalBusiness'"));
		
		$userDetailInfo = $tbluser->userInfoOrder($idOrder);
		
		
        $rowset = $tblOrder->getOrderAndStatus($idOrder);
        $rowsetDetail = $tblOrderDetail->fetchAll($tblOrderDetail->select()->where("orderId = ". $idOrder));
		$tblConfirm = new Kutu_Core_Orm_Table_PaymentConfirmation;
		
		if($rowset[0]->orderStatus == 6){
			$status = 'rejected';
		}else{
			$status = 'confirmed';
		}
		
		
		$unConfirmed = $tblConfirm->fetchAll($tblConfirm->select()->where("confirmed =0 AND orderId = ". $idOrder));
		echo '<pre>';
		//print_r($userDetailInfo);
		echo '</pre>';
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
		$detail .= '</ol><blockquote><br />';
		
		//print_r($detail); 
		$sMailSource=$template[0]->note;
		if( $userTo == 'admin'){
			$sMailEmailTo= $lgsMail[0]->settingValue;
			$sMailEmailFrom= $userDetailInfo[0]->email;
			$link = '<a href="'.KUTU_ROOT_URL.'/admin/store/detailOrder/id/'.$idOrder.'">here</a>';
		}else{
			$sMailEmailTo= $userDetailInfo[0]->email;
			$sMailEmailFrom= $lgsMail[0]->settingValue;
			$link = '<a href="'.KUTU_ROOT_URL.'/site/store_payment/detail/id/'.$idOrder.'">here</a>';
		}
        $sMailSubject="Confirmation for user payment";
        $sMailHeader='';
        $aMailDataSet=array('PAYMENTDATE' 	=> $unConfirmed[0]->paymentDate,
							'PAIDTIME' => $unConfirmed[0]->paymentDate,
                            'PAYMENT'	=> $rowset[0]->paymentMethod,
							'DESCRIPTION'	=> $detail,
							'TOTALORDER'	=> $rowset[0]->orderTotal,
							'ORDERTIME'	=> $rowset[0]->datePurchased,
							'INVOICE'	=>	$rowset[0]->invoiceNumber,
							'METHOD' =>$rowset[0]->paymentMethod,
							'LINK' => $link,
							'STATUS' => $status);
        $mail->SendFileMail($sMailSource, $sMailEmailTo, $sMailSubject, $sMailEmailFrom, $sMailHeader, $aMailDataSet);
	}
/*------------------------------------------------------------------------*/	
	
	public function sendBankInvoiceToUser($orderId, $sendCc ='')
	{
		$config = new Zend_Config_Ini(KUTU_ROOT_DIR.'/application/configs/mail.ini', 'general');
		
		$siteOwner = "LGS Online";
		$siteName = $config->mail->sender->support->name;
		$contactEmail = $config->mail->sender->support->email;
		
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$rowOrder = $tblOrder->find($orderId)->current();
		$userId = $rowOrder->userId;
		
		$tblUser = new Kutu_Core_Orm_Table_User();
		$rowUser = $tblUser->find($userId)->current();
		
		$userEmail = $rowUser->email;
		$userFullname = $rowUser->firstname.' '.$rowUser->lastname;
		
		if($sendCc == 1){
			$mailCc = $config->mail->sender->support->email;
			$mailCcName = $config->mail->sender->support->name;
		}else{
			$mailCc = '';
			$mailCcName = '';
		}
		$message = 
"Dear $userFullname,

This is a notice that an invoice has been generated on $rowOrder->datePurchased.

Your payment method is: $rowOrder->paymentMethod

Please sent your payment to :

BCA BANK, cabang Bintaro
XXX.XXX.XXXX
PT. LGS

LIPPO BANK
YYY.YYY.YYYY
PT. LGS

MANDIRI BANK
YYY.YYY.YYYY
PT. LGS

BANK BNI Syariah
XXX.XXX.XXXX
PT. LGS

Invoice # $rowOrder->invoiceNumber
Amount Due: USD $rowOrder->orderTotal
Due Date: -

You can login to your client area to view and pay the invoice at ".KUTU_ROOT_URL."/site/store/viewinvoice/orderId/$orderId. After made payment to us, please fill confirmation order at ".KUTU_ROOT_URL."/site/store_payment/confirm

Salam,

LGS ONLINE
==============================";	
		
		$sFile = "sendBankInvoiceToUser.log";
		$content = "SENDER : ".$config->mail->sender->support->email."\n";
		$content .= "NAME : ".$config->mail->sender->support->name."\n";
		$content .= "RECIPIENT : ".$userEmail."\n";
		$content .= "MESSAGE : [LGS ONLINE] Receipt Invoice# " . $rowOrder->invoiceNumber . $message ."\n"; 
		$this->logThisMail($sFile,$content);
		
		$this->send($config->mail->sender->support->email, $config->mail->sender->support->name, 
				$userEmail, '', "[LGS ONLINE] Invoice: ". $rowOrder->invoiceNumber, $message,
				$mailCc, $mailCcName);
	
	}
	
	public function sendInvoiceToUser($orderId, $sendCc = '')
	{
		$config = new Zend_Config_Ini(KUTU_ROOT_DIR.'/application/configs/mail.ini', 'general');
		
		$siteOwner = "LGS Online";
		$siteName = $config->mail->sender->support->name;
		$contactEmail = $config->mail->sender->support->email;
		
		if($sendCc == 1){
			$mailCc = $config->mail->sender->support->email;
			$mailCcName = $config->mail->sender->support->name;
		}else{
			$mailCc = '';
			$mailCcName = '';
		}
		
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$rowOrder = $tblOrder->find($orderId)->current();
		$userId = $rowOrder->userId;
		
		$tblUser = new Kutu_Core_Orm_Table_User();
		$rowUser = $tblUser->find($userId)->current();
		
		$userEmail = $rowUser->email;
		$userFullname = $rowUser->firstname.' '.$rowUser->lastname;
		
		$message = 
"Dear $userFullname,

This is a notice that an invoice has been generated on $rowOrder->datePurchased.

Your payment method is: $rowOrder->paymentMethod

Invoice # $rowOrder->invoiceNumber
Amount Due: USD $rowOrder->orderTotal
Due Date: -

You can login to your client area to view and pay the invoice at ".KUTU_ROOT_URL."/site/store/viewinvoice/orderId/$orderId.

Best Regards,

LGS ONLINE

==============================";	
		
		$sFile = "sendInvoiceToUser.log";
		$content = "SENDER : ".$config->mail->sender->support->email."\n";
		$content .= "NAME : ".$config->mail->sender->support->name."\n";
		$content .= "RECIPIENT : ".$userEmail."\n";
		$content .= "MESSAGE : [LGS ONLINE] Receipt Invoice# " . $rowOrder->invoiceNumber . $message ."\n"; 
		$this->logThisMail($sFile,$content);
		
		$this->send($config->mail->sender->support->email, $config->mail->sender->support->name, 
				$userEmail, '', "[LGS ONLINE] Invoice: ". $rowOrder->invoiceNumber, $message,
				$mailCc, $mailCcName);
	
	}
	
	public function sendReceiptToUser($orderId, $paymentMethod='', $statusText='', $sendCc = '')
	{
		$config = new Zend_Config_Ini(KUTU_ROOT_DIR.'/application/configs/mail.ini', 'general');
		
		$siteOwner = "LGS Online";
		$siteName = $config->mail->sender->support->name;
		$contactEmail = $config->mail->sender->support->email;
		
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$rowOrder = $tblOrder->find($orderId)->current();
		$userId = $rowOrder->userId;
		if($sendCc == 1){
			$mailCc = $config->mail->sender->support->email;
			$mailCcName = $config->mail->sender->support->name;
		}else{
			$mailCc = '';
			$mailCcName = '';
		}
		//first check if orderId status is PAID, then send the email.
		
		switch ($rowOrder->orderStatus)
		{
			case 1:
				die('ORDER STATUS IS NOT YET PAID. CAN NOT SEND RECEIPT!.');
				break;
			case 3:
				$orderStatus = "PAID";
				break;
			case 5:
				$orderStatus = "POSTPAID PENDING";
				break;
			case 6:
				$orderStatus = "PAYMENT REJECTED";
				break;
			case 7:
				$orderStatus = "PAYMENT ERROR";
				break;
			default:
				$orderStatus = "PAYMENT ERROR";
				break;
		}
		
		$tblUser = new Kutu_Core_Orm_Table_User();
		$rowUser = $tblUser->find($userId)->current();
		
		$userEmail = $rowUser->email;
		$userFullname = $rowUser->firstname.' '.$rowUser->lastname;
		
		switch(strtolower($paymentMethod))
		{
			case 'paypal':
			case 'manual':
			case 'bank':
			case 'postpaid':
			default:
				$message = 
"					
Dear $userFullname,

This is a payment receipt for Invoice # $rowOrder->invoiceNumber

Total Amount: USD $rowOrder->orderTotal
Transaction #:
Total Paid: USD $rowOrder->orderTotal
Status: $orderStatus
Reason: $rowOrder->adminNotes
Your payment method is: $paymentMethod

You may review your invoice history at any time by logging in to your account ".KUTU_ROOT_URL."/site/store_payment/list

Note: This email will serve as an official receipt for this payment.

Salam,

LGS ONLINE

==============================";

		}
		$sFile = "sendReceiptToUser-".$paymentMethod.".log";
		$content = "SENDER : ".$config->mail->sender->support->email."\n";
		$content .= "NAME : ".$config->mail->sender->support->name."\n";
		$content .= "RECIPIENT : ".$userEmail."\n";
		$content .= "MESSAGE : [LGS ONLINE] Receipt Invoice# " . $rowOrder->invoiceNumber . $message ."\n"; 
		$this->logThisMail($sFile,$content);
		
		$this->send($config->mail->sender->support->email, $config->mail->sender->support->name, 
				$userEmail, '', "[LGS ONLINE] Receipt Invoice# ". $rowOrder->invoiceNumber, $message,
				$mailCc, $mailCcName);
	}
	
	public function sendUserBankConfirmationToAdmin($orderId)
	{
		$config = new Zend_Config_Ini(KUTU_ROOT_DIR.'/application/configs/mail.ini', 'general');
		
		$sOrderId = '';
		
		if(is_array($orderId))
		{
			
			for($i=0; $i< count($orderId);$i++)
			{
				$sOrderId .= $orderId[$i].', ';
			}
		}
		else
		{
			$sOrderId = $orderId;
		}
		
		$message = 
"					
You just have received Bank Transfer Confirmation for Order ID $sOrderId please check thru admin page.".

KUTU_ROOT_URL."/admin/store/confirm.

==============================";
		
		$sFile = "sendUserBankConfirmationToAdmin.log";
		$content = "SENDER : ".$config->mail->sender->support->email."\n";
		$content .= "NAME : ".$config->mail->sender->support->name."\n";
		$content .= "RECIPIENT : ".$config->mail->sender->support->email."\n";
		$content .= "MESSAGE : [LGS ONLINE] Receipt Invoice# " . $rowOrder->invoiceNumber . $message ."\n"; 
		$this->logThisMail($sFile,$content);

		$this->send($config->mail->sender->support->email, $config->mail->sender->support->name, 
						$config->mail->sender->billing->email, $config->mail->sender->billing->name, 
						"[LGS ONLINE] Bank Transfer Payment Confirmation ", $message
						);
		
		
	}
	
	public function sendPaypalCompleteNotificationToUser($orderId)
	{
		$config = new Zend_Config_Ini(KUTU_ROOT_DIR.'/application/configs/mail.ini', 'general');
		
		$sOrderId = '';
		
		$tblUser = new Kutu_Core_Orm_Table_User();
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$rowOrder = $tblOrder->find($orderId)->current();
		$userId = $rowOrder->userId;
		$rowUser = $tblUser->find($userId)->current();
		$userEmail = $rowUser->email;
		$userFullname = $rowUser->firstname.' '.$rowUser->lastname;
		
		$message = 
"					
Your Paypal payment for item(s) with Order ID $sOrderId has been completely proceed.

now you can continue to <a href=\"".KUTU_ROOT_URL."/site/store_payment/document\">download the document(s)</a> or just continue <a href=\"".KUTU_ROOT_URL."/dms\">browsing our database</a>..

==============================";
		
		$sFile = "sendPaypalCompleteNotificationToUser.log";
		$content = "SENDER : ".$config->mail->sender->support->email."\n";
		$content .= "NAME : ".$config->mail->sender->support->name."\n";
		$content .= "RECIPIENT : ".$userEmail;
		$content .= "MESSAGE : [LGS ONLINE] Receipt Invoice# " . $rowOrder->invoiceNumber . $message ."\n"; 
		$this->logThisMail($sFile,$content);
		
		$this->send($config->mail->sender->support->email, $config->mail->sender->support->name, 
						$userEmail, '', "[LGS ONLINE] Paypal Payment Complete ", $message);
		
		
	}
	
	public function sendFailedIpnToAdmin($data)
	{
		$config = new Zend_Config_Ini(KUTU_ROOT_DIR.'/application/configs/mail.ini', 'general');
		
		$sOrderId = '';
		
		$message = $data;
		
		$sFile = "sendFailedIpnToAdmin.log";
		$content = "SENDER : ".$config->mail->sender->support->email."\n";
		$content .= "NAME : ".$config->mail->sender->support->name."\n";
		$content .= "RECIPIENT : ".$config->mail->sender->support->email."\n";
		$content .= "MESSAGE : [LGS ONLINE] FAILED IPN " . $message ."\n"; 
		$this->logThisMail($sFile,$content);

		$this->send($config->mail->sender->support->email, $config->mail->sender->support->name, 
						$config->mail->sender->support->email, '', "MESSAGE : [LGS ONLINE] FAILED IPN ", $message
						,'','');
		
		
	}
	
	public function send($mailFrom, $fromName, $mailTo, $mailToName, $subject, $body, $mailCc='', $mailCcName='')
	{
		$config = new Zend_Config_Ini(KUTU_ROOT_DIR.'/application/configs/mail.ini', 'general');
		$options = array('auth' => $config->mail->auth,
		                'username' => $config->mail->username,
		                'password' => $config->mail->password);
		
		if(!empty($config->mail->ssl))
		{
			$options = array('auth' => $config->mail->auth,
							'ssl' => $config->mail->ssl,
			                'username' => $config->mail->username,
			                'password' => $config->mail->password);
		}
			
		//echo $config->mail->host.'--'; die();
		$transport = new Zend_Mail_Transport_Smtp($config->mail->host, $options);
		
		$mail = new Zend_Mail();
		$mail->setBodyText($body);
		$mail->setFrom($mailFrom, $fromName);
		$mail->addTo($mailTo, $mailToName);
		if($mailCc != ''){
			$mail->addCc($mailCc, $mailCcName);
		}
		$mail->setSubject($subject);
		
		try 
		{
			$mail->send($transport);
		}
		catch (Zend_Exception $e)
		{
			//no need to do anything. The error is only about sending email.
			//maybe, we may set status in table user indicating that we never send
			// the user with welcome email.
			echo $e->getMessage();
		}
	}
	public function logThisMail($sFile, $sFileContent)
    {
		//if (!$this->logIpn) return;
		$sFileName = 'LogMAIL-'.$sFile;
        // Timestamp
        $text = '[' . date('m/d/Y g:i A').'] - ';

        $text .= $sFileContent;
		$text .= "==============================================================\n\n";
        // Write to log
        $fp = fopen($sFileName,'a');
        fwrite($fp, $text . "\n\n");
        fclose($fp);
    }
	public function testEcho()
	{
		echo 'test model';
	}
}
?>