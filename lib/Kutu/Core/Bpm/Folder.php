<?php
class Kutu_Core_Bpm_Folder
{
	public function delete($folderGuid)
	{
		$tbl = new Kutu_Core_Orm_Table_Folder();
		$rowset = $tbl->find($folderGuid);
		if(count($rowset))
		{
			$row = $rowset->current();
			$row->delete();
		}
	}
	
	//this function will delete the folder specified, its sub-folders and its catalogs
	public function forceDelete($folderGuid)
	{
		
		
		Zend_Loader::loadClass('Kutu_Core_Orm_Table_Folder');
		$tblFolder = new Kutu_Core_Orm_Table_Folder();
		$rowSet = $tblFolder->fetchChildren($folderGuid);
		$row1 = $tblFolder->find($folderGuid)->current();
		
		
		
		foreach($rowSet as $row)
		{
			
			$this->forceDelete($row->guid);
		
		}
		
		echo $row1->title . ' ';
		$rowsetCatalogFolder = $row1->findDependentRowsetCatalogFolder();
		
		$tblCatalog = new Kutu_Core_Orm_Table_Catalog();
		$bpmCatalog = new Kutu_Core_Bpm_Catalog();
		if(count($rowsetCatalogFolder))
		{
			foreach($rowsetCatalogFolder as $rowCatalogFolder)
			{
				$rowCatalog = $tblCatalog->find($rowCatalogFolder->catalogGuid)->current();
				echo $rowCatalog->guid . '<br>';
				$bpmCatalog->delete($rowCatalog->guid);
			}
			
			$this->delete($row1->guid);
		}
		else
		{
			$this->delete($row1->guid);
		}
	}
	protected function _traverseFolder($folderGuid, $sGuid, $level)
	{
		Zend_Loader::loadClass('Kutu_Core_Orm_Table_Folder');
		$tblFolder = new Kutu_Core_Orm_Table_Folder();
		$rowSet = $tblFolder->fetchChildren($folderGuid);
		$row = $tblFolder->find($folderGuid)->current();
		$sGuid = '';
			if(count($rowSet))
			{
				$sGuid = '<li>'."<a href='".KUTU_ROOT_URL."/pages/g/$row->guid/h/1'>".$row->title.'</a><ul>';
			}
			else
			{
				$sGuid = '<li>'."<a href='".KUTU_ROOT_URL."/pages/g/$row->guid/h/1'>".$row->title.'</a>';
			}
		
		if(true)
		{
			//echo $level;
			foreach($rowSet as $row)
			{
				//$sTab = '<ul>';
				//$sTab = '';
				//for($i=0;$i<$level;$i++)
					//$sTab .= '<li>';
				
				//$option = '<option value="'.$row->guid.'">'.$sTab.$row->title.'</option>';
				//$option = '"'.$row->guid.'" :'.'"'.$sTab.$row->title.'",';
				//$option = $sTab.$row->title;
				$sGuid .= $this->_traverseFolder($row->guid, '', $level+1)."";
			
				//$sGuid .= $sTab.$row->title . '|<br>'. $this->_traverseFolder($row->guid, '', $level+1);
			
			}
			if(count($rowSet))
			{
				return $sGuid.'</ul></li>';
			}
			else
			{
				return $sGuid.'</li>';
			}
		}
	}
}
?>