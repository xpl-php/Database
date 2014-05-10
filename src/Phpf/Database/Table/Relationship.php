<?php

namespace Phpf\Database\Table;

use InvalidArgumentException;

class Relationship {
	
	protected $relation;
	
	protected $is_native;
	
	public function __construct(Relation $relation, $table) {
		
		if (! is_string($table)) {
				
			if (! $table instanceof ModelAwareObject) {
				$msg = 'Table must be table name string or instance of ModelAwareObject - '.gettype($table).' given.';
				throw new InvalidArgumentException($msg);
			}
			
			$table = $table->getTableName();
		}
		
		$this->is_native = ($table === $relation->getNativeTableName());
		
		if (! $this->is_native) {
			if ($table !== $relation->getForeignTableName()) {
				throw new InvalidArgumentException("Invalid table '$table' for relation.");
			}
		}
		
		$this->relation = $relation;
	}
	
	public function getType() {
		return $this->relation->getType();
	}
	
	public function isNative() {
		return $this->is_native;
	}
	
	public function isForeign() {
		return ! $this->isNative();
	}
	
	public function getRelatedObjects($value) {
		
		if ($this->isNative()) {
			$table = $this->relation->getForeignTableName();
			$key = $this->relation->getForeignKey();
		} else {
			$table = $this->relation->getNativeTableName();
			$key = $this->relation->getNativeKey();
		}
		
		$db = \Database::instance();
		
		return $db->query("SELECT * FROM $table WHERE $key = $value")->fetchAll(\PDO::FETCH_ASSOC);
	}
	
}
