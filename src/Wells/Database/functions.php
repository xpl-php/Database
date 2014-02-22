<?php
/**
 * @package Wells.Database
 * @subpackage functions
 */

/**
 * Registers a table schema.
 */
function register_schema( Wells\Database\Table\Schema $schema ){
	Wells\Database\Database::i()->registerSchema($schema);
}

/**
 * Registers an Model\Controller instance
 */	
function register_model_controller( Wells\Database\Model\Controller $controller ){
	Wells\Util\Registry::addToGroup( 'model-controller', $controller, $controller->getTableBasename() );
	return true;
}

/**
 * Returns the database instance
 */
function db(){
	return Wells\Database\Database::i();	
}

/**
 * Returns a Schema instance
 */
function schema( $name ){
	return Wells\Database\Database::i()->schema($name);	
}

/**
 * Returns a Table instance
 */
function table( $name ){
	return Wells\Database\Database::i()->table($name);	
}

/**
 * Returns an Model\Controller instance
 */	
function model_controller( $table_basename ){
	return Wells\Util\Registry::getFromGroup( 'model-controller', $table_basename );
}

/**
 * Returns array of installed table names.
 */
function db_get_installed_tables(){
	return Wells\Database\Database::i()->getInstalledTables();
}

/**
 * Returns number of queries run during current request.
 */
function db_get_query_count(){
	return Wells\Database\Database::i()->num_queries;
}

/**
 * Creates a registered table in the database.
 */
function db_create_table( $table ){
	
	$db = Wells\Database\Database::i();
	
	if ( $db->isTableInstalled($table) )
		return 2;
	
	$schema = $db->schema($table);
	
	$create_ddl = Wells\Database\Sql\Writer::createTable($schema);

	$db->query($create_ddl);
	
	return $db->isTableInstalled($table) ? 1 : 0;
}

/**
 * Drops a registered table from the database.
 */
function db_drop_table( $table ) {
	
	$db = Wells\Database\Database::i();
	
	if ( ! $db->isTableInstalled($table) )
		return 2;

	$schema = $db->schema($table);

	$drop_ddl = Wells\Database\Sql\Writer::dropTable($schema);

	$db->query($drop_ddl);
	
	return $db->isTableInstalled($table) ? 1 : 0;
}
