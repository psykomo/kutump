<?php
class Kutu_Application_Resource_Indexing extends Zend_Application_Resource_ResourceAbstract
{
	public function init()
    {
		$options = array_change_key_case($this->getOptions(), CASE_LOWER);
		
		//print_r($options);
		
		switch (strtolower($options['adapter']))
		{
			case 'solr':
				//print_r($options['solr']['write']);
				
				$aWrite = $options['solr']['write'];
				
				$solrHost = $aWrite['host'];
    			$solrPort = $aWrite['port'];
    			$solrHomeDir = $aWrite['dir'];
    			
				$newAdapter = new Kutu_Search_Adapter_Solr($solrHost, $solrPort, $solrHomeDir);
				
				//print_r($options['extractor']['pdf']);
				$newAdapter->setExtractor('pdf', $options['extractor']['pdf']);
				$newAdapter->setExtractor('word', $options['extractor']['word']);
    			
    			//check if newAdapter is an abstract of Kutu_Search_Adapter
    			
    			return $newAdapter;
    			break;
    		case 'zendlucene':

				$config = $options['zendlucene'];
				
    			if(isset($config['dir']))
    				$luceneHomeDir = $config['dir'];
    			else 
    				$luceneHomeDir = null;
    			
				$newAdapter = new Kutu_Search_Adapter_ZendLucene($luceneHomeDir);
				
				$newAdapter->setExtractor('pdf', $options['extractor']['pdf']);
				$newAdapter->setExtractor('word', $options['extractor']['word']);
    			
    			//check if newAdapter is an abstract of Kutu_Search_Adapter
    			
    			return $newAdapter;
    			break;
			default:
				throw new Zend_Exception('Indexing adapter: '.$options['adapter']. ' is not supported.');
		}

    }
}
?>