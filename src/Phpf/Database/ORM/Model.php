<?php
/**
 * @package Phpf.Database
 * @subpackage ORM
 */

namespace Phpf\Database\Orm;

use Phpf\Database\Database;

abstract class Model {
	
	public $tablename;
	
	protected $table;
	
	protected $fetch_objects = false;
	
	protected $object_class;
	
	/**
	 * Imports Table object from database.
	 */
	final public function __construct(){
		$this->table = Database::instance()->table( $this->getTableBasename() );
		$this->tablename = $this->table->schema()->table;
	}
	
	/**
	 * Returns FluentPDO instance
	 */
	final public function fluent(){
		return Database::instance()->fluent();
	}
	
	/**
	 * Returns table basename string.
	 * @return string Table basename
	 */
	abstract public function getTableBasename();
	
	/**
	 * Returns the table name.
	 * @return string Table name
	 */
	final public function getTableName(){
		return $this->tablename;
	}
	
	/**
	 * Returns the primary key name for table.
	 */
	final public function getPrimaryKey(){
		return $this->schema()->primary_key;
	}
	
	/**
	 * Returns model's Table instance.
	 * @return Table
	 */
	final public function table(){
		return $this->table;
	}
	
	/**
	 * Returns model's Table's Schema instance.
	 * @return Table\Schema
	 */
	 final public function schema(){
	 	return $this->table->schema();
	 }
	
	/**
	 * Returns true if value is a column name for the table.
	 * @return bool True if valid column
	 */
	final public function isColumn( $key ){
		return $this->table->schema()->isColumn( $key );	
	}
	
	/**
	 * Returns true if value is a key name for the table.
	 * @return bool True if valid key
	 */
	final public function isKey( $key ){
		return $this->table->schema()->isKey( $key );	
	}
	
	/**
	 * Returns sprintf-style string based on column format.
	 * @return string One of: '%f' if float, '%d' if integer, '%s' if anything else
	 */
	final public function getColumnFormat( $key ){
		return $this->table->schema()->getColumnFormat( $key );	
	}
	
	/**
	 * Set whether to return objects.
	 * Must set object_class before setting to true.
	 */
	final public function setFetchObjects( $val ){
		
		if ( $val && !isset($this->object_class) ){
			throw new \RuntimeException("Must set object_class property before calling setFetchObjects().");
		} else {
			$this->fetch_objects = (bool) $val;
		}
		
		return $this;
	}
	
	/**
	 * Set the class to use for returned objects.
	 */
	final public function setObjectClass( $class ){
		$this->object_class = $class;
		return $this;
	}
		
	/**
	* Insert a row into the table.
	*
	* @see Database::insert()
	*/
	public function insert( $data ){
		
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
	public function update( $data, $where ){
		
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
	public function delete( $where ) {
		
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
	public function updateField( $name, $value, array $where ){
		return $this->table->updateField($name, $value, $where);
	}
	
	/**
	* Select a row or (columns from a row) from the table.
	*
	* @see Database::select()
	*/
	public function select( $where, $select = '*' ){
			
		$data = $this->table->select( $where, $select );
		
		if ( empty($data) || is_scalar($data) ){
			return $data;
		}
		
		if ( 1 === count($data) ){
			
			$data = array_shift($data);
			
			if ( !$this->fetch_objects ){
				return $data;
			} else {
				$class = $this->object_class;
				return new $class($data);
			}
		}
		
		$objects = array();
		$pk = $this->getPrimaryKey();
		
		foreach( $data as $obj ){
			if ( $this->fetch_objects ){
				$class = $this->object_class;
				$objects[ $obj->$pk ] = new $class($obj);
			} else {
				$objects[ $obj->$pk ] = $obj;
			}
		}
		
		return $objects;
	}
	
	/**
	 * Perform a PDO query
	 */
	public function query( $query ){
		return $this->table->query($query);
	}
	
	/**
	 * Perform action before insert().
	 */
	protected function beforeInsert( &$data ){}
	
	/**
	 * Perform action after insert().
	 */
	protected function afterInsert( &$success ){}
	
	/**
	 * Perform action before update().
	 */
	protected function beforeUpdate( &$data, &$where ){}
	
	/**
	 * Perform action after update().
	 */
	protected function afterUpdate( &$success ){}
	
	/**
	 * Perform action before delete().
	 */
	protected function beforeDelete( &$where ){}
	
	/**
	 * Perform action after delete().
	 */
	protected function afterDelete( &$success ){}
	
}
