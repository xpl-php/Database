<?php

namespace Phpf\Database {
	
	class Functional {
		// dummy class
	}
}

namespace {
	
	/**
	 * Creates and registers a database table schema.
	 */
	function db_table_schema($name, array $columns, $primary_key = 'id', array $unique_keys = null, array $keys = null){
		
		$schema = array(
			'table_basename' => $name,
			'columns' => array(),
			'primary_key' => $primary_key,
			'unique_keys' => array(),
			'keys' => array()
		);
		
		foreach($columns as $col => $dtype){
			$schema['columns'][$col] = strtolower($dtype);
		}
		
		if ( isset($unique_keys) ){
			foreach($unique_keys as $idx => $key){
				if (is_numeric($idx)){
					$schema['unique_keys'][$key] = $key;
				} else {
					$schema['unique_keys'][$idx] = $key;
				}
			}
		}
		
		if ( isset($keys) ){
			foreach($keys as $idx => $key){
				if (is_numeric($idx)){
					$schema['keys'][$key] = $key;
				} else {
					$schema['keys'][$idx] = $key;
				}
			}
		}
		
		\Phpf\Database\Database::instance()->registerSchema(new \Phpf\Database\Table\Schema($schema));
	}
			
	/**
	 * Creates and registers a table schema.
	 */
	function db_register_schema( array $data ){
		if ($schema = new \Phpf\Database\Table\Schema($data)) {
			\Phpf\Database\Database::instance()->registerSchema($schema);
			return true;
		}
		return false;
	}
		
	/**
	 * Returns a Schema instance
	 */
	function db_get_schema( $name ){
		return \Phpf\Database\Database::instance()->schema($name);	
	}
	
	/**
	 * Returns a Table instance
	 */
	function db_get_table( $name ){
		return \Phpf\Database\Database::instance()->table($name);	
	}
	
	/**
	 * Returns array of installed table names.
	 */
	function db_get_installed_tables(){
		return \Phpf\Database\Database::instance()->getInstalledTables();
	}
	
	/**
	 * Returns number of queries run during current request.
	 */
	function db_get_query_count(){
		return \Phpf\Database\Database::instance()->num_queries;
	}
	
	/**
	 * Creates a registered table in the database.
	 */
	function db_create_table( $table ){
		
		$db = \Phpf\Database\Database::instance();
		
		if ($db->isTableInstalled($table))
			return 2;
		
		$schema = $db->schema($table);
		
		$create_ddl = \Phpf\Database\Sql\Writer::createTable($schema);
	
		$db->query($create_ddl);
		
		return $db->isTableInstalled($table) ? 1 : 0;
	}
	
	/**
	 * Drops a registered table from the database.
	 */
	function db_drop_table( $table ) {
		
		$db = \Phpf\Database\Database::instance();
		
		if (! $db->isTableInstalled($table))
			return 2;
	
		$schema = $db->schema($table);
	
		$drop_ddl = \Phpf\Database\Sql\Writer::dropTable($schema);
	
		$db->query($drop_ddl);
		
		return $db->isTableInstalled($table) ? 1 : 0;
	}

}
