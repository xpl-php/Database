<?php

namespace xpl\Database;

class Factory
{
	
	/**
	 * Classname for configs.
	 * @var string
	 */	
	const CONFIG_CLASS = 'xpl\Database\Config\Database';
	
	/**
	 * Classname for connections/adapters.
	 * @var string
	 */
	const ADAPTER_CLASS = 'xpl\Database\PdoAdapter';
	
	/**
	 * Default configuration settings.
	 * @var array
	 */
	protected $config_defaults = array(
		'driver' => 'mysql',
		'host' => '127.0.0.1',
		'name' => null,
		'user' => null,
		'password' => null,
		'driver_options' => array()
	);
	
	public function getConfigDefaults() {
		return $this->config_defaults;
	}
	
	public function setConfigDefaults(array $defaults) {
		$this->config_defaults = array_replace($this->config_defaults, $defaults);
	}
	
	public function configure(array $args) {
		return $this->create(static::CONFIG_CLASS, array_replace($this->getConfigDefaults(), $args));
	}
	
	public function connect(Config\Database $config) {
		
		$connection = $this->create(static::ADAPTER_CLASS, $config);
		
		$connection->connect();
		
		return $connection;
	}
	
	protected function create($class, $arg = null) {
		return new $class($arg);
	}
	
}
