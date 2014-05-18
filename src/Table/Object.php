<?php

namespace Phpf\Database\Table;

class Object extends \Phpf\Common\SerializableDataContainer {
	
	public function __construct($data = null) {
		if (isset($data)) {
			$this->import($data);
		}
	}
	
}
