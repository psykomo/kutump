<?php

class Site_Store_PaymentController extends Zend_Controller_Action{
    
    protected $_model; 
    protected $_payment;
    protected $_paymentVars;
    protected $_paymentMethod;
    protected $_testMode;
    protected $_userInfo;
    protected $_orderIdNumber;
    protected $_defaultCurrency;
    protected $_currencyValue;
	protected $_userDetailInfo;
	protected $_lgsMail;
        
    function preDispatch()
	{
	
		//don't check auth from here because the verificationAction SHOULD NOT USE ANY AUTHENTICATION METHOD
		
		
        /*
        - Load Configuration dari tabel kutupaymentSetting
        - set TestMode = True or False 
        */
        $this->_testMode=true;
		$this->_defaultCurrency='USD';
		$tblPaymentSetting = new Kutu_Core_Orm_Table_PaymentSetting();
		$usdIdrEx = $tblPaymentSetting->fetchAll($tblPaymentSetting->select()->where(" settingKey= 'USDIDR'"));
		$this->_currencyValue = $usdIdrEx[0]->settingValue;
		$this->_helper->layout()->setLayout('layout-final-inside');
        
        $saveHandlerManager = new Kutu_Session_SaveHandler_Manager();
        $saveHandlerManager->setSaveHandler();
        Zend_Session::start();
        
        $sReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $sReturn = urlencode($sReturn);
        $this->view->returnTo = $sReturn;
       
        $tblPaymentSetting = new Kutu_Core_Orm_Table_PaymentSetting();        
        $rowSet = $tblPaymentSetting->fetchAll();
        //var_dump($rowSet);
        
        for($iRow=0; $iRow<count($rowSet);$iRow++){
            $key=$rowSet[$iRow]->settingKey;
            $this->_paymentVars[$key]=$rowSet[$iRow]->settingValue;
        }
		
        $tblSetting = new Kutu_Core_Orm_Table_PaymentSetting();
        $this->_lgsMail = $tblSetting->fetchAll($tblSetting->select()->where("settingKey = 'paypalBusiness'"));

    }
    
	//function for updating invoice and add 1 row to history   
	protected function updateInvoiceMethod($orderId, $payMethod, $status, $notify, $note){        
        $tblOrder = new Kutu_Core_Orm_Table_Order();
		
		$rows = $tblOrder->find($orderId)->current();
		$row = array();
		
		$ivnum = $rows->invoiceNumber;
		
		/*if(empty($ivnum)){
			if($status==3 || $status==5 || (!empty($_SESSION['_method'])&&($_SESSION['_method'] =='paypal')))
			$ivnum = $this->getInvoiceNumber();
			//$row=array ('invoiceNumber'	=> $ivnum);
		}*/
		//if( )$ivnum = $this->getInvoiceNumber();
		
		
		$row=array ('orderStatus'	=> $status, 'paymentMethod' => $payMethod);
		
		//$_SESSION['_method'] = '';
		/*$this->_paymentMethod=$payMethod;//set payment method on table
		$row->paymentMethod=$this->_paymentMethod;*/
		
		$tblOrder->update($row, 'orderId = '. $orderId);
		
		$tblHistory = new Kutu_Core_Orm_Table_OrderHistory();
		$rowHistory = $tblHistory->fetchNew();
		
		$rowHistory->orderId = $orderId;
		$rowHistory->orderStatusId = $status;
		$rowHistory->dateCreated = date('YmdHis');
		$rowHistory->userNotified = $notify;
		$rowHistory->note = $note;
		$rowHistory->save();
		
		return $ivnum;
	}

