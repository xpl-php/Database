<?php

namespace Phpf\Database\Table;

use Phpf\Database\Database;

class Schema {
	
	public $table_basename;			// No prefix - required.
	
	public $table;					// $table_basename + table prefix
		
	public $columns = array();		// assoc. array of "column => SQL definition" - required.
		
	public $primary_key;			// string - required.
		
	public $unique_keys = array();	// array (exclusive of $primary_key)
		
	public $keys = array();			// array (exclusive of $primary_key and $unique_keys)
	
	public function __construct( array $args ){
		
		foreach( $args as $key => $value ){
			$this->$key = $value;	
		}
	
		if ( ! isset($this->table_basename) ){
			throw new \RuntimeException('Must set Database\Table\Schema property $table_basename.');	
		}
	}
	
	/** 
	* Returns true if col exists
	*/
	public function isColumn( $column ){
		return isset( $this->columns[ $column ] );
	}
	
	/** 
	* Returns true if col is key
	*/	
	public function isKey( $column ){
		return (bool) (
			$column === $this->primary_key 
			|| isset($this->keys[$column]) 
			|| isset($this->unique_keys[$column])
		);
	}

	/**
	* Returns a column's format for SQL 
	*
	* integer => %d
	* float => %f
	* string => %s (default)
	*/
	public function getColumnFormat( $column ){
		
		if ( !$this->isColumn($column) )
			return false;
		
		$field = strtolower($this->columns[$column]);
		
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
	public function getColumnLength( $column ){
		
		if ( !$this->isColumn($column) )
			return false;
		
		$field = $this->columns[ $column ];
		
		if ( false === ($_pos = strpos($field, '(')) )
			return null;
		
		$_start = $_pos + 1;
		$length = substr( $field, $_start, strpos($field, ')') - $_start );
		
		// Floats/decimals can has two lengths: (3,5) => 123.12345
		if ( false !== strpos($length, ',') ){
			$_n = explode(',', $length);
			$length = array_sum($_n);
		}
		
		return (int) $length;		
	}

	/**
	* Returns object properties as array.
	*/
	public function toArray(){
		return get_object_vars($this);
	}
	
	/**
	* Returns SQL string for WHERE clause, given a column, value, 
	* and whether wildcards should be used if a LIKE statement is given.
	*/
	public function sqlColumnWhere( $col, $val, $like_wild = false ){
		
		if ( !$this->isColumn($column) ) return null;
		
		$format = $this->getColumnFormat($column);
		
		if ( '%s' === $format ){
			$value = \Phpf\Util\Str::escSqlLike($value);
			if ($like_wildcard)
				$value = '%' . $value . '%';
			$return = "$column LIKE '$value'";	
		} else {
			$return = "$column = $value";
		}
		
		return $return;
	}
	
}