<?php

namespace Phpf\Database\Table\Object;

use Phpf\Database\Table\Relation\Relationship;

class Composite extends ModelAware {
	
	protected $relationships = array();
	
	public function hasRelationships($foreign_table) {
		isset($this->relationships[$foreign_table]) or $this->buildRelationships($foreign_table);
		return !empty($this->relationships[$foreign_table]);
	}
	
	public function getRelationships($foreign_table) {
		return $this->hasRelationships($foreign_table) ? $this->relationships[$foreign_table] : null;
	}
	
	protected function buildRelationships($foreign_table) {
		
		$this->relationships[$foreign_table] = array();
		
		$schema = $this->schema();
		
		if ($schema->hasRelations($foreign_table)) {
			
			foreach($schema->getRelations($foreign_table) as &$relation) {
				$this->relationships[$foreign_table] = new Relationship($relation, $this);
			}
		}
		
		return $this;
	}
	
	public function getRelatedObject($table, $key) {
		
		if (! $this->schema()->hasRelations($table)) {
			return false;
		}
		
		if ($this->isNative()) {
			$table = $this->relation->getForeignTableName();
			$fkey = $this->relation->getForeignKey();
		} else {
			$table = $this->relation->getNativeTableName();
			$fkey = $this->relation->getNativeKey();
		}
		
		$db = \Database::instance();
		
		return $db->query("SELECT * FROM $table WHERE $fkey = $keyZ")->fetchAll(\PDO::FETCH_ASSOC);
	}
	
	
}
