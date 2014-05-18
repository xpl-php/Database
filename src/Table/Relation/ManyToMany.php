<?php

namespace Phpf\Database\Table\Relation;

class ManyToMany extends AbstractRelation {
	
	public function getType() {
		return 'many_to_many';
	}
}
