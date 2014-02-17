<?php

namespace Wells\Database;

abstract class ObjectModel {
	
	public $tablename;
	
	protected $table;
	
	static protected $_instance;
	
	static public function i(){
		if ( ! isset( static::$_instance ) )
			static::$_instance = new static();
		return static::$_instance;
	}
	
	public final function set_table( Table &$table ){
		$this->table =& $table;
		$this->tablename = $table->schema()->table;
	}
	
	public final function get_tablename(){
		return $this->tablename;
	}
	
	public final function get_table_basename(){
		return $this->table->schema()->table_basename;	
	}
	
	public final function is_column( $key ){
		return $this->table->schema()->is_column( $key );	
	}
	
	public final function is_key( $key ){
		return $this->table->schema()->is_key( $key );	
	}
	
	public final function get_column_format( $key ){
		return $this->table->schema()->get_column_format( $key );	
	}
	
	public function insert( $data, $format = null ){
		
		$this->beforeInsert( $data, $format );
		
		$success = $this->table->insert( $data, $format );
		
		$this->afterInsert( $success );
		
		return $success;
	}
	
	protected function beforeInsert( &$data, &$format ){}
	protected function afterInsert( &$success ){}
	
	public function replace( $data, $format = null ) {
		
		$this->beforeReplace( $data, $format );
		
		$success = $this->table->replace( $data, $format );

		$this->afterReplace( $success );
		
		return $success;
	}
	
	protected function beforeReplace( &$data, &$format ){}
	protected function afterReplace( &$success ){}
		
	
	public function update( $data, $where, $format = null, $where_format = null ){
		
		$this->beforeUpdate( $data, $where, $format, $where_format );
		
		$success = $this->table->update( $data, $where, $format, $where_format );
		
		$this->afterUpdate( $success );
		
		return $success;
	}
	
	protected function beforeUpdate( &$data, &$where, &$format, &$where_format ){}
	protected function afterUpdate( &$success ){}
	
	
	public function delete( $where, $where_format = null ) {
		
		$this->beforeDelete( $where, $where_format );
		
		$success = $this->table->delete( $where, $where_format );

		$this->afterDelete( $success );
		
		return $success;
	}
	
	protected function beforeDelete( &$where, &$where_format ){}
	protected function afterDelete( &$success ){}
	
	
	public function update_var( $name, $value, array $where ){
		return $this->table->update_var( $name, $value, $where );
	}
	
	public function prepare( $query, $args ){
		return $this->table->prepare( $query, $args );	
	}
	
	public function query( $query ){
		return $this->table->query( $query );	
	}
	
	public function get_var( $query = null, $x = 0, $y = 0 ) {
		return $this->table->get_var( $query, $x, $y );
	}
	
	public function get_row( $query = null, $output = OBJECT, $y = 0 ) {
		return $this->table->get_row( $query, $output, $y );	
	}
	
	public function get_col( $query = null , $x = 0 ) {
		return $this->table->get_col( $query, $x );	
	}
	
	public function get_results( $query = null, $output = OBJECT ) {
		return $this->table->get_results( $query, $output );
	}

}


class Node_Object_Factory {
	
	function make( &$data, $class ){
		return new $class( $data );
	}
	
}

abstract class Node_ObjectModel extends ObjectModel {
	
	protected $object_class;
	
	static protected $_instance;
	
	public function set_object_class( $class ){
		$this->object_class = $class;
		return $this;	
	}
	
	public function get_object_class(){
		return $this->object_class;
	}
	
	public function forge_object( &$data ){
		
		if ( !isset($this->object_class) )
			return $data;
		
		return Node_Object_Factory::make( $data, $this->object_class );
	}
	
	
}

class MY_Node_ObjectModel extends Node_ObjectModel {
	
	static protected $_instance;
	
	protected $object_class = 'MY_Object';
		
}
