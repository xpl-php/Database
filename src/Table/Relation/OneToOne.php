<?php

namespace Phpf\Database\Table\Relation;

class OneToOne extends AbstractRelation {
	
	public function getType() {
		return 'one_to_one';
	}
	
}