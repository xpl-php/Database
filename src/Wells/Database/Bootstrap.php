<?php
/**
* @package Wells.Database
*/

define( 'OBJECT', 'OBJECT', true );
define( 'OBJECT_K', 'OBJECT_K' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'ARRAY_N', 'ARRAY_N' );

require __DIR__ . '/FluentPDO/FluentPDO.php';
require __DIR__ . '/functions.php';


/** @TODO Move to application

class_alias( 'Wells\Database\Database', 'Database' );

Wells\Database\Database::init(
	DATABASE_NAME, 
	DATABASE_HOST, 
	DATABASE_USER, 
	DATABASE_PASSWORD,
	DATABASE_TABLE_PREFIX
);

*/