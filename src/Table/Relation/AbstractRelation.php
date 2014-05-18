<?php

namespace Phpf\Database\Table\Relation;

use Phpf\Database\Table\Schema;

abstract class AbstractRelation {
	
	protected $native_table;
	protected $native_key;
	protected $foreign_table;
	protected $foreign_key;
	
	abstract public function getType();
	
	final public function __construct(Schema $schema, $column, $foreign_table, $foreign_key = '%s_id') {
		$this->native_table = $schema->getBasename();
		$this->native_key = $column;
		$this->foreign_table = $foreign_table;
		
		// Auto-foreign key
		// e.g. Given schema for "user" and foreign table "meta" with key "%s_id",
		// foreign key (i.e. for "meta") will be set to "user_id".
		if (false !== strpos($foreign_key, '%s')) {
			$foreign_key = sprintf($foreign_key, $this->native_table);
		}
		$this->foreign_key = $foreign_key;
	}
	
	final public function getNativeTableName() {
		return $this->native_table;
	}
	
	final public function getForeignTableName() {
		return $this->foreign_table;
	}
	
	final public function getNativeKey() {
		return $this->native_key;
	}
	
	final public function getForeignKey() {
		return $this->foreign_key;
	}
		
}