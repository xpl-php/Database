<?php
/**
 * @package Wells.Database
 * @subpackage functions
 */

/**
 * Registers a table schema.
 */
function register_schema( Phpf\Database\Table\Schema $schema ){
	Phpf\Database\Database::i()->registerSchema($schema);
}

/**
 * Returns the database instance
 */
function db(){
	return Phpf\Database\Database::i();	
}

/**
 * Returns a Schema instance
 */
function schema( $name ){
	return Phpf\Database\Database::i()->schema($name);	
}

/**
 * Returns a Table instance
 */
function table( $name ){
	return Phpf\Database\Database::i()->table($name);	
}

/**
 * Returns array of installed table names.
 */
function db_get_installed_tables(){
	return Phpf\Database\Database::i()->getInstalledTables();
}

/**
 * Returns number of queries run during current request.
 */
function db_get_query_count(){
	return Phpf\Database\Database::i()->num_queries;
}

/**
 * Creates a registered table in the database.
 */
function db_create_table( $table ){
	
	$db = Phpf\Database\Database::i();
	
	if ( $db->isTableInstalled($table) )
		return 2;
	
	$schema = $db->schema($table);
	
	$create_ddl = Phpf\Database\Sql\Writer::createTable($schema);

	$db->query($create_ddl);
	
	return $db->isTableInstalled($table) ? 1 : 0;
}

/**
 * Drops a registered table from the database.
 */
function db_drop_table( $table ) {
	
	$db = Phpf\Database\Database::i();
	
	if ( ! $db->isTableInstalled($table) )
		return 2;

	$schema = $db->schema($table);

	$drop_ddl = Phpf\Database\Sql\Writer::dropTable($schema);

	$db->query($drop_ddl);
	
	return $db->isTableInstalled($table) ? 1 : 0;
}
