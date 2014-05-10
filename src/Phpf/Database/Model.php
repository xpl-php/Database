<?php
/**
 * @package Phpf
 */

namespace Phpf\Database;

use RuntimeException;
use Phpf\Database\Table\ModelAwareObject;

abstract class Model
{
	
	/**
	 * Name of the table excluding prefix.
	 * @var string
	 */
	protected $table_basename;
	
	/**
	 * Name of the table including prefix.
	 * @var string
	 */
	protected $table_name;
	
	/**
	 * Table object.
	 * @var \Phpf\Database\Table
	 */
	protected $table;

	/**
	 * Whether to fetch objects; optionally the object class.
	 * 
	 * @var boolean|string
	 */
	protected $fetch_objects = false;
	
	/**
	 * Connects table to object.
	 * 
	 * If a subclass overrides this method, it must call the 
	 * connect() method before attempting to use the object.
	 */
	public function __construct() {
		$this->connect();
	}
	
	/**
	 * Returns true if value is a column name for the table.
	 * 
	 * @return bool True if valid column
	 */
	final public function isColumn($key) {
		return $this->table->schema()->isColumn($key);
	}

	/**
	 * Returns true if value is a key name for the table.
	 * 
	 * @return bool True if valid key
	 */
	final public function isKey($key) {
		return $this->table->schema()->isKey($key);
	}

	/**
	 * Returns the primary key name for table.
	 * 
	 * @return string Primary key column name.
	 */
	final public function getPrimaryKey() {
		return $this->schema()->primary_key;
	}

	/**
	 * Returns the table name.
	 * @return string Table name
	 */
	final public function getTableName() {
		return $this->table_name;
	}

	/**
	 * Returns model's Table instance.
	 * @return Table
	 */
	final public function table() {
		return $this->table;
	}

	/**
	 * Returns model's Table's Schema instance.
	 * @return Table\Schema
	 */
	final public function schema() {
		return $this->table->schema();
	}

	/**
	 * Set whether to return objects.
	 */
	final public function setFetchObjects($value) {
		$this->fetch_objects = $value;
		return $this;
	}

	/**
	 * Returns FluentPDO instance
	 */
	final public function fluent() {
		return \Database::instance()->fluent();
	}

	/**
	 * Perform a PDO query
	 */
	public function query($query) {
		return $this->table->query($query);
	}

	/**
	 * Insert a row into the table.
	 *
	 * @see Database::insert()
	 */
	public function insert($data) {

		$this->beforeInsert($data);

		$success = $this->table->insert($data);

		$this->afterInsert($success);

		return $success;
	}

	/**
	 * Update a row in the table
	 *
	 * @see Database::update()
	 */
	public function update($data, $where) {

		$this->beforeUpdate($data, $where);

		$success = $this->table->update($data, $where);

		$this->afterUpdate($success);

		return $success;
	}

	/**
	 * Delete a row in the table
	 *
	 * @see Database::delete()
	 */
	public function delete($where) {

		$this->beforeDelete($where);

		$success = $this->table->delete($where);

		$this->afterDelete($success);

		return $success;
	}

	/**
	 * Update a single row column value in the table
	 *
	 * @see Database::update()
	 */
	public function updateField($name, $value, array $where) {
		return $this->table->updateField($name, $value, $where);
	}

	/**
	 * Select a row or (columns from a row) from the table.
	 *
	 * @see Database::select()
	 */
	public function select($where, $select = '*') {

		$data = $this->table->select($where, $select);

		if (empty($data) || is_scalar($data)) {
			return $data;
		}

		if (1 === count($data)) {

			$data = array_shift($data);

			return $this->fetch_objects ? $this->createObject($data) : $data;
		}

		$keys = array_ppull($data, $this->getPrimaryKey());
		
		if ($this->fetch_objects) {
			$values = array_map(array($this, 'createObject'), $data);
		} else {
			$values =& $data;
		}
		
		return array_combine($keys, $values);
	}
	
	public function createObject($data) {
		
		if (true === $this->fetch_objects) {
			$class = 'Phpf\Database\Table\ModelAwareObject';
		} else {
			$class = $this->fetch_objects;
		}
		
		$object = new $class($data);
		
		if ($object instanceof ModelAwareObject) {
			$object->setModelClass(get_called_class());
		}
		
		return $object;
	}
	
	public function createCollection(array $objects) {
		
		if (isset($this->collection_class)) {
			$class = $this->collection_class;
		} else {
			$class = 'Phpf\Database\Table\Collection';
		}
		
		return new $class($objects);
	}

	/**
	 * Perform action before insert().
	 */
	protected function beforeInsert(&$data) {
	}

	/**
	 * Perform action after insert().
	 */
	protected function afterInsert(&$success) {
	}

	/**
	 * Perform action before update().
	 */
	protected function beforeUpdate(&$data, &$where) {
	}

	/**
	 * Perform action after update().
	 */
	protected function afterUpdate(&$success) {
	}

	/**
	 * Perform action before delete().
	 */
	protected function beforeDelete(&$where) {
	}

	/**
	 * Perform action after delete().
	 */
	protected function afterDelete(&$success) {
	}

	/**
	 * Connects the model's Table object from the Database instance.
	 * 
	 * @return $this
	 */
	protected function connect() {
		
		if (! isset($this->table_basename)) {
			$class = get_called_class();
			throw new RuntimeException("Missing 'table_basename' property on Model with class '$class'.");
		}
		
		$this->table = \Database::instance()->table($this->table_basename);
		
		$this->table_name = $this->table->schema()->getName();
		
		return $this;
	}

}
