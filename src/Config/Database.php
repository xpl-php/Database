<?php

namespace xpl\Database\Config;

class Database extends AbstractConfig 
{
	
	public function getDsn() {
		
		if (! isset($this->driver)) {
			throw new \RuntimeException("Cannot get database DSN (no driver set).");
		}
		
		if (! in_array($this->driver, pdo_drivers(), true)) {
			throw new \InvalidArgumentException("Invalid PDO driver: '$driver'.");
		}
		
		return pdo_dsn($this->get('driver'), $this->get('host'), $this->get('name'), $this->get('port'));
	}
	
}
