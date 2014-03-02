<?php
/**
 * @package Phpf.Database
 * @subpackage Service
 */

namespace Phpf\Database\Service;

class Provider implements \Phpf\Service\Provider {
	
	protected $provided = false;
	
	public function isProvided(){
		return $this->provided;
	}
	
	public function provide(){
			
		include dirname(__DIR__) . '/functions.php';
		
		// FluentPDO autoloader
		spl_autoload_register( function ($class){
			
			if ( 0 === strpos($class, 'FluentPDO') ){
			
				$path = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
				
				require $path;
			}
			
		} );
		
		$this->provided = true;	
	}
	
}
