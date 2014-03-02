<?php
/**
 * @package Phpf.Database
 * 
 * This is an alternative to the Service Provider.
 * Do not use both..
 */

include __DIR__ . '/functions.php';

// FluentPDO autoloader
spl_autoload_register( function ($class){
		
	if ( 0 === strpos($class, 'FluentPDO') ){
			
		$path = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
		
		require $path;
	}

} );
