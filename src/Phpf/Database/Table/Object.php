<?php

namespace Phpf\Database\Table;

use Phpf\Common\Container\SerializableData as Container;

class Object extends Container {
	
	public function __construct($data = null) {
		if (isset($data)) {
			$this->import($data);
		}
	}
	
}
