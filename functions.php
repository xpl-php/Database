<?php
/**
 * @package Wells.Database
 * @subpackage functions
 */
 
/**
 * Creates and registers an object model.
 * 
 * @param string $class The model's class.
 * @param string $table_basename The DB table basename.
 * @param string|null $class_alias An alias to register for the class.
 * @return bool True if successful, false if not.
 */
function create_register_model( $class, $table_basename, $class_alias = null ){
	
	if ( ! class_exists( $class ) ){
		trigger_error( "Unknown model class '$class'." );
		return false;
	}
	
	$model = $class::i();
	
	$table = table( $table_basename );
	
	if ( ! $table ){
		trigger_error( "Unknown table '$table_basename'." );
		return false;
	}
	
	$model->set_table( $table );
	
	register_model( $model );
	
	if ( ! empty($class_alias) )
		class_alias( $class, $class_alias );
	
	return true;
}

/**
 * Registers a DB_ObjectModel instance.
 */	
function register_model( Wells\Database\ObjectModel $model ){
	Registry::addToGroup( 'model', $model, $model->get_table_basename() );
	return true;
}

/**
 * Returns a DB_ObjectModel instance.
 */	
function get_model( $table_basename ){
	return Registry::getFromGroup( 'model', $table_basename );
}

/**
 * Alias for get_model()
 * @see get_model()
 */	
function model( $table_basename ){
	return get_model($table_basename);
}

/**
 * Returns DB instance
 */
function db(){
	return DB::i();	
}

/**
 * Returns a DB_Table_Schema instance.
 */
function schema( $name ){
	return DB::i()->schema( $name );	
}

/**
 * Returns a DB_Table instance.
 */
function table( $name ){
	return DB::i()->table( $name );	
}

/**
 * Returns array of installed table names.
 */
function db_get_installed_tables(){
	return DB::i()->get_col("SHOW TABLES", 0);
}

/**
 * Creates a registered table in the database.
 */
function db_create_table( $table ){
	
	$db =& DB::i();
	
	if ( $db->table_exists( $table ) )
		return 2;
	
	$schema = $db->schema( $table );
	
	$create_ddl = Wells\Database\Sql\Writer::create_table( $schema );

	$db->query( $create_ddl );
	
	return $db->table_exists( $table ) ? 1 : 0;
}

/**
 * Drops a registered table from the database.
 */
function db_drop_table( $table ) {
	
	$db =& DB::i();
	
	if ( ! $db->table_exists( $table ) )
		return 2;

	$schema = $db->schema( $table );

	$drop_ddl = Wells\Database\Sql\Writer::drop_table( $schema );

	$db->query( $drop_ddl );
	
	return $db->table_exists( $table ) ? 1 : 0;
}

/**
 * Returns number of queries run during current request.
 */
function get_num_queries(){
	return DB::i()->get_num_queries();
}
