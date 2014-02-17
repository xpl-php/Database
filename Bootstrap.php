<?php
/**
* @package Wells.Database
*/

define( 'OBJECT', 'OBJECT', true );
define( 'OBJECT_K', 'OBJECT_K' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'ARRAY_N', 'ARRAY_N' );

if ( ! defined( 'DATABASE_TABLE_PREFIX' ) ){
	define( 'DATABASE_TABLE_PREFIX', '' );	
}

require __DIR__ . '/DB.php';
require_once __DIR__ . '/functions.php';

class_alias( 'Wells\Database\DB', 'DB' );

DB::init();	

