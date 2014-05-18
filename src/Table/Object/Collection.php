<?php

namespace Phpf\Database\Table\Object;

class Collection extends \Phpf\Common\SerializableDataContainer
{
	
	public function __construct($data = null) {
		if (isset($data)) {
			$this->import($data);
		}
	}
	
	public function setId($collection_id) {
		$this->set('id', $collection_id);
		return $this;
	}
	
	public function getId() {
		return $this->get('id');
	}
		
}
