<?php
/**
 * @package Phpf.Database
 * @subpackage Service.Provider
 */

namespace Phpf\Database\Service;

class Provider implements Phpf\Service\Provider {
	
	protected $provided = false;
	
	public function isProvided(){
		return $this->provided;
	}
	
	public function provide(){
		
		$basedir = dirname(__DIR__);
			
		include $basedir . '/functions.php';
		
		// FluentPDO autoloader
		spl_autoload_register( function ($class) use($basedir){
				
			if ( 0 !== strpos($class, 'FluentPDO') )
				return;
			
			$path = $basedir . '/' . str_replace('\\', '/', $class) . '.php';
			
			require $path;
		} );
		
		$this->provided = true;	
	}
	
}