	public function indexAction()
	{
		//[TODO] must check if orderId has been paid before to avoid double charge, if somehow user can access directly to payment controller.
		
		
		$this->_checkAuth();
		
		$orderId = $this->_request->getParam('orderId');
		$this->_orderIdNumber = $orderId;
		
		if(empty($orderId))
		{
			echo "kosong";
			die();
		}
		
		$modelAppStore = new App_Model_Store();
		if(!$modelAppStore->isUserOwnOrder($this->_userDetailInfo->guid, $orderId))
		{
			//forward to error page
			$this->_helper->redirector->gotoSimple('error', 'store', 'site',array('view'=>'notowner'));
			die();
		}
		if($modelAppStore->isOrderPaid($orderId))
		{
			//forward to error page
			$this->_helper->redirector->gotoSimple('error', 'store', 'site',array('view'=>'orderalreadypaid'));
			die();
		}
		
		
		
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$items = $tblOrder->getOrderDetail($orderId);
		//var_dump($items); die();
		
		$tmpMethod = $this->_request->getParam('method');
		if(!empty($tmpMethod))
		{
			$items[0]['paymentMethod'] = $tmpMethod;
		}
		
		switch($items[0]['paymentMethod'])
		{ 
            case 'paypal':
				/*
                 - Detect Multi Item and set accordingly
                 - Logic for test mode 
                */
                require_once('PaymentGateway/Paypal.php');  // include the class file
                $paymentObject = new Paypal;             // initiate an instance of the class
                
                if($this->_testMode){                    
                    $paymentObject->addField('business', $this->_paymentVars['paypalTestBusiness']);
                    $paymentObject->addField('return', $this->_paymentVars['paypalTestSuccessUrl']);
                    $paymentObject->addField('cancel_return', $this->_paymentVars['paypalTestCancelUrl']);
                    $paymentObject->addField('notify_url', $this->_paymentVars['paypalTestNotifyUrl']);
                    $paymentObject->enableTestMode();
                }else{                
                    $paymentObject->addField('business', $this->_paymentVars['paypalBusiness']);
                    $paymentObject->addField('return', $this->_paymentVars['paypalSuccessUrl']);
                    $paymentObject->addField('cancel_return', $this->_paymentVars['paypalCancelUrl']);
                    $paymentObject->addField('notify_url', $this->_paymentVars['paypalNotifyUrl']);
                }
                
                for($iCart=0;$iCart<count($items);$iCart++){
                    $i=$iCart+1;
                    $paymentObject->addField("item_number_".$i, $items[$iCart]['itemId']); 
                    $paymentObject->addField("item_name_".$i, $items[$iCart]['documentName']); //nama barang [documentName]
                    $paymentObject->addField("amount_".$i, $items[$iCart]['price']); //harga satuan [price]
                    $paymentObject->addField("quantity_".$i, $items[$iCart]['qty']); //jumlah barang [qty]\
                }
                $paymentObject->addField('tax_cart',$items[0]['orderTax']);
                $paymentObject->addField('currency_code',$this->_defaultCurrency);

				//$paymentObject->addField('custom',$_SESSION['_orderIdNumber']);
                $paymentObject->addField('custom',$orderId);
                
				$ivnum = $this->updateInvoiceMethod($orderId, 'paypal', 1, 0, 'paid with paypal method');
				
				//$paymentObject->dumpFields();
				
                $paymentObject->submitPayment();
				
				//setting payment and status as pending (1), notify = 0, notes = 'paid with...'
				break;
				
			case 'manual':
			case 'bank':
                /*
                 1. update order status
                 2. redirect to instruction page 
                */

				//setting payment and status as pending (1), notify = 0, notes = 'paid with...'
				$this->updateInvoiceMethod($orderId, 'bank', 1, 0, 'paid with manual method');
				
				// HAP: i think we should send this notification when user were on page "Complete Order" and after confirmation made by user is approved;
				//$this->Mailer($orderId, 'admin-order', 'admin');
				//$this->Mailer($orderId, 'user-order', 'user');
				
				$this->_helper->redirector('instruction','store_payment','site',array('orderId'=>$orderId));
                break;

			case 'postpaid':
                /*
                 1. validate POSTPAID status of the client 
                 2. validate CREDIT LIMIT (per user) with current Outstanding Bill + New Bill
                 3. update order status
                 4. redirect to success or failed 
                */
				/*
                * if userid isn't listed as postpaid user will be redirected
                */
                if(!$this->_userInfo->isPostPaid){
                    echo 'Not Post Paid Customer';
                    //$paymentObject->submitPayment();
                    return $this->_helper->redirector('notpostpaid');
                }
                /*====================VALIDATE CREDIT LIMIT=====================*/
                /*
                * validate credit limit :
                * 1. count total transaction 
                * 2. counting total previous unpaid postpaid transaction
                * 3. validate
                */
                //$cart = $this->completeItem();

                /*-----count total amount of prevous unpaid transaction------*/
                $tblOrder = new Kutu_Core_Orm_Table_Order(); 
				//table kutuOrder
                //select previous transaction that are postpaid based on userid
				//echo ($tblOrder->outstandingUserAmout($this->_userInfo->userId));
                $outstandingAmount=$tblOrder->outstandingUserAmout($this->_userInfo->userId);
				
                /*count total amount of prevous unpaid transaction------*/ 
                if($this->_userInfo->creditLimit == 0){
                            $limit = 'Unlimited';
                            $netLimit = 'Unlimited';
                    }else{
                            $limit = number_format($this->_userInfo->creditLimit,2);
                            $netLimit = $limit - $outstandingAmount;
                            $netLimit = number_format($netLimit,2);
                    }
                //$superTotal = $cart['grandTotal']+$outstandingAmount;
				$superTotal = $items[0]['orderTotal'] + $outstandingAmount;
				
                if(($this->_userInfo->creditLimit != 0) AND ($this->_userInfo->creditLimit <  $superTotal )){
                    echo $superTotal.$limit;

                    $this->_helper->redirector('postpaidlimit');
                    echo 'Credit Limit Reached, Please Contact Our Billing';

                /*====================VALIDATE CREDIT LIMIT=====================*/
                } else {

					/*view file =index.phtml*/
                    $this->view->type = "postpaid";
					$this->view->limit = $limit;
					$this->view->outstandingAmount = $outstandingAmount;
					$this->view->grandTotal = $items[0]['orderTotal'];
					$this->view->netLimit = $netLimit;
					$this->view->taxInfo = $items[0];
					$this->view->orderId = $orderId;
					//var_dump($items[0]);die();

                }
                break;
		}
		
		//var_dump($items[0]);
		
		//die();
		
	}
	
