<?php

namespace Phpf\Database\Table\Relation;

use InvalidArgumentException;

class Relation {
	
	/**
	 * Relation type.
	 * @var int
	 */
	protected $type;
	
	protected $native_table;
	protected $foreign_table;
	
	protected $native_key;
	protected $foreign_key;
	
	protected static $types = array(
		'one_to_one',
		'one_to_many',
		'many_to_many',
	);
	
	public function __construct($type, $native_table, $native_key, $foreign_table, $foreign_key) {
		$this->setType($type);
		$this->native_table = $native_table;
		$this->native_key = $native_key;
		$this->foreign_table = $foreign_table;
		$this->foreign_key = $foreign_key;
	}
	
	final public function setType($type) {
		
		if (! in_array($type, static::$types, true)) {
			throw new InvalidArgumentException("Unknown relation type '$type'.");
		}
		
		$this->type = $type;
		
		return $this;
	}
	
	final public function getType() {
		return $this->type;
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
