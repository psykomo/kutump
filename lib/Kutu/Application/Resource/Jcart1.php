<?php
class Kutu_Application_Resource_Jcart1 extends Zend_Application_Resource_ResourceAbstract
{
	public function init()
    {
		require_once(KUTU_ROOT_DIR.'/lib/jcart/jcart.php');
		
		$options = array_change_key_case($this->getOptions(), CASE_LOWER);
		//$options1 = $this->getOptions();
		
		//var_dump($options);die();
		return $options;
    }
}
?>