    public function successAction()
	{
        
    }
    
	public function verificationAction(){		
        /*
         - check payment type use switch if necessary (paypal, twoco, manual )
         - use verification function from existing library of paypal/twoco
         - set order status if verified
         - redirect to proper page? or trigger mail?          
        */
        
        
        // Create an instance of the paypal library
		require_once('PaymentGateway/Paypal.php');
        $myPaypal = new Paypal();
        
        // Log the IPN results
        $myPaypal->ipnLog = TRUE;
        
        // Enable test mode if needed
		if($this->_testMode){
            $myPaypal->enableTestMode();
        }
        // Check validity, status, amount and tax amount and write down it
        if ($myPaypal->validateIpn())
        {	
            //if ($myPaypal->ipnData['payment_status'] == 'Completed' && $myPaypal['']=='')
			if ($myPaypal->ipnData['payment_status'] == 'Completed')
            {
				
				 $data=$myPaypal->ipnData;
                 //$this->Mailer($data['custom'], 'admin-paypal', 'admin');
				 //$this->Mailer($data['custom'], 'user-paypal', 'XXX');    
				            
				 $this->paypalsave('SUCCESS', $data);
				
				$mod = new Site_Model_Store_Mailer();
				$mod->sendReceiptToUser($data['custom'], 'paypal', 'SUCCESS PAID');
            }
            else
            {
				 $data=$myPaypal->ipnData;
                 //$this->Mailer($data['custom'], 'admin-paypal', 'admin');
				 //$this->Mailer($data['custom'], 'user-paypal', 'admin');                 
				 $this->paypalsave('FAILED', $data);
				
				$mod = new Site_Model_Store_Mailer();
				$mod->sendReceiptToUser($data['custom'], 'paypal', 'FAILED');
            }
        }
		else
		{
            foreach($this->_request->getParams() as $key=>$val){
				$data[$key] = $val;
			}
			//all data and key are same with ipnData
			//$this->Mailer($data['custom'], 'admin-paypal', 'admin');
			//send all post variables to admin email 
			$writer = new Zend_Log_Writer_Stream(ROOT_PATH.'/app_log.txt');
			$logger = new Zend_Log($writer);

			$logger->info(var_dump($data));
        }   
    	
		die();
    }
    
