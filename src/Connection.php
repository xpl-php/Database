<?php

namespace xpl\Database;

use PDO;

class Connection extends PDO
{
	
	protected $_escape = array();
	protected $infoSchema;
	
	public function __construct($dsn, $user = null, $password = null, $attributes = array()) {
		
		parent::__construct($dsn, $user, $password, $attributes);
		
		switch($this->getAttribute(PDO::ATTR_DRIVER_NAME)) {
			
			case 'mysql':
				$this->_escape['open'] = $this->_escape['close'] = '`';
				break;
				
			case 'mssql':
				$this->_escape['open'] = '[';
				$this->_escape['close'] = ']';
				break;
			
			default:
				$this->_escape['open'] = $this->_escape['close'] = '"';
				break;
		}
	}
	
	/**
	 * Escapes names (tables, columns etc.)
	 */
	public function quoteName($name) {
		$names = array();
		
		foreach (explode(".", $name) as $name) {
			
			$dbl_close = str_replace($this->_escape['close'], $this->_escape['close'].$this->_escape['close'], $name);
			
			$names[] = $this->_escape['open'].$dbl_close.$this->_escape['close'];
		}
		
		return implode(".", $names);
	}

	/**
	 * Prepares a query, binds parameters, and executes it.
	 * If you're going to run the query multiple times, it's faster to prepare once, and reuse the
	 * statement.
	 */
	public function prepareExecute($sql, $params = null) {

		$stmt = $this->prepare($sql);

		if (is_array($params)) {
			$stmt->execute($params);
		} else {
			$stmt->execute();
		}

		return $stmt;
	}

	public function getInfoSchema() {
	
		if (null === $this->infoSchema) {
			$this->infoSchema = new InformationSchema($this);
		}
		
		return $this->infoSchema;
	}
	
	public function getDriverName() {
		return $this->getAttribute(PDO::ATTR_DRIVER_NAME);
	}
	
}
