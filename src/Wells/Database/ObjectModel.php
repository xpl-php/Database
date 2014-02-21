<?php

namespace Wells\Database;

abstract class ObjectModel {
	
	public $tablename;
	
	protected $table;
	
	public function __construct(){
		$this->init();	
	}
	
	/**
	 * Returns table basename string.
	 * @return string Table basename
	 */
	abstract public function getTableBasename();
	
	/**
	 * Imports Table to object.
	 */
	final public function init(){
		$this->table = Database::i()->table( $this->getTableBasename() );
		$this->tablename = $this->table->schema()->table;
	}
	
	/**
	 * Returns model's Table instance.
	 * @return Table
	 */
	final public function table(){
		return $this->table;
	}
	
	/**
	 * Returns the table name.
	 * @return string Table name
	 */
	final public function getTableName(){
		return $this->tablename;
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
	* Select a row or (columns from a row) from the table.
	*
	* @see DB::select()
	*/
	public function select( array $where, $select = '*' ){
		return $this->table->select( $where, $select );
	}
	
	/**
	* Insert a row into the table.
	*
	* @see DB::insert()
	*/
	public function insert( $data ){
		
		$this->beforeInsert($data);
		
		$success = $this->table->insert($data);
		
		$this->afterInsert($success);
		
		return $success;
	}
	
	protected function beforeInsert( &$data ){}
	protected function afterInsert( &$success ){}
	
	/**
	* Update a row in the table
	*
	* @see DB::update()
	*/	
	public function update( $data, $where ){
		
		$this->beforeUpdate($data, $where);
		
		$success = $this->table->update($data, $where);
		
		$this->afterUpdate($success);
		
		return $success;
	}
	
	protected function beforeUpdate( &$data, &$where ){}
	protected function afterUpdate( &$success ){}
	
	/**
	* Delete a row in the table
	*
	* @see DB::delete()
	*/
	public function delete( $where ) {
		
		$this->beforeDelete($where);
		
		$success = $this->table->delete($where);

		$this->afterDelete($success);
		
		return $success;
	}
	
	protected function beforeDelete( &$where ){}
	protected function afterDelete( &$success ){}
	
	/**
	* Update a single row column value in the table
	*
	* @see DB::update()
	*/
	public function updateField( $name, $value, array $where ){
		return $this->table->updateField($name, $value, $where);
	}
	
	/**
	 * Perform a PDO query
	 */
	public function query( $query ){
		return $this->table->query($query);
	}
	
}
