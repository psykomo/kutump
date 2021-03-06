<?php

class Kutu_Core_Orm_Table_ForumBoardChild extends Zend_Db_Table_Abstract 
{
	protected $_name = 'KutuForumBoardChild';
	
	function fetchForumTopic($topicid)
	{
		$sql = $this->_db->select()
			->from(array('tfbc' => 'KutuForumBoardChild'),
			array('childId' => 'tfbc.id','board_id','child_title','author','answers','child_entrydate' => 'entrydate','child_from' => 'lastpost_from'))
			->join(array('tfp' => 'KutuForumPosts'),
			'tfbc.id = tfp.topic_id')
			->where('tfbc.board_id = ?',$topicid)
			->where('tfp.status = ?',0); // status = 0 itu rootnya reply
		
//    	$sql = $sql->__toString();
//    	print_r($sql);exit();

    	$db = $this->_db->query($sql);
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
	function countTopic($topicid)
	{
		$sql = $this->_db->select()->from('KutuForumBoardChild', array('count' => 'COUNT(*)'))
			->where('board_id = ?',$topicid);
		
//		$sql = $sql->__toString();
//		print_r($sql);exit();
		
		$db = $this->_db->query($sql);
		$dataFetch = $db->fetchAll(Zend_Db::FETCH_ASSOC);
		return ($dataFetch[0]['count']);
	}
	function getMax($id)
	{
		$sql = $this->_db->select()
			->from('KutuForumBoardChild', array('maxs' => 'MAX('.$id.')'));
		
		$db = $this->_db->query($sql);
		$dataFetch = $db->fetchAll(Zend_Db::FETCH_ASSOC);
		return ($dataFetch[0]['maxs']);
	}
}

?>