<?php

namespace Phpf\Database\Table\Relation;

class OneToMany extends AbstractRelation {
	
	public function getType() {
		return 'one_to_many';
	}
}
