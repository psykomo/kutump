<?php
class Site_DmsController extends Zend_Controller_Action
{
	function preDispatch()
	{
		$this->_helper->layout()->setLayout('layout-final-inside');
		
		Zend_Session::start();
		
		$sReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$sReturn = urlencode($sReturn);
		$this->view->returnTo = $sReturn;
		
		$auth =  Zend_Auth::getInstance();
		if(!$auth->hasIdentity())
		{
			$actionName = $this->getRequest()->getActionName();
			if($actionName!='search')
				$this->_redirect(KUTU_ROOT_URL.'/helper/sso/login'.'?returnTo='.$sReturn);
			//$this->_forward('about');
		}
		else
		{
			// [TODO] else: check if user has access to admin page
			$username = $auth->getIdentity()->username;
			$this->view->username = $username;
		}
		
	}
	public function indexAction()
	{
		//$this->_helper->layout()->setLayout('layout-iht');
		
		$r = $this->getRequest();
		$node = ($r->getParam('node')?$r->getParam('node'):'lgs4a0ee4ab533b4');
		
		$tblFolder = new Kutu_Core_Orm_Table_Folder();
		$rowFolder = $tblFolder->find($node)->current();
		
		$this->view->listTitle = $rowFolder->title;
		$this->view->pageTitle = $rowFolder->title;
		
		//View catalogs
		$this->view->currentNode = $node;
		$limit = ($r->getParam('limit'))?$r->getParam('limit'):12;
		$this->view->limit =$limit;
		$itemsPerPage = $limit;
		$this->view->itemsPerPage = $itemsPerPage;
		$offset = ($r->getParam('offset'))?$r->getParam('offset'):0;
		$this->view->offset = $offset;
		
		$sort = ($r->getParam('sort'))?$r->getParam('sort'):"regulationType desc, year desc";  //"regulationType desc, year desc";
		$this->view->sort = $sort;
		
		$db = Zend_Db_Table::getDefaultAdapter()->query
		("SELECT catalogGuid as guid from KutuCatalogFolder where folderGuid='$node'");
		$rowset = $db->fetchAll(Zend_Db::FETCH_OBJ);
		
		$solrAdapter = Kutu_Search::manager();
		
		$numi = count($rowset);
		$sSolr = "id:(";
		for($i=0;$i<$numi;$i++)
		{
			$row = $rowset[$i];
			$sSolr .= $row->guid .' ';
		}
		$sSolr .= ')';
		
		if(!$numi)
			$sSolr="id:(hfgjhfdfka)";
			
		$solrResult = $solrAdapter->findAndSort($sSolr,$offset,$limit, array($sort));
		$solrNumFound = count($solrResult->response->docs);
		$this->view->totalItems = $solrResult->response->numFound;
		$this->view->hits = $solrResult;
		
		$bpm = new Kutu_Core_Bpm_Catalog();
		$this->view->bpm = $bpm;
	}
	public function indexwithajaxAction()
	{
		//$this->_helper->layout()->setLayout('layout-iht');
		
		$r = $this->getRequest();
		$node = ($r->getParam('node')?$r->getParam('node'):'lgs4a0ee4ab533b4');
		$modDir = $this->getFrontController()->getModuleDirectory();
		require_once($modDir.'/components/ViewFolder.php');
		$w = new ViewFolder($node);
		$this->view->widget1 = $w;
		
		require_once($modDir.'/components/ViewCatalogsInFolder.php');
		$w2 = new Dms_ViewCatalogsInFolder($node);
		$this->view->widget2 = $w2;
		
	}
	public function detailsAction()
	{
		//$this->_helper->layout()->setLayout('layout-iht');
		
		$r = $this->getRequest();
		$catalogGuid = $r->getParam('guid');
		$this->view->catalogGuid = $catalogGuid;
		
		$folderGuid = $r->getParam('node');
		$this->view->currentNode = $folderGuid;
		
		
		$tblCatalog = new Kutu_Core_Orm_Table_Catalog();
		$rowCatalog = $tblCatalog->find($catalogGuid)->current();
		
		//update number of downloads and number of views
		$bpm = new Kutu_Core_Bpm_Catalog();
		$bpm->updateNumberOfViews($rowCatalog->guid);
		$this->view->bpm = $bpm;
		
		
		$rowsetAttribute = $rowCatalog->findDependentRowsetCatalogAttribute();
		$rowTitle = $rowsetAttribute->findByAttributeGuid('fixedTitle');
		$this->view->catalogTitle = $rowTitle->value;
		
		$rowSubTitle = $rowsetAttribute->findByAttributeGuid('fixedSubTitle');
		$this->view->catalogSubTitle = $rowSubTitle->value;
		
		$modDir = $this->getFrontController()->getModuleDirectory();
		require_once($modDir.'/components/Dms/Catalog/DetailsViewer.php');
		$w = new Dms_Catalog_DetailsViewer($catalogGuid, 'root');
		$this->view->widget1 = $w;
		
		if(empty($folderGuid))
		{
			$rowsetFolder = $rowCatalog->findManyToManyRowset('Kutu_Core_Orm_Table_Folder', 'Kutu_Core_Orm_Table_CatalogFolder');
			if(count($rowsetFolder)>0)
			{
				$rowFolder = $rowsetFolder->current();
				$folderGuid = $rowFolder->guid;
				$this->view->currentNode = $folderGuid;
			}
		}
	}
	public function aboutAction()
	{
		
	}
	public function searchAction()
	{
		//$this->_helper->layout()->setLayout('layout-iht');
		
		$r = $this->getRequest();
		
		$query = $r->getQuery(); 
		if (empty($query)) 
		{ 
			// no parameters passed in query string
		 	if(!empty($_SESSION['sQuery']))
				$sQuery = $_SESSION['sQuery'];
			else
				$sQuery = 'xxyyzz';
		}
		else
		{
			$sQuery = $r->getParam('sQuery');
			if(empty($sQuery))
				$sQuery = 'xxyyzz';
			$_SESSION['sQuery'] = $sQuery;
		}
		
		$this->view->sQuery = $sQuery;
		
		
		//Get ready ZEND paginator 
		
		$limit = ($r->getParam('limit'))?$r->getParam('limit'):10;
		$this->view->limit =$limit;
		$currentPage = $this->_getParam('page',1);
		$sort = ($r->getParam('sort'))?$r->getParam('sort'):"createdDate desc";  //"regulationType desc, year desc";
		$this->view->sort = $sort;
		
		//----
		
		
		
		$indexingEngine = Kutu_Search::manager();
    	
		$offset = ($currentPage-1) * $limit;
		$hits = $indexingEngine->find($sQuery,$offset, $limit);
		
		$this->view->hits = $hits;
		
		$bpm = new Kutu_Core_Bpm_Catalog();
		$this->view->bpm = $bpm;
		
		
		//ZEND PAGINATOR
		
		$adapter = new Zend_Paginator_Adapter_Null($hits->response->numFound);
		
		$paginator = new Zend_Paginator($adapter);
		$paginator->setCurrentPageNumber($currentPage);
		$paginator->setItemCountPerPage($limit);
		$this->view->paginator = $paginator;
	}
	function advancedsearchAction()
	{
		//$this->_helper->layout()->setLayout('layout-iht');
		
		$r = $this->getRequest();
		
		$query = $r->getQuery(); 
		if (empty($query)) 
		{ 
			// no parameters passed in query string
		 	if(!empty($_SESSION['sQuery']))
				$sQuery = $_SESSION['sQuery'];
			else
				$sQuery = 'xxyyzz';
		}
		else
		{
			$sQuery = $r->getParam('sQuery');
			if(empty($sQuery))
				$sQuery = 'xxyyzz';
			$_SESSION['sQuery'] = $sQuery;
		}
		
		$this->view->sQuery = $sQuery;
		
		
		//Get ready ZEND paginator 
		
		$limit = ($r->getParam('limit'))?$r->getParam('limit'):10;
		$this->view->limit =$limit;
		$currentPage = $this->_getParam('page',1);
		$sort = ($r->getParam('sort'))?$r->getParam('sort'):"createdDate desc";  //"regulationType desc, year desc";
		$this->view->sort = $sort;
		
		//----
		
		
		
		$indexingEngine = Kutu_Search::manager();
    	
		$offset = ($currentPage-1) * $limit;
		$hits = $indexingEngine->find($sQuery,$offset, $limit);
		
		$this->view->hits = $hits;
		
		$bpm = new Kutu_Core_Bpm_Catalog();
		$this->view->bpm = $bpm;
		
		
		//ZEND PAGINATOR
		
		$adapter = new Zend_Paginator_Adapter_Null($hits->response->numFound);
		
		$paginator = new Zend_Paginator($adapter);
		$paginator->setCurrentPageNumber($currentPage);
		$paginator->setItemCountPerPage($limit);
		$this->view->paginator = $paginator;
	}
}
?>