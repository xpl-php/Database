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
	
	/**
	 * Imports Table object from database.
	 */
	final public function __construct(){
		$this->table = Database::instance()->table( $this->getTableBasename() );
		$this->tablename = $this->table->schema()->table;
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
		return $this->table->select( $where, $select );
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
