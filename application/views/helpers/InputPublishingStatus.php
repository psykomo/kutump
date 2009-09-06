<?php
class App_View_Helper_InputPublishingStatus extends Zend_View_Helper_FormSelect 
{
    
    public function inputPublishingStatus($elementName="status", $selectedValue)
    {
		require_once(CONFIG_PATH.'/master-status.php');
		$aStatus = MasterStatus::getPublishingStatus();
		
		return $this->formSelect($elementName, $selectedValue, null , $aStatus);

    }
    
}
?>