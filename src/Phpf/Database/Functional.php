<?php

namespace Phpf\Database {
	
	class Functional {
		// dummy class
	}
}

namespace {
	
	use Phpf\Database\Database;
		
	/**
	 * Returns the database instance
	 */
	function database(){
		return Database::instance();	
	}
		
	/**
	 * Creates and registers a table schema.
	 */
	function db_register_schema( array $data ){
		if ( $schema = new \Phpf\Database\Table\Schema($data) ){
			Database::instance()->registerSchema($schema);
			return true;
		}
		return false;
	}
		
	/**
	 * Returns a Schema instance
	 */
	function db_get_schema( $name ){
		return Database::instance()->schema($name);	
	}
	
	/**
	 * Returns a Table instance
	 */
	function db_get_table( $name ){
		return Database::instance()->table($name);	
	}
	
	/**
	 * Returns array of installed table names.
	 */
	function db_get_installed_tables(){
		return Database::instance()->getInstalledTables();
	}
	
	/**
	 * Returns number of queries run during current request.
	 */
	function db_get_query_count(){
		return Database::instance()->num_queries;
	}
	
	/**
	 * Creates a registered table in the database.
	 */
	function db_create_table( $table ){
		
		$db = Database::instance();
		
		if ( $db->isTableInstalled($table) )
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
		
		$db = Database::instance();
		
		if ( ! $db->isTableInstalled($table) )
			return 2;
	
		$schema = $db->schema($table);
	
		$drop_ddl = \Phpf\Database\Sql\Writer::dropTable($schema);
	
		$db->query($drop_ddl);
		
		return $db->isTableInstalled($table) ? 1 : 0;
	}

}
