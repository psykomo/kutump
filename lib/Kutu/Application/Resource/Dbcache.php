<?php
class Kutu_Application_Resource_Dbcache extends Zend_Application_Resource_ResourceAbstract
{
	public function init()
    {
		$options = array_change_key_case($this->getOptions(), CASE_LOWER);
		if (isset($options['enable'])) 
		{
			if($options['enable'])
			{
				$frontendOptions = array(
				'lifetime' => 7200, // cache lifetime of 2 hours
			    'automatic_serialization' => true
			    );

				$backendOptions  = array(
				    //'cache_dir'                => KUTU_ROOT_DIR.'/data/cache'
				    );

				$cacheDbTable = Zend_Cache::factory('Core',
				                             'Apc',
				                             $frontendOptions,
				                             $backendOptions);


				// Next, set the cache to be used with all table objects
				Zend_Db_Table_Abstract::setDefaultMetadataCache($cacheDbTable);
				
				//		echo "apc: ". ini_get("apc.shm_segments");
				//		//ini_set("apc.shm_size", '90');
				//		echo "apc: ". ini_get("apc.shm_size");
				//		print_r(apc_sma_info());

						//phpinfo();
			}
		}
	
        
		
    }
}
?>