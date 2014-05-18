<?php

namespace Phpf\Database;

class Model2 implements \ArrayAccess, \Countable {
	
	protected static $info = array(
		'name'			=> null,
		'columns'		=> array(),
		'primary_key'	=> null,
		'unique_keys'	=> array(),
		'keys'			=> array(),
		'relations'		=> array(),
		'fetch_objects' => false,
		'collection_class' => null,
	);
	
	protected static $readonly = array();
	
	protected static $schema;
	protected static $db;
	
	public function info($key) {
		$class = get_called_class();
		return $class::$info[$key];
	}
	
	public function getName() {
		return $this->info('name');
	}
	
	public function getColumnNames() {
		return array_keys($this->info('columns'));
	}
	
	public function getFields() {
		return $this->getColumnNames(); 
	}
	
	public function getPrimaryKey() {
		return $this->info('primary_key');
	}
	
	public function getUniqueKeys() {
		return $this->info('unique_keys');
	}
	
	public function getKeys() {
		return $this->info('keys');
	}
	
	public function fetchObjects() {
		return $this->info('fetch_objects');
	}

	public function getCollectionClass() {
		return $this->info('collection_class');
	}
	
	public function isReadonly($key) {
		$class = get_called_class();
		return isset($class::$readonly[$key]);
	}
	
	public function offsetGet($index) {
		return isset($this->$index) ? $this->$index : null;
	}
	
	public function offsetSet($index, $newval) {
		if ($this->isReadonly($index)) {
			throw new \RuntimeException("Cannot set '$index' - key is read-only.");
		}
		$this->$index = $newval;
		return $this;
	}
	
	public function offsetExists($index) {
		return isset($this->$index);
	}
	
	public function offsetUnset($index) {
		if ($this->isReadonly($index)) {
			throw new \RuntimeException("Cannot unset '$index' - key is read-only.");
		}
		unset($this->$index);
	}
	
	public function count() {
		return count($this);
	}
	
	/**
	 * Sets Schema as property.
	 */
	public function __construct(Table\Schema $schema, Database &$db = null) {
		
		static::$info = array_merge(self::$info, static::$info);
		
		static::$schema = $schema;
		
		if (isset($db)) {
			$this->setDatabase($db);
		}
	}
	
	public function setDatabase(Database &$db) {
		static::$db =& $db;
		return $this;
	}
	
	public function __get($var) {
		if ('db' === $var) {
			return $this->db();
		}
		if ('schema' === $var) {
			return $this->schema();
		}
		return isset($this->$var) ? $this->$var : null;
	}
	
	public function schema() {
		return isset(static::$schema) ? static::$schema : null;
	}
	
	public function db() {
		return isset(static::$db) ? static::$db : null;
	}
	
	public function tablename() {
		return isset(static::$schema) ? static::$schema->name : null;
	}

	/**
	 * Select a row or (columns from a row) from the table.
	 * 
	 * Creates objects if $fetch_objects is true or a class name.
	 * 
	 * If multiple rows are returned, returns an array with the keys
	 * set to the primary key value of each item.
	 *
	 * @see Database::select()
	 */
	public function select($where, $select = '*') {

		$data = $this->db()->select($this->tablename(), $where, $select);

		if (empty($data) || is_scalar($data)) {
			return $data;
		}
		
		$fetch_objects = $this->fetchObjects();

		if (1 === count($data)) {
			$data = array_shift($data);
			return $fetch_objects ? $this->createObject($data) : $data;
		}

		$keys = array_ppull($data, $this->getPrimaryKey());
		
		if ($fetch_objects) {
			$values = array_map(array($this, 'createObject'), $data);
		} else {
			$values =& $data;
		}
		
		return array_combine($keys, $values);
	}
	
	/**
	 * Creates an object from data.
	 * 
	 * Uses the model's class, if set, otherwise the standard model-aware object.
	 * 
	 * @param mixed $data Object data.
	 * @return \Phpf\Database\Table\Object\ModelAware Instance of model-aware object.
	 */
	public function createObject($data) {
		
		$class = $this->fetchObjects();
		
		if (true === $class) {
			$class = 'Phpf\Database\Table\Object\ModelAware';
		}
		
		$object = new $class($data);
		
		if ($object instanceof Table\Object\ModelAware) {
			$object->setModelClass(get_called_class());
		}
		
		return $object;
	}
	
	public function createCollection(array $objects) {
		
		$class = $this->getCollectionClass();
		
		if (! $class) {
			$class = 'Phpf\Database\Table\Object\Collection';
		}
		
		return new $class($objects);
	}

	/**
	 * Insert a row into the table.
	 *
	 * @see Database::insert()
	 */
	public function insert($data) {
		return $this->db()->insert($this->tablename(), $data);
	}

	/**
	 * Update a row in the table
	 *
	 * @see Database::update()
	 */
	public function update($data, $where) {
		return $this->db()->update($this->tablename(), $data, $where);
	}

	/**
	 * Delete a row in the table
	 *
	 * @see Database::delete()
	 */
	public function delete($where) {
		return $this->db()->delete($this->tablename(), $where);
	}

	/**
	 * Update a single row column value in the table
	 *
	 * @see Database::update()
	 */
	public function updateField($name, $value, array $where) {
		return $this->update(array($name => $value), $where);
	}

	/**
	 * Performs a query directly on PDO
	 *
	 * @see Database::query()
	 */
	public function query($query) {
		return $this->db()->query($query);
	}
	
}
