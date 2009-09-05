<?php
class Site_StoreController extends Zend_Controller_Action
{
	protected $_userInfo;
	protected $_configStore;
	protected $_userId;
	
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
		$userId=$auth->getIdentity()->guid;
		$this->_userId = $userId;
		
		$tblUserFinance= new Kutu_Core_Orm_Table_UserFinance();
		$this->_userInfo=$tblUserFinance->find($userId)->current();
		
		$storeConfig = Kutu_Application::getOption('store');
		$this->_configStore = $storeConfig;
		
	}
	public function checkoutAction()
	{
		$auth =  Zend_Auth::getInstance();
        $userId=$auth->getIdentity()->guid;

		$tblUser= new Kutu_Core_Orm_Table_User();
        $userDetailInfo = $tblUser->find($userId)->current(); 
		
        $tblUserFinance= new Kutu_Core_Orm_Table_UserFinance();
		$userFinanceInfo = $tblUserFinance->find($userId)->current();
        if(empty($userFinanceInfo))
		{
			$finance = $tblUserFinance->fetchNew();
			$finance->userId = $userId;
			$finance->taxNumber = '';
			$finance->taxCompany = $userDetailInfo->company;
			$finance->taxAddress = $userDetailInfo->mainAddress;
			$finance->taxCity = $userDetailInfo->city;
			$finance->taxProvince = $userDetailInfo->state;
			$finance->taxCountryId = $userDetailInfo->countryId;
			$finance->taxZip = $userDetailInfo->zip;
			$finance->save();
		}
		
		$userFinanceInfo = $tblUserFinance->find($userId)->current();
		
		//print_r($_POST);
		$cart =& $_SESSION['jCart']; if(!is_object($cart)) $cart = new jCart();
		$this->view->cart = $cart;
		//print_r($this->_userInfo);
		$this->view->userInfo = $userFinanceInfo;
		
		//if($this->_configStore->store->isClosed)
		if($this->_isStoreClosed())
			$this->_forward('closed','store','site');
	}
	public function confirmorderAction()
	{
		//this page will do:
		//1. show all details that has been previously inputted
		//2. calculate TAX, and show it to user
		//3. GRAND TOTAL PRICE
		
		//check if jcart is empty. If empty it means user has not put any items into cart or user has just finished complete order,
		// but then going back to confirm order page.
		//var_dump($_SESSION['jCart']);
		if(!is_object($_SESSION['jCart']))
		{
			//forward to somewhere
			echo "FORWARDED";
			$this->_helper->redirector('cartempty','store_payment','site');	
		}
		if(count($_SESSION['jCart']->items)==0)
		{
			//forward to somewhere
			echo "SHOULD BE FORWARDED";
			$this->_helper->redirector('cartempty','store','site');	
		}
		
		$tblPaymentSetting = new Kutu_Core_Orm_Table_PaymentSetting();        
        $rowTaxRate = $tblPaymentSetting->fetchRow("settingKey='taxRate'");
		
		$cart =& $_SESSION['jCart']; if(!is_object($cart)) $cart = new jCart();
		
		$bpm = new Kutu_Core_Bpm_Catalog();
        $result = array('subTotal' => 0, 'taxAmount' => 0, 'grandTotal'=> 0,'items'=>array()); 
        for($iCart=0;$iCart<count($cart->items);$iCart++)
		{
            $itemId=$cart->items[$iCart];
            $qty= 1;
            $itemPrice=$bpm->getPrice($itemId);
            //$itemPrice=20;
            $result['items'][$iCart]['itemId']= $itemId;
            $result['items'][$iCart]['item_name'] = Kutu_Core_Util::getCatalogAttributeValue($itemId,'fixedTitle'); 
            $result['items'][$iCart]['itemPrice']= $itemPrice;
            $result['items'][$iCart]['qty']= $qty;
            $result['subTotal']+=$itemPrice*$qty;
		}
        $result['taxAmount']= $result['subTotal']*$rowTaxRate->settingValue/100;
        $result['grandTotal'] = $result['subTotal']+$result['taxAmount'];
		
		$data = array();
		foreach($this->_request->getParams() as $key=>$value){
			$data[$key] = $value;
		}
		$this->view->cart = $result;
		
		$this->view->data = $data;
		
		if($data['method']=='postpaid')
		{
			$tblUserFinance= new Kutu_Core_Orm_Table_UserFinance();
			$userFinanceInfo = $tblUserFinance->find($this->_userId)->current();
			if(!$userFinanceInfo->isPostPaid){
	            echo 'Not Post Paid Customer';
	            //$paymentObject->submitPayment();
	            return $this->_helper->redirector('notpostpaid','store_payment','site');
	        }
		}
		
	}
	public function completeorderAction()
	{
		//this is where we generate invoice
		// and then empty cart
		// and then if payment method is paypal, ask if user wants to continue to pay via paypal.
		// if payment method is manual, then print-out the payment instruction.
		// send invoice to user.
		
		
		$tblPaymentSetting = new Kutu_Core_Orm_Table_PaymentSetting();        
        $rowTaxRate = $tblPaymentSetting->fetchRow("settingKey='taxRate'");
		
		
		$cart =& $_SESSION['jCart']; if(!is_object($cart)) $cart = new jCart();
		
		if(empty($cart) || count($cart->items)==0)
		{	
			//die("CART IS EMPTY");
			$this->_helper->redirector('cartempty','store','site');
		}
		
		$bpm = new Kutu_Core_Bpm_Catalog();
        $result = array('subTotal' => 0, 'taxAmount' => 0, 'grandTotal'=> 0,'items'=>array()); 
        for($iCart=0;$iCart<count($cart->items);$iCart++)
		{
            $itemId=$cart->items[$iCart];
            $qty= 1;
            $itemPrice=$bpm->getPrice($itemId);
            //$itemPrice=20;
            $result['items'][$iCart]['itemId']= $itemId;
            $result['items'][$iCart]['item_name'] = Kutu_Core_Util::getCatalogAttributeValue($itemId,'fixedTitle'); 
            $result['items'][$iCart]['itemPrice']= $itemPrice;
            $result['items'][$iCart]['qty']= $qty;
            $result['subTotal']+=$itemPrice*$qty;
		}
        $result['taxAmount']= $result['subTotal']*$rowTaxRate->settingValue/100;
        $result['grandTotal'] = $result['subTotal']+$result['taxAmount'];

        $method = $this->_request->getParam('paymentMethod');
		$orderId = $this->saveOrder($result,$method);
		
		$cart = null;
		
		$data = $this->_request->getParams();
		
		$this->view->cart = $result;
		
		$this->view->data = $data;
		
		$this->view->orderId = $orderId;
		
		//send notification to user
		// 1. send invoice
		// 2. if paymentmethod=bank, also send instruction
		
		$mod = new Site_Model_Store_Mailer();
		
		switch(strtolower($method))
		{
			case 'manual':
			case 'bank':
				$mod->sendBankInvoiceToUser($orderId);
				break;
			case 'paypal':
				$mod->sendInvoiceToUser($orderId);
				break;
			case 'postpaid':
				$mod->sendInvoiceToUser($orderId);
				break;
		}
		
		
	}
	public function viewinvoiceAction()
	{
		$orderId = $this->_request->getParam('orderId');
		
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$items = $tblOrder->getOrderDetail($orderId);
		
		$this->view->orderId = $orderId;
		$this->view->invoiceNumber = $items[0]['invoiceNumber'];
		
		$tblPaymentSetting = new Kutu_Core_Orm_Table_PaymentSetting();        
        $rowTaxRate = $tblPaymentSetting->fetchRow("settingKey='taxRate'");
		
		//var_dump($items);
		//die();
		
		if($this->_userId != $items[0]['userId'])
		{
			//forward to somewhere
			//echo "SHOULD BE FORWARDED";
			$this->_helper->redirector('cartempty','store','site');	
		}
		
		
		
		$result = array();
		$result['subTotal'] = 0;
		for($iCart=0;$iCart<count($items);$iCart++){
            
			$itemId = $items[$iCart]['itemId'];
            $qty= 1;
            $itemPrice = $items[$iCart]['price'];
            
            $result['items'][$iCart]['itemId']= $itemId;
            $result['items'][$iCart]['item_name'] = $items[$iCart]['documentName']; 
            $result['items'][$iCart]['itemPrice']= $itemPrice;
            $result['items'][$iCart]['qty']= $qty;
            $result['subTotal'] += $itemPrice*$qty;
        }

		$result['taxAmount']= $result['subTotal'] * $rowTaxRate->settingValue/100;
        $result['grandTotal'] = $result['subTotal'] + $result['taxAmount'];

		$this->view->cart = $result;
		
		$data = array();
		$data['taxNumber'] = $items[0]['taxNumber'];
		$data['taxCompany'] = $items[0]['taxCompany'];
		$data['taxAddress'] = $items[0]['taxAddress'];
		$data['taxCity'] = $items[0]['taxCity'];
		$data['taxZip'] = $items[0]['taxZip'];
		$data['taxProvince'] = $items[0]['taxProvince'];
		$data['taxCountry'] = $items[0]['taxCountryId'];
		$data['paymentMethod'] = $items[0]['paymentMethod'];
		$data['currencyValue'] = $items[0]['currencyValue'];
		
		$this->view->data = $data;
	}
	
	private function saveOrder($cart,$method)
	{
		//[TODO] this value should be put into table KutuPaymentSetting
		$defaultCurrency='USD';
		
		$tblPaymentSetting = new Kutu_Core_Orm_Table_PaymentSetting();
		$usdIdrEx = $tblPaymentSetting->fetchRow(" settingKey= 'USDIDR'");
		$currencyValue = $usdIdrEx->settingValue;      
        $rowTaxRate = $tblPaymentSetting->fetchRow("settingKey='taxRate'");
		$taxRate = $rowTaxRate->settingValue;
		//die($currencyValue);
		
		$auth =  Zend_Auth::getInstance();
		$userId=$auth->getIdentity()->guid;
		
		$tblOrder=new Kutu_Core_Orm_Table_Order();
        $row=$tblOrder->fetchNew();
		
		$row->userId=$userId;
        
		//get value from post var (store/checkout.phtml)
		if($this->getRequest()->getPost()){
				$value = $this->getRequest()->getPost(); 
				// get posted value
				
				$row->taxNumber=$value['taxNumber'];
				$row->taxCompany=$value['taxCompany'];
				$row->taxAddress=$value['taxAddress'];
				$row->taxCity=$value['taxCity'];
				$row->taxZip=$value['taxZip'];
				$row->taxProvince=$value['taxProvince'];
				$row->taxCountryId=$value['taxCountry'];
				$row->paymentMethod=$method;
        }
        $row->datePurchased=date('YmdHis');
        $row->orderStatus=1; //pending
        $row->currency = $defaultCurrency;        
        $row->currencyValue = $currencyValue;        
        $row->orderTotal=$cart['grandTotal'];
        $row->orderTax=$cart['taxAmount'];
        $row->ipAddress= $this->getRealIpAddress();
		/*echo '<pre>';
		//print_r($row);
		echo '</pre>';*/
        $orderId = $row->save();

		//$orderId = $tblOrder->getLastInsertId();
		
        $rowJustInserted = $tblOrder->find($orderId)->current();
		$rowJustInserted->invoiceNumber = date('Ymd') . '.' . $orderId;
		$rowJustInserted->save();
		
		$this->view->invoiceNumber = $rowJustInserted->invoiceNumber;
        
        $tblOrderDetail=new Kutu_Core_Orm_Table_OrderDetail();
        
		for($iCart=0;$iCart<count($cart['items']);$iCart++){        
            $rowDetail=$tblOrderDetail->fetchNew();
            
            $itemId=$cart['items'][$iCart]['itemId'];        
            $rowDetail->orderId=$orderId;
            $rowDetail->itemId=$itemId;
            $rowDetail->documentName=$cart['items'][$iCart]['item_name'];
            $rowDetail->price=$cart['items'][$iCart]['itemPrice'];
			$itemPrice = $rowDetail->price;
            @$rowDetail->tax=((($cart['grandTotal']-$cart['subTotal']))/$cart['subTotal'])*100;
            $rowDetail->qty=$cart['items'][$iCart]['qty'];
            $rowDetail->finalPrice=$itemPrice + ($itemPrice * $taxRate / 100);                
            $rowDetail->save();
        }

		//[TODO] MUST ALSO INSERT/UPDATE KutuUserFinance

        return $orderId;
    }

	protected function getRealIpAddress(){
		if (!empty($_SERVER['HTTP_CLIENT_IP'])){   //check ip from share internet
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		}elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){   //to check ip is pass from proxy
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}else{
			$ip=$_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	function closedAction()
	{
		
	}
	private function _isStoreClosed()
	{
		return $this->_configStore['isClosed'];
	}
	public function cartemptyAction()
	{
		
	}
	
	public function testmodelAction()
	{
		//$mod = new Model_Store_Mailer();
		//$mod = new Store_Model_Mailer();
		//$mod->test();
		$mod = new Site_Model_Store_Mailer();
		//$mod->sendPaypalInvoiceToUser(12);
		//$mod->sendReceiptToUser(13, 'paypal', 'FAILED');
		$mod->sendUserBankConfirmationToAdmin(array(1,2));
		die();
	}
	public function errorAction()
	{
		$view = $this->_request->getParam('view');
		
		switch (strtolower($view))
		{
			case 'orderalreadypaid':
				$this->renderScript('store/error-orderalreadypaid.phtml');
				break;
			case 'noorderfound':
				$this->renderScript('store/error-noorderfound.phtml');
				break;
			case 'notowner':
			default:
				$this->renderScript('store/error-notowner.phtml');
				break;
		}
		
		
		//die();
	}

}
?>