<?php

namespace Wells\Database;

class Table {
	
	protected $db;
	
	protected $schema;
	
	function __construct( Table\Schema $schema, DB &$db ){
		
		$this->schema = $schema;
		
		$this->db =& $db;
	}
	
	function get_schema(){
		return $this->schema;	
	}
	
	function schema(){
		return $this->schema;	
	}
	
	/** ================================
				DB methods
	================================= */
	
	/**
	* Insert a row into a table.
	*
	* @see DB::insert()
	*/
	public function insert( $data, $format = null ){
		return $this->db->insert( $this->schema->table, $data, $format );
	}

	/**
	* Replace a row into a table.
	*
	* @see DB::replace()
	*/
	public function replace( $data, $format = null ) {
		return $this->db->replace( $this->schema->table, $data, $format, 'REPLACE' );
	}

	/**
	* Update a row in the table
	*
	* @see DB::update()
	*/	
	public function update( $data, $where, $format = null, $where_format = null ){
		return $this->db->update( $this->schema->table, $data, $where, $format, $where_format );
	}
	
	/**
	* Delete a row in the table
	*
	* @see DB::delete()
	*/
	public function delete( $where, $where_format = null ) {
		return $this->db->delete( $this->schema->table, $where, $where_format );
	}
	
	/**
	* Update a single row value in the table
	*
	* @see DB::update()
	*/
	public function update_var( $name, $value, array $where ){
		return $this->update( array($name => $value), $where );
	}
	
	public function prepare( $query, $args ){
		return $this->db->prepare( $query, $args );	
	}
	
	public function query( $query ){
		return $this->db->query( $query );	
	}
	
	public function get_var( $query = null, $x = 0, $y = 0 ) {
		return $this->db->get_var( $query, $x, $y );
	}
	
	public function get_row( $query = null, $output = OBJECT, $y = 0 ) {
		return $this->db->get_row( $query, $output, $y );	
	}
	
	public function get_col( $query = null , $x = 0 ) {
		return $this->db->get_col( $query, $x );	
	}
	
	public function get_results( $query = null, $output = OBJECT ) {
		return $this->db->get_results( $query, $output );
	}
	
}
