<?php
class Kutu_Core_Orm_Table_PaymentConfirmation extends Zend_Db_Table_Abstract 
{
	protected $_name = 'KutuPaymentConfirmation';
	
	function unconfirmListCount($where){
    	$db = $this->_db->query("Select count(paymentId) AS count 
                                FROM 
									KutuPaymentConfirmation AS KPC, 
									KutuOrder AS KO, 
									KutuUser AS KU
								WHERE 
									KO.orderId = KPC.orderId 
								AND 
									KU.guid = KO.userId
								AND 
									confirmed = 0
								$where");
    	$dataFetch = $db->fetchAll(Zend_Db::FETCH_ASSOC);
    	
    	return ($dataFetch[0]['count']);
    }	
	public function unconfirmList($where, $limit, $offset){
        $db = $this->_db->query("SELECT 
									KPC.*,KO.*, KU.*, KOS.ordersStatus 
								FROM 
									KutuPaymentConfirmation AS KPC, 
									KutuOrder AS KO, 
									KutuUser AS KU,
									KutuOrderStatus AS KOS
								WHERE 
									KO.orderId = KPC.orderId 
								AND 
									KU.guid = KO.userId
								AND 
									confirmed = 0
								AND
									KO.orderStatus = KOS.orderStatusId
								$where
                                LIMIT $offset, $limit");
    	$dataFetch = $db->fetchAll(Zend_Db::FETCH_ASSOC);
        $data  = array(
            'table'    => $this,
            'data'     => $dataFetch,
            'rowClass' => $this->_rowClass,
            'stored'   => true
        );

        Zend_Loader::loadClass($this->_rowsetClass);
        return new $this->_rowsetClass($data);
	}
}