    public function instructionAction(){
		
		$this->_checkAuth();
		
		$orderId = $this->_request->getParam('orderId');
		
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$row = $tblOrder->find($orderId)->current();
		if(empty($row))
			die('NO ORDER DATA AVAILABLE');
			
		//var_dump($rowset);
		
		$this->view->row = $row;
		
		$_SESSION['jCart'] = null;         
    }
    
	
	public function postpaidAction(){
		$this->_checkAuth();
		$orderId = $this->_request->getParam('orderId');
		if(empty($orderId))
		{
			die('NO ORDER ID');
		}
        
		//setting payment and status as postpaid (5), notify = 0, notes = 'paid with...'
		$this->updateInvoiceMethod($orderId, 'postpaid', 5, 0, 'paid with postpaid method');
		
		
		//Send receipt for postpaid transaction
		$mod = new Site_Model_Store_Mailer();
		$mod->sendReceiptToUser($orderId, 'postpaid', 'POSTPAID RECORDED');
		
		//$this->Mailer($orderId, 'admin-order', 'admin');
		//$this->Mailer($orderId, 'user-order', 'user');
		
		
		$this->_helper->redirector('postpaidsuccess');
	}

	public function postpaidsuccessAction(){
		$this->_checkAuth();
        $userId = $this->_userInfo->userId;
		
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$rowset = $tblOrder->getLastOrder($userId);
		$this->view->rowset = $rowset;
		//print_r($rowset);
		unset($_SESSION['_orderIdNumber']);
		$_SESSION['jCart'] = '';  
	}
	public function postpaidlimitAction(){
       $this->_checkAuth();
	}
	public function notpostpaidAction(){
        $this->_checkAuth();
    }
    
	
	public function listAction(){
		$this->_checkAuth();
		$r = $this->getRequest();
		
		$limit = ($r->getParam('limit'))?$r->getParam('limit'):10;
		$this->view->limit =$limit;
		$itemsPerPage = $limit;
		$this->view->itemsPerPage = $itemsPerPage;
		$offset = ($r->getParam('offset'))?$r->getParam('offset'):0;
		$this->view->offset = $offset;
		$Query = (($r->getPost('Query')))?$r->getPost('Query'):$r->getParam('Query');
		$this->view->Query = $Query;
		$query ='';
		if(!empty($Query)){
			$query = " AND KOD.documentName LIKE '%$Query%' ";
		}
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		//echo '<pre>';
		//var_dump($this);
        $where=$this->_userInfo->userId;
		$rowsetTotal = $tblOrder->countOrders ($query,"'".$where."'");
		$rowset = $tblOrder->getOrderSummary($query,"'".$where."'",$limit,$offset);

        $this->view->numCount = $rowsetTotal;
		$this->view->listOrder = $rowset;
		//print_r($this->_request->getParams());
	}
    public function detailAction(){
        $this->_checkAuth();
        $orderId = $this->_request->getParam('id');
        $tblOrder = new Kutu_Core_Orm_Table_Order();
		$rowset = $tblOrder->getOrderAndStatus($orderId);
		$this->view->listOrder = $rowset;
		$tblOrderDetail = new Kutu_Core_Orm_Table_OrderDetail();
		$rowsetDetail = $tblOrderDetail->fetchAll($tblOrderDetail->select()->where("orderId='".$orderId."'"));
		$this->view->listOrderDetail = $rowsetDetail;
	}
	public function searchAction(){
		$this->_checkAuth();
        $r = $this->getRequest();
		$sQuery = $r->getParam('sQuery');
		$this->view->sQuery = $sQuery;
		$sLimit = ($r->getParam('sLimit'))?$r->getParam('sLimit'):10;
		$this->view->sLimit =$sLimit;
		$itemsPerPage = $sLimit;
		$this->view->itemsPerPage = $itemsPerPage;
		$sOffset = ($r->getParam('sOffset'))?$r->getParam('sOffset'):0;
		$this->view->sOffset = $sOffset;
		
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$where="'".$this->_userInfo->userId."'
        AND (KO.orderId LIKE '%" . $sQuery . "%'
		OR invoiceNumber LIKE '%" . $sQuery . "%'
		OR taxNumber LIKE '%" . $sQuery . "%'
		OR taxCompany LIKE '%" . $sQuery . "%'
		OR taxAddress LIKE '%" . $sQuery . "%'
		OR taxCity LIKE '%" . $sQuery . "%'
		OR taxZip LIKE '%" . $sQuery . "%'
		OR taxProvince LIKE '%" . $sQuery . "%'
		OR taxCountryId LIKE '%" . $sQuery . "%'
		OR telephone LIKE '%" . $sQuery . "%'
		OR paymentMethod LIKE '%" . $sQuery . "%'
		OR paymentMethodNote LIKE '%" . $sQuery . "%'
		OR orderStatus LIKE '%" . $sQuery . "%'
		OR currency LIKE '%" . $sQuery . "%'
		OR currencyValue LIKE '%" . $sQuery . "%'
		OR orderTotal LIKE '%" . $sQuery . "%'
		OR orderTax LIKE '%" . $sQuery . "%'
		OR paypalIpnId LIKE '%" . $sQuery . "%'
		OR ipAddress LIKE '%" . $sQuery . "%') ";
        $rowset = $tblOrder->getOrderSummary($where, $sLimit, $sOffset);
        $rowsetTotal = $tblOrder->countOrders($where);

		$this->view->numCount = $rowsetTotal;
        $this->view->listOrder = $rowset;
        //print_r($r->getParams());
	}
	public function transactionAction(){
		$this->_checkAuth();	
		$r = $this->getRequest();
		$limit = ($r->getParam('limit'))?$r->getParam('limit'):10;
		$this->view->limit =$limit;
		$itemsPerPage = $limit;
		$this->view->itemsPerPage = $itemsPerPage;
		$offset = ($r->getParam('offset'))?$r->getParam('offset'):0;
		$this->view->offset = $offset;
		$Query = (($r->getPost('Query')))?$r->getPost('Query'):$r->getParam('Query');
		$this->view->Query = $Query;
		$query ='';
		if(!empty($Query)){
			$query = " AND KOD.documentName LIKE '%$Query%' ";
		}
		
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		
		$rowsetTotal = $tblOrder->countOrders ($query,"'".$this->_userInfo->userId."' AND (orderStatus = 3 OR orderStatus = 5)");
        $where="'".$this->_userInfo->userId."' AND (orderStatus = 3 OR orderStatus = 5)";
		$rowset = $tblOrder->getOrderSummary($query,$where,$limit,$offset);
		
        $this->view->numCount = $rowsetTotal;
		$this->view->listOrder = $rowset;
		//print_r($this->_request->getParams());
	}
	public function billingAction(){
		$this->_checkAuth();
        $userFinance = new Kutu_Core_Orm_Table_UserFinance();
		$userId = @$this->_userInfo->userId;
		$rowset = $userFinance->getUserFinance($userId);
		/*echo '<pre>';
		print_r($rowset);
		echo '</pre>';*/
		$tblOrder = new Kutu_Core_Orm_Table_Order(); 
		//table kutuOrder
		//select previous transaction that are postpaid based on userid
		$outstandingAmount = @$tblOrder->outstandingUserAmout($this->_userInfo->userId);
		$this->view->rowset = $rowset;
		$this->view->outstandingAmount = $outstandingAmount;
		//print_r($this->_request->getParams());
		if($this->_request->isPost('save')){
			$data['taxNumber'] = $this->_request->getParam('taxNumber');
			$data['taxCompany'] = $this->_request->getParam('taxCompany');
			$data['taxAddress'] = $this->_request->getParam('taxAddress');
			$data['taxCity'] = $this->_request->getParam('taxCity');
			$data['taxProvince'] = $this->_request->getParam('taxProvince');
			$data['taxZip'] = $this->_request->getParam('taxZip');
			$data['taxCountryId'] = $this->_request->getParam('taxCountryId');
			$where = "userId = '".$userId."'";
			$userFinance->update($data,$where);
			$this->_helper->redirector('bilupdsuc');
		}else{
			//echo 'falset';
		}
	}
	public function bilupdsucAction(){
		$this->_checkAuth();
        $this->_helper->redirector('billing');
	}
	public function documentAction(){
		$this->_checkAuth();
		$r = $this->getRequest();
		$limit = ($r->getParam('limit'))?$r->getParam('limit'):10;
		$this->view->limit =$limit;
		$itemsPerPage = $limit;
		$this->view->itemsPerPage = $itemsPerPage;
		$offset = ($r->getParam('offset'))?$r->getParam('offset'):0;
		$this->view->offset = $offset;
		
		if($this->_request->get('Query')){
			$where = $r->getParam('Query');
			$this->view->Query = $where;
		}else{
			$where = ' ';
			$this->view->Query = 'search base on document name';
		}
		
		$tblOrder = new Kutu_Core_Orm_Table_Order(); 
		$userId = $this->_userInfo->userId;
		
		$rowset = $tblOrder->getDocumentSummary($userId, $where, $limit, $offset );
		$rowsetTotal = $tblOrder->countDocument($userId, $where);
		
		//print_r($r->getParams());
		$this->view->numCount = $rowsetTotal;
		$this->view->rowset = $rowset;
	}
	public function trdetailAction(){
	    $this->_checkAuth();
        $orderId = $this->_request->getParam('id');
		$userId = $this->_userInfo->userId;
        
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$tblOrderDetail = new Kutu_Core_Orm_Table_OrderDetail();
		$tblOrderHistory = new Kutu_Core_Orm_Table_OrderHistory();
		$tblOrderPaypalHistory = new Kutu_Core_Orm_Table_PaypalPaymentHistory();
		
		$rowset = $tblOrder->getOrderAndStatus($orderId);
		$rowsetDetail = $tblOrderDetail->fetchAll($tblOrderDetail->select()->where("orderId='".$orderId."'"));
		$rowsetHistory = $tblOrderHistory->getUserHistory($orderId);
		$rowsetPaypalHistory = $tblOrderPaypalHistory->fetchAll($tblOrderPaypalHistory->select()->where("orderId='".$orderId."'"));
		
		//print_r($rowsetHistory);
		$this->view->listOrder = $rowset;
		$this->view->listOrderDetail = $rowsetDetail;
		$this->view->rowsetHistory = $rowsetHistory;		
		$this->view->rowsetPaypalHistory = $rowsetPaypalHistory;		
	}
	public function paypalsave($status, $dataPaypal = array()){
		
		$tblOrder = new Kutu_Core_Orm_Table_Order();        
        
        $orderId = $dataPaypal['custom'];//$_SESSION['_orderIdNumber'];//$this->_orderIdNumber;//$data['custom'];
        
		//echo $orderId;
        //var_dump($dataPaypal);
        //print_r($data['custom']);
        $dataPrice = $tblOrder->fetchAll($tblOrder->select()->where('orderId = '.$orderId));
        
		if($dataPrice[0]->orderTotal == $dataPaypal['mc_gross']){	
            $payStatus = 3; //paid - completed
        }else{
            $payStatus = 7; //payment error
        }
		
       
		$tblPaypal = new Kutu_Core_Orm_Table_Paypal();
		$data = $tblPaypal->fetchNew();
		
		$data->orderId= $orderId;
		$data->mcGross  = $dataPaypal['mc_gross']; 
        $data->addressStatus  = $dataPaypal['address_status'];  
		$data->payerId  = $dataPaypal['payer_id'];  
		$data->addressStreet  = $dataPaypal['address_street'];  
		$data->paymentDate  = $dt = date('Y-m-d H:i:s', strtotime($dataPaypal['payment_date']));
		$data->paymentStatus  = $status; 						
		$data->addressZip  = $dataPaypal['address_zip']; 
		$data->firstName  = $dataPaypal['first_name'];  
		$data->mcFee  = $dataPaypal['mc_fee'];
		$data->addressName  = $dataPaypal['address_name']; 
		$data->notifyVersion  = $dataPaypal['notify_version']; 
		$data->payerStatus  = $dataPaypal['payer_status']; 
		$data->addressCountry  = $dataPaypal['address_country']; 
		$data->addresCity  = $dataPaypal['address_city'];  
		$data->payerEmail  = $dataPaypal['payer_email'];  
		$data->verifySign  = $dataPaypal['verify_sign']; 
		$data->paymentType  = $dataPaypal['payment_type'];
		$data->txnId  = $dataPaypal['txn_id'];  
		$data->lastName  = $dataPaypal['last_name'];  
		$data->receiverEmail  = $dataPaypal['receiver_email'];  
		$data->addressState  = $dataPaypal['address_state']; 
		$data->receiverId  = $dataPaypal['receiver_id']; 
		$data->txnType  = $dataPaypal['txn_type'];  
		$data->mcCurrency  = $dataPaypal['mc_currency']; 
		$data->paymentGross  = $dataPaypal['payment_gross']; 
		$data->paymentFee  = $dataPaypal['payment_fee']; 
		$data->numCartItems  = (isset($dataPaypal['num_cart_items']))?$dataPaypal['num_cart_items']:'1';
		$data->business  = $dataPaypal['business'];
		$data->parentTxnId  = $dataPaypal['txn_id'];
		$data->lastModified = date('Y-m-d');
		$data->dateAdded = date('Y-m-d');
		
		try
		{
			$paypalIpnId = $data->save();
		}
		catch(Exception $e)
		{
			$writer = new Zend_Log_Writer_Stream(ROOT_PATH.'/app_log.txt');
			$logger = new Zend_Log($writer);

			$logger->info($e->getMessage());
		}
        
		//echo($tblPaypal->getLastInsertId());
		
        $paypalHistory = new Kutu_Core_Orm_Table_PaypalPaymentHistory();
		$row = $paypalHistory->fetchNew();
		$row->paypalIpnId = $paypalIpnId;
        $row->orderId = $orderId;
		$row->txnId = $this->_request->getParam('txn_id');
		$row->parentTxnId = $this->_request->getParam('txn_id');
		$row->paymentStatus = $this->_request->getParam('payment_status');
		$row->dateAdded = date('Y-m-d');
		$row->save();
        
        $this->updateInvoiceMethod($orderId, 'paypal', $payStatus, 0, 'paid with paypal method');
	}
    public function confirmAction(){
        $this->_checkAuth();
        $userId = $this->_userInfo->userId;
        $tblOrder = new Kutu_Core_Orm_Table_Order();
        $tblSetting = new Kutu_Core_Orm_Table_PaymentSetting();
        
        $rowset = $tblOrder->getTransactionToConfirm($userId);//,$limit,$offset);
        $numCount = $tblOrder->getTransactionToConfirmCount($userId);
        $bankAccount = $tblSetting->fetchAll($tblSetting->select()->where("settingKey = 'bankAccount'"));
        
		if($this->_request->get('sended') == 1){
			$this->view->sended = 'Payment Confirmation Sent';
		}
        $this->view->numCount = $numCount;
        $this->view->rowset = $rowset;
        $this->view->bankAccount = $bankAccount;
		/*echo'<pre>';
		print_r($this->_userDetailInfo);
        echo'</pre>';*/
    }
    public function payconfirmAction()
	{
	
		$this->_checkAuth();
	
		//if there is orderId send by previous page
		$tmpOrderId = $this->_request->getParam('orderId');

		if(empty($tmpOrderId))
		{
			$this->_helper->redirector->gotoSimple('error', 'store', 'site',array('view'=>'noorderfound'));
			die();
		}
		
		//[TODO]
		// 1. must check if user who sent the confirmation is the user who own the orderId.
		// 2. if no.1 above return false for at least one orderId, then forward to Error Page.
		
		$modelAppStore = new App_Model_Store();
		foreach($this->_request->getParam('orderId') as $key=>$value)
		{
			if(!$modelAppStore->isUserOwnOrder($this->_userDetailInfo->guid, $value))
			{
				//forward to error page
				$this->_helper->redirector->gotoSimple('error', 'store', 'site',array('view'=>'notowner'));
				die();
			}
			
		}
		
		//if orderId status is PAID redirect to error page
		
		//die('here');
		
		
		$tblConfirm = new Kutu_Core_Orm_Table_PaymentConfirmation();
		$tblOrder = new Kutu_Core_Orm_Table_Order();
        $r = $this->getRequest();
		
		$amount = 0;
		//var_dump($r->getParam('orderId'));
		//die();
		foreach($r->getParam('orderId') as $ksy=>$value){
			$amount += $tblOrder->getAmount($value,($r->getParam('currency')));
		}
		foreach($r->getParam('orderId')as $key=>$row){
			
			$data = $tblConfirm->fetchNew();
			
			$data['paymentMethod'] = $r->getParam('paymentMethod');
			$data['destinationAccount'] = $r->getParam('destinationAccount');
			$data['paymentDate'] = $r->getParam('paymentDate');
			$data['amount'] = $amount;
			$data['currency'] = $r->getParam('currency');
			$data['senderAccount'] = $r->getParam('senderAccount');
			$data['senderAccountName'] = $r->getParam('senderAccountName');
			$data['bankName'] = $r->getParam('bankName');
			$data['note'] = $r->getParam('note');
			$data['orderId'] = $row;
			$data->save();
			
			$statdata['orderStatus'] = 4;
			$tblOrder->update($statdata, 'orderId = '.$data['orderId']);
			
			$tblHistory = new Kutu_Core_Orm_Table_OrderHistory;
			
			//add history
			$dataHistory = $tblHistory->fetchNew();
			//history data
			$dataHistory['orderId'] = $data['orderId']; 

			$dataHistory['orderStatusId'] = 6; 
			$dataHistory['dateCreated'] = date('Y-m-d'); 
			$dataHistory['userNotified']   = 1; 
			$dataHistory['note'] = 'Waiting Confirmation'; 
			$dataHistory->save();
			
			//$this->Mailer($data['orderId'], 'admin-confirm', 'admin');
			$mod = new App_Model_Store_Mailer();
			$mod->sendUserBankConfirmationToAdmin($data['orderId']);
		}
		$this->_helper->redirector->gotoSimple('confirm', 'store_payment', 'site', array('sended' => '1'));
	}
    
