<?php

namespace xpl\Database\Config;

abstract class AbstractManifest
{
	
	protected $file;
	protected $contents;
	
	/**
	 * Set path to the config manifest file.
	 * 
	 * @param string $manifest_file Path to config manifest file.
	 */
	public function __construct($manifest_file) {
		
		if (! is_readable($manifest_file)) {
			throw new \RuntimeException("Manifest file not readable: '$manifest_file'.");
		}
		
		$this->file = $manifest_file;
	}
	
	/**
	 * Returns the manifest file path.
	 * 
	 * @return string
	 */
	public function getFile() {
		return $this->file;
	}
	
	/**
	 * Returns an array of settings for a given database.
	 * 
	 * @param string $database Database name.
	 * @return array Associative array of database settings.
	 */
	public function getConfigSettings($database) {
		
		if (! isset($this->contents)) {
			$this->contents = $this->getManifestContents();
		}
		
		return isset($this->contents[$database]) ? $this->contents[$database] : null;
	}
	
	/**
	 * Reads the manifest contents and stores in memory.
	 */
	protected function getManifestContents() {
		
		$contents = $this->readFile($this->file);
		
		foreach($contents as $database => &$args) {
			$args['name'] = $database;
		}
		
		return $contents;
	}
	
	/**
	 * Reads contents from a file and returns an array.
	 * 
	 * @param string $file File path.
	 * @return array File contents.
	 */
	abstract protected function readFile($file);
	
}
