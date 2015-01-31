<?php

namespace xpl\Database\Connection;

use xpl\Database\Factory;
use xpl\Database\Config\AbstractManifest as Manifest;
use RuntimeException;

class Pool 
{
	
	protected $factory;
	protected $manifest;
	protected $configs;
	protected $connections;
	
	public function __construct(Factory $factory, Manifest $manifest = null) {
		$this->factory = $factory;
		$this->manifest = $manifest;
		$this->configs = array();
		$this->connections = array();
	}
	
	/**
	 * Returns the config/connection factory.
	 * 
	 * @return \xpl\Database\Factory
	 */
	public function getFactory() {
		return $this->factory;
	}
	
	/**
	 * Returns the config manifest, if set.
	 * 
	 * @return \xpl\Database\Config\AbstractManifest 
	 */
	public function getManifest() {
		return $this->manifest;
	}
	
	/**
	 * Sets the config manifest.
	 * 
	 * @param \xpl\Database\Config\AbstractManifest $manifest
	 */
	public function setManifest(Manifest $manifest) {
		$this->manifest = $manifest;
	}
	
	/**
	 * Whether a config manifest is set.
	 * 
	 * @return boolean
	 */
	public function hasManifest() {
		return isset($this->manifest);
	}
	
	/**
	 * Whether a given database has been configured.
	 * 
	 * @param string $database Database name.
	 * @return boolean
	 */
	public function isConfigured($database) {
		return isset($this->configs[$database]);
	}
	
	/**
	 * Whether a given database has been connected.
	 * 
	 * @param string $database Database name.
	 * @return boolean
	 */
	public function isConnected($database) {
		return isset($this->connections[$database]);
	}
	
	/**
	 * Returns an array of settings for a given database.
	 * 
	 * @param string $dbname Database name.
	 * 
	 * @return array Associative array of database settings.
	 */
	public function getConfig($database) {
		
		if (! isset($this->configs[$database])) {
			$this->createConfig($database);
		}
		
		return $this->configs[$database];
	}
	
	/**
	 * Returns a PDO connection for a configured database, creating if necessary.
	 * 
	 * @param string $database Database name.
	 * @return \xpl\Database\PdoAdapter PDO adapter.
	 */
	public function getConnection($database) {
		
		if (! isset($this->connections[$database])) {
			$this->createConnection($database);
		}
		
		return $this->connections[$database];
	}
	
	/**
	 * Creates a database config object with the given settings.
	 * 
	 * @param string $database Database name.
	 * @param array $settings Association array of database config settings.
	 */
	public function configure($database, array $settings) {
		
		$settings['name'] = $database;
		
		$this->configs[$database] = $this->factory->configure($settings);
	}
	
	/**
	 * Returns the total number of DB queries executed by all connections 
	 * during the current request.
	 * 
	 * @return int|null
	 */
	public function getNumQueries() {
		$n = 0;
		foreach($this->connections as $c) {
			$n += $c->getNumQueries();
		}
		return $n;
	}
	
	/** Alias of connect() */
	public function get($db) {
		return $this->connect($db);
	}
	
	/**
	 * Creates a config object for a given database from manifest settings.
	 * 
	 * @param string $database Database name.
	 */
	protected function createConfig($database) {
		
		if (! isset($this->manifest)) {
			throw new RuntimeException("Cannot get config settings for '$database': no manifest set.");
		}
		
		$settings = $this->manifest->getConfigSettings($database);
		
		if (! $settings) {
			throw new RuntimeException("Cannot configure '$database': no settings found.");
		}
		
		$this->configure($database, $settings);
	}
	
	/**
	 * Creates a connection object for a given database.
	 * 
	 * @param string $database Database name.
	 */
	protected function createConnection($database) {
		$this->connections[$database] = $this->factory->connect($this->getConfig($database));
	}
}