	public function Mailer($idOrder, $key, $userTo)
	{
        $modMailer = new App_Model_Store_Mailer();
		$modMailer->mailer($idOrder, $key, $userTo);
    }
    
    private function _checkAuth(){
        $auth =  Zend_Auth::getInstance();
        $userId=$auth->getIdentity()->guid;
        if(!$auth->hasIdentity()){
                $this->_redirect(KUTU_ROOT_URL.'/helper/sso/login'.'?returnTo='.$sReturn);
        }else{
            // [TODO] else: check if user has access to admin page
            $username = $auth->getIdentity()->username;
            $this->view->username = $username;
        }
        //$userId=$auth->getIdentity()->guid;
		$tblUser= new Kutu_Core_Orm_Table_User();
        $this->_userDetailInfo=$tblUser->find($userId)->current(); 
		
        $tblUserFinance= new Kutu_Core_Orm_Table_UserFinance();
		$this->_userInfo=$tblUserFinance->find($userId)->current();
        if(empty($this->_userInfo)){
			$finance = $tblUserFinance->fetchNew();
			$finance['userId'] = $userId;
			$finance->save();
			$this->_userInfo=$tblUserFinance->find($userId)->current();
		}
		
		
    }
	
	function testmodelAction()
	{
		//$mod = new Site_Model_Store_Mailer();
		//$mod->testEcho();
		//$ret = Kutu_Application::getResource('jcart');
		//var_dump($ret);
		
		$front = Zend_Controller_Front::getInstance();
		$bootstrap = $front->getParam('bootstrap');
		$resource = $bootstrap->getPluginResource('modules');
		$moduleBootstraps = $resource->getExecutedBootstraps();
		$newsBootstrap = $moduleBootstraps['site'];
		
		//var_dump($newsBootstrap->getOptions());
		//var_dump($newsBootstrap->getResource('jcart'));
		
		$jcart = Kutu_Application::getResource('jcart1',$this->_request->getModuleName());
		var_dump($jcart);
		
		die();
	}
}