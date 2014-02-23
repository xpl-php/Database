<?php
/**
* @package Phpf.Database
*/

require __DIR__ . '/functions.php';

// FluentPDO autoloader
spl_autoload_register( function ($class){
		
	if ( 0 !== strpos($class, 'FluentPDO') )
		return;
	
	$path = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
	
	require $path;
} );
