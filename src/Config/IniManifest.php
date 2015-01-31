<?php

namespace xpl\Database\Config;

class IniManifest extends AbstractManifest
{
	
	const
		/**
		 * Manifest filename used if given a directory path.
		 * 
		 * @var string
		 */ 
		FILENAME = 'db.ini';
	
	/**
	 * Set directory or file path to manifest file.
	 * 
	 * @param string $path File path to INI manifest file or directory.
	 */
	public function __construct($path) {
		
		if (! $file = realpath($path)) {
			throw new \InvalidArgumentException("Invalid manifest file path: '$path'.");
		}
		
		if (is_dir($file)) {
			$file .= DIRECTORY_SEPARATOR.static::FILENAME;
		}
		
		parent::__construct($file);
	}
	
	protected function readFile($file) {
		return parse_ini_file($file, true);
	}
	
}
