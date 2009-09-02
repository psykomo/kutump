<?php
class App_Model_Store
{
	function test()
	{
		echo 'test'; die();
	}
	
	//this function will return true if userId supplied is the same with userId in specified Order
	function isUserOwnOrder($userId, $orderId)
	{
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$rowOrder = $tblOrder->find($orderId)->current();
		if(!empty($rowOrder))
		{
			if($userId == $rowOrder->userId)
				return true;
			else
				return false;
		}
		else
			return false;
	}
	function isOrderPaid($orderId)
	{
		$tblOrder = new Kutu_Core_Orm_Table_Order();
		$rowOrder = $tblOrder->find($orderId)->current();
		if(!empty($rowOrder))
		{
			if($rowOrder->orderStatus==3)
				return true;
			else
				return false;
		}
		else
			return false;
	}
}
?>