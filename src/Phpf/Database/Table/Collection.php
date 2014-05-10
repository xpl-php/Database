<?php

namespace Phpf\Database\Table;

use Phpf\Common\Container\SerializableData as Container;

class Collection extends Container {
	
	protected $id;
	
	public function __construct($data = null) {
		if (isset($data)) {
			$this->import($data);
		}
	}
	
	public function setId($collection_id) {
		$this->id = $collection_id;
		return $this;
	}
		
}
