<?php

namespace Phpf\Database\Table;

use InvalidArgumentException;

class Schema
{
	
	/**
	 * Full table name (prefixed). Set in constructor.
	 * @var string
	 */
	protected $name;
	
	/**
	 * Table basename (not prefixed). Required.
	 * @var string
	 */
	protected $basename;
	
	/**
	 * Associative array of column names and objects.
	 * @var array
	 */
	protected $columns = array();
	
	/**
	 * Table primary key.
	 * @var string
	 */
	protected $primary_key;
	
	/**
	 * Unique keys for the table, exclusive of primary key.
	 * @var array
	 */
	protected $unique_keys = array();
	
	/**
	 * Other indexes for the table, exclusive of unique and primary keys.
	 * @var array
	 */
	protected $keys = array();
	
	/**
	 * Object relation definitions.
	 * @var array
	 */
	protected $relations = array();
	
	/**
	 * Sets the table name (i.e. without prefix).
	 * 
	 * @param string $name Table name without prefix.
	 */
	public function __construct($basename) {
		$this->basename = $basename;
	}
	
	/**
	 * Allows access to protected properties.
	 * 
	 * @param string $var Property name.
	 * @return mixed Property value if set otherwise null.
	 */
	public function __get($var) {
		return isset($this->$var) ? $this->$var : null;
	}
	
	/**
	 * Adds multiple columns to the schema.
	 * 
	 * @param array Array of column objects.
	 * @return $this
	 */
	public function setColumns(array $columns) {
		foreach($columns as $column) {
			$this->setColumn($column);
		}
		return $this;
	}
	
	/**
	 * Adds a Column object to the schema.
	 * 
	 * @param \Phpf\Database\Column $col Column to add
	 * @return $this
	 */
	public function setColumn(Column $column) {
			
		$name = $column->getName();
		
		$this->columns[$name] = $column;
			
		if ($column->isIndex()) {
			if ($column->isPrimaryKey()) {
				$this->primary_key = $name;
			} else if ($column->isUniqueKey()) {
				$this->unique_keys[$name] = $name;
			} else {
				$this->keys[$name] = $name;
			}
		}
		
		return $this;
	}
	
	/**
	 * Returns a column object by name.
	 * 
	 * @param string $name Column name.
	 * @return \Phpf\Database\Column Column if set, otherwise null.
	 */
	public function getColumn($name) {
		return isset($this->columns[$name]) ? $this->columns[$name] : null;
	}
	
	/**
	 * Returns true if col exists.
	 * 
	 * @param string $column Column name.
	 * @return boolean True if exists, otherwise false.
	 */
	public function isColumn($column) {
		return isset($this->columns[$column]);
	}
		
	/**
	 * Returns true if column is indexed.
	 * 
	 * @param string $column Column name.
	 * @return boolean True if indexed, otherwise false.
	 */
	public function isIndex($column) {
		return ($column === $this->primary_key || in_array($column, $this->keys, true) || in_array($column, $this->unique_keys, true));
	}
	
	/** &Alias of isIndex() */
	public function isKey($col) {
		return $this->isIndex($col);
	}
	
	/**
	 * Sets the full table name using the database table prefix.
	 * 
	 * @param string $table_prefix Database table prefix.
	 * @return $this
	 */
	public function setTablePrefix($table_prefix) {
		$this->name = $table_prefix.$this->basename;
		return $this;
	}
	
	public function hasRelations($column = null) {
		
		if (isset($column)) {
			return ! empty($this->relations[$column]);
		}
		
		return ! empty($this->relations);
	}
	
	public function getRelations($column = null) {
		
		if (isset($column)) {
			return isset($this->relations[$column]) ? $this->relations[$column] : null;
		}
		
		return $this->relations;
	}
	
	public function addRelation($type, $column, $foreign_table, $foreign_key) {
		
		if (! isset($this->columns[$column])) {
			throw new InvalidArgumentException("Invalid table column '$column'.");
		}
		
		$relation = new Relation($type, $this->getBasename(), $column, $foreign_table, $foreign_key);
		
		$this->relations[$column][$foreign_table] = $relation;
	}
	
	final public function getName() {
		return $this->name;
	}
	
	final public function getBasename() {
		return $this->basename;
	}
}
