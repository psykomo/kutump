<?php
class Kutu_Core_Orm_Table_Order extends Zend_Db_Table_Abstract 
{
	protected $_name = 'KutuOrder';

	public function getLastInsertId(){
		return $this->_db->lastInsertId();
	}
	
	public function getLastOrder($userId){
		$db = $this->_db->query("Select * FROM KutuOrder WHERE userId ='".$userId
								."' ORDER BY(orderId) DESC LIMIT 1");
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
	function countOrdersAdmin($where)
    {
    	$db = $this->_db->query("Select count(orderId) AS count 
                                FROM 
                                    KutuOrder AS KO,
                                    KutuOrderStatus AS KOS,
									KutuUser as KU
                                WHERE 
                                    KOS.orderStatusId = KO.orderStatus
								AND
									KU.guid = KO.userId
                                AND
                                     ".$where);
    	$dataFetch = $db->fetchAll(Zend_Db::FETCH_ASSOC);
    	
    	return ($dataFetch[0]['count']);
    }	
    function countOrders($query, $userId)
    {
    	$db = $this->_db->query
    	("Select count(KO.orderId) AS count From KutuOrder as KO, KutuOrderDetail AS KOD
    	where KOD.orderID =KO.orderID AND KO.userId=$userId $query");
    	
    	$dataFetch = $db->fetchAll(Zend_Db::FETCH_ASSOC);
    	
    	return ($dataFetch[0]['count']);
    }	
    function outstandingUserAmout($userId)
    {
    	$db = $this->_db->query
    	("SELECT SUM(orderTotal) AS total FROM KutuOrder where userId = '$userId' AND  orderStatus=5");
    	
    	$dataFetch = $db->fetchAll(Zend_Db::FETCH_ASSOC);
    	
    	return ($dataFetch[0]['total']);
    }
    
    public function getOrderSummaryAdmin($where,$limit,$offset){
        /*if($where==0){
            $where = " != 0";
        }else{
            $where = " = ".$where;
        }*/
        //echo $where;
        $db = $this->_db->query("SELECT KO.*,KOS.ordersStatus,
                                COUNT(itemId) AS countTotal,KU.* 
                                from
                                ((KutuOrder AS KO 
                                Left join KutuOrderDetail AS KOD 
                                    ON KOD.orderId=KO.orderId)
                                LEFT JOIN KutuUser AS KU 
                                    ON KU.guid = KO.userId)
                                LEFT JOIN KutuOrderStatus AS KOS 
                                    ON KOS.orderStatusId = KO.orderStatus
                                WHERE $where
                                GROUP BY(KO.orderId) DESC
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
	public function getAllOrderSummaryAdmin($where){
        /*if($where==0){
            $where = " != 0";
        }else{
            $where = " = ".$where;
        }*/
        //echo $where;
        $db = $this->_db->query("SELECT KO.*,KOS.ordersStatus,
                                COUNT(itemId) AS countTotal,KU.* 
                                from
                                ((KutuOrder AS KO 
                                Left join KutuOrderDetail AS KOD 
                                    ON KOD.orderId=KO.orderId)
                                LEFT JOIN KutuUser AS KU 
                                    ON KU.guid = KO.userId)
                                LEFT JOIN KutuOrderStatus AS KOS 
                                    ON KOS.orderStatusId = KO.orderStatus
                                WHERE $where
                                GROUP BY(KO.orderId) DESC");
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
    public function getOrderSummary($query, $where,$limit,$offset){
        //echo $where;
        $db = $this->_db->query("SELECT KO.*,KOS.ordersStatus,
                                COUNT(itemId) AS countTotal,KU.* 
                                FROM
                                ((KutuOrder AS KO 
                                LEFT JOIN KutuOrderDetail AS KOD 
                                    ON KOD.orderId=KO.orderId)
                                LEFT JOIN KutuUser AS KU 
                                    ON KU.guid = KO.userId)
                                LEFT JOIN KutuOrderStatus AS KOS 
                                    ON KOS.orderStatusId = KO.orderStatus
                                WHERE KO.userId = $where
								$query
                                GROUP BY(KO.orderId) DESC
                                LIMIT $offset, $limit");
        //$db = $this->_db->query();
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
	public function getDocumentSummary($userId, $where, $limit, $offset){
        $db = $this->_db->query("SELECT KOD.*, KO.datePurchased AS purchasingDate
                                FROM
                                KutuOrderDetail AS KOD,
								KutuOrder AS KO 
                                WHERE 
									KO.orderId = KOD.orderId
								AND
									userId = '$userId'
								AND
									(KO.orderStatus = 3 
									OR
									KO.orderStatus = 5)
								AND 
									documentName LIKE '%$where%'
                                LIMIT $offset, $limit");
        //$db = $this->_db->query();
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
	function countDocument($userId, $where)
    {
    	$db = $this->_db->query("SELECT count(itemId) as totalDoc
                                FROM
									KutuOrderDetail AS KOD,
									KutuOrder AS KO 
                                WHERE 
									KO.orderId = KOD.orderId
								AND
									userId = '$userId'
								AND
									(KO.orderStatus = 3 
									OR
									KO.orderStatus = 5)
								AND 
									documentName LIKE '%$where%'");
    	
    	$dataFetch = $db->fetchAll(Zend_Db::FETCH_ASSOC);
    	
    	return ($dataFetch[0]['totalDoc']);
    }
    public function getPostpaidSummary($where, $limit, $offset, $order){
        if(!empty($where)){
			$and = "AND $where";
		}else{
			$and = '';
		}
        $db = $this->_db->query("SELECT 
                                KU.*, 
                                KUF.creditlimit AS creditLimit, 
                                SUM(orderTotal) AS total
                            FROM
                                ((KutuUser AS KU
                            LEFT JOIN 
                                KutuUserFinance AS KUF 
                            ON
                                KUF.userId = KU.guid)
                            LEFT JOIN
                                KutuOrder AS KO
                            ON
                                ko.userId = KUF.userId 
                                AND paymentMethod = 'postpaid'
                                AND (orderStatus =5 OR orderStatus =4))
                            WHERE 
                                isPostPaid =1
							$and
                            GROUP BY
                                KUF.userId
                            $order
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
	public function getPostpaidCount($where){
	    if(!empty($where)){
			$and = "AND $where";
		}else{
			$and = '';
		}	
        $db = $this->_db->query("SELECT 
                                    COUNT(KU.guid) AS countPostpaid
                                FROM
                                    KutuUser AS KU
                                LEFT JOIN
                                    KutuUserFinance AS KUF 
                                ON
                                    kuf.userId = ku.guid
                                WHERE
                                    isPostPaid = 1
								");
    	
    	$dataFetch = $db->fetchAll(Zend_Db::FETCH_ASSOC);
    	
    	return ($dataFetch[0]['countPostpaid']);
    }
    public function getTransactionToConfirm($userId /*, $limit, $offset*/){
        $db = $this->_db->query("SELECT 
                                    KO.*,KOS.ordersStatus,
                                    COUNT(itemId) AS countTotal,KU.guid 
                                FROM
                                    ((KutuOrder AS KO 
                                LEFT JOIN KutuOrderDetail AS KOD 
                                    ON KOD.orderId = KO.orderId)
                                LEFT JOIN KutuUser AS KU 
                                    ON KU.guid = KO.userId)
                                LEFT JOIN KutuOrderStatus AS KOS 
                                    ON KOS.orderStatusId = KO.orderStatus
                                WHERE 
                                    KO.userId = '$userId' 
                                AND 
                                    (paymentMethod = 'bank' 
                                AND
                                    (
                                    orderStatus = 5 
									OR orderStatus = 1  
									OR orderStatus = 4
									OR orderStatus = 6
                                    ))
                                GROUP BY(KO.orderId) ASC");
                                /*LIMIT 
                                    $offset,$limit");*/
        //$db = $this->_db->query();
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
    public function getTransactionToConfirmCount($userId){
        $db = $this->_db->query("SELECT 
                                    COUNT(orderId) AS countConfirm
                                FROM
                                    KutuOrder 
                                WHERE 
                                    userId = '$userId' 
                                AND 
                                    (
                                    paymentMethod = 'bank'
                                AND
                                    (
                                    orderStatus = 5 
									OR orderStatus = 1 
									OR orderStatus = 4 
									OR orderStatus = 6 
                                    ))");
    	
    	$dataFetch = $db->fetchAll(Zend_Db::FETCH_ASSOC);
    	
    	return ($dataFetch[0]['countConfirm']);
    }
	public function getOrderAndStatus($orderId){
        $db = $this->_db->query("SELECT 
                            KO.*, KOS.* 
                            FROM
                                KutuOrder AS KO,
                                KutuOrderStatus AS KOS
                            WHERE 
                                orderStatus =orderStatusId
                            AND 
								orderId = $orderId");
        //$db = $this->_db->query();
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
	public function getLastTransactionDate($userId){
		        $db = $this->_db->query("SELECT KUF.userId, datePurchased 
										FROM KutuUserFinance AS KUF
										LEFT JOIN KutuOrder AS KO
										ON KO.userId = KUF.userId
										WHERE KUF.userId = '$userId' 
										ORDER BY datePurchased DESC 
										LIMIT 0,1");
        //$db = $this->_db->query();
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
	public function getAmount($orderId, $currency){
		if($currency == 'IDR'){
			$db = $this->_db->query("SELECT 
                                    (currencyValue*orderTotal) AS mount 
								FROM KutuOrder 
								WHERE
									orderId = $orderId");
		}else{
			$db = $this->_db->query("SELECT 
                                    orderTotal AS mount
								FROM KutuOrder 
								WHERE
									orderId = $orderId");
		}
    	
    	$dataFetch = $db->fetchAll(Zend_Db::FETCH_ASSOC);
    	
    	return ($dataFetch[0]['mount']);
	}
	public function getOrderDetail($orderId){
		$db = $this->_db->query("SELECT KO.*, KOD.*
										FROM KutuOrder AS KO
										JOIN KutuOrderDetail AS KOD
										ON KOD.orderId = KO.orderId
										WHERE KO.orderId = $orderId");
        //$db = $this->_db->query();
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