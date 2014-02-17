<?php

namespace Wells\Database\Table;

class Schema {
	
	public 
		$table_basename,		// No prefix - required.
		
		$table,					// $table_basename + table prefix
		
		$columns = array(),		// assoc. array of "column => SQL definition" - required.
		
		$primary_key,			// string - required.
		
		$unique_keys = array(),	// indexed array (exclusive of $primary_key)
		
		$keys = array();		// indexed array (exclusive of $primary_key and $unique_keys)
	
	
	function __construct( array $args ){
		
		$class_now = get_class( $this );
		
		foreach( $args as $key => $value ){
			if ( property_exists( $class_now, $key ) ){
				$this->$key = $value;	
			}	
		}
		
		// set the table name
		if ( empty( $this->table ) ){
			
			if ( ! isset( $this->table_basename ) )
				trigger_error( 'Must set DB_Table_Schema property $table_basename.');		
			
			$this->table = db()->get_prefix() . $this->table_basename;	
		}
		
		db()->register_schema( $this );
	}
	
	/**
	* Overwrite this to use a custom table class (must extend DB_Table).
	*/
	public function get_table_class(){
		return false;
	}
	
	/** 
	* Returns true if col exists
	*/
	public function is_column( $column ){
		return isset( $this->columns[ $column ] );
	}
	
	/** 
	* Returns true if col is key
	*/	
	public function is_key( $column ){
		return $column === $this->primary_key || in_array($column, $this->unique_keys) || in_array($column, $this->keys);
	}

	/**
	* Returns a column's format for SQL 
	*
	* integer => %d
	* float => %f
	* string => %s (default)
	*/
	public function get_column_format( $column ){
		
		if ( !$this->is_column( $column ) )
			return false;
		
		$field = strtolower( $this->columns[ $column ] );
		
		if ( strpos($field, 'int') !== false || strpos($field, 'time') !== false )
			return '%d';
		if ( strpos($field, 'float') !== false )
			return '%f';
		else
			return '%s';
	}
	
	/** 
	* Returns field length max
	*/
	public function get_column_length( $column ){
		
		if ( !$this->is_column( $column ) )
			return false;
		
		$field = $this->columns[ $column ];
		
		$_pos = strpos( $field, '(' );
		
		if ( false === $_pos ) return null;
		
		$_start = $_pos + 1;
		$length = substr( $field, $_start, strpos( $field, ')' ) - $_start );
		
		// Floats/decimals can has two lengths: (3,5) => 123.12345
		if ( false !== strpos( $length, ',' ) ){
			$_n = explode( ',', $length );
			$length = array_sum( $_n );
		}
		
		return (int) $length;		
	}

	/**
	* Returns object properties as array.
	*/
	public function to_array(){
		return get_object_vars( $this );
	}
	
	/**
	* Returns SQL string for WHERE clause, given a column, value, 
	* and whether wildcards should be used if a LIKE statement is given.
	*/
	public function column_where_sql( $column, $value, $like_wildcard = false ){
		
		if ( !$this->is_column($column) ) return null;
		
		$format = $this->get_column_format($column);
		
		if ( '%s' === $format ){
			$value = esc_sql_like( $value );
			if ( $like_wildcard )
				$value = '%' . $value . '%';
			$return = "$column LIKE '$value'";	
		} else {
			$return = "$column = $value";
		}
		
		return $return;
	}
	
	function sql_col_where( $col, $val, $like_wild = false ){
		return $this->column_where_sql( $col, $val, $like_wild );	
	}
	
}