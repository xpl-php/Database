<?php

namespace Phpf\Database;

use Phpf\Common\Container;
use PDO;

class Config extends Container {
	
	public function __construct($data = null) {
		if (isset($data)) {
			if (is_string($data)) {
				$this->setDatabase($data);
			} else {
				$this->import($data);
			}
		}
	}
	
	public function setDatabase($database) {
		$this->set('database', $database);
		return $this;
	}
	
	public function setHost($host) {
		$this->set('host', $host);
		return $this;
	}
	
	public function setUser($user) {
		$this->set('user', $user);
		return $this;
	}
	
	public function setPassword($pass) {
		$this->set('password', $pass);
		return $this;
	}
	
	public function setTablePrefix($prefix) {
		$this->set('table_prefix', $prefix);
		return $this;
	}
	
	public function setDriver($driver) {
			
		if (! in_array($driver, PDO::getAvailableDrivers(), true)) {
			throw new \InvalidArgumentException("Unsupported PDO driver '$driver'.");
		}
		
		$this->set('driver', $driver);
		return $this;
	}
	
	public function setAsPrimary($value) {
		$this->set('primary', (bool)$value);
		return $this;
	}
	
	public function getDatabase() {
		return $this->get('database');
	}
	
	public function getHost() {
		return $this->get('host');
	}
	
	public function getUser() {
		return $this->get('user');
	}
	
	public function getPassword() {
		return $this->get('password');
	}
	
	public function getTablePrefix() {
		return $this->get('table_prefix');
	}
	
	public function getDriver() {
		return $this->get('driver');
	}
	
	public function isPrimary() {
		return $this->exists('primary') ? $this->get('primary') : false;
	}

	public function getDsn() {
		return $this->getDriver().':dbname='.$this->getDatabase().';host='.$this->getHost();
	}
}
