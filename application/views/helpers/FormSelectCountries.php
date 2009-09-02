<?php
class App_View_Helper_FormSelectCountries extends Zend_View_Helper_FormSelect 
{
    
    public function formSelectCountries($elementName="countryId", $selectedValue)
    {
		$config = new Zend_Config_Xml(CONFIG_PATH.'/countries.xml','countries');
		$aCountries = array();
		foreach($config->get('country') as $country)
		{
			//echo $country->name." ($country->alpha2)<br>";
			$aCountries[$country->alpha2] = $country->name;
		}
		
		return $this->formSelect($elementName, $selectedValue, null , $aCountries);

    }
    
}
?>