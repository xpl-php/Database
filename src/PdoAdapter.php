<?php

namespace xpl\Database;

use xpl\Data\AdapterInterface;
use PDO;
use PDOException;
use RuntimeException;

class PdoAdapter implements AdapterInterface 
{
	
	const EXCEPTION_CODE = 417;
	
	/**
	 * @var \xpl\Database\Config\Database
	 */
	protected $config;
	
	/**
	 * @var \xpl\Database\Connection
	 */
	protected $connection;
	
	/**
	 * @var \PDOStatement
	 */
	protected $statement;
	
	/**
	 * @var int
	 */
	protected $numQueries;
	
	/**
	 * @var int
	 */
	protected $fetchMode = PDO::FETCH_ASSOC;
	
	/**
	 * @var array
	 */
	protected $tables;
	
	/**
	 * @param \xpl\Database\Config\Database
	 */
	public function __construct(Config\Database $config) {
		$this->config = $config;
		$this->numQueries = 0;
		$this->tables = array();
	}

	/**
	 * @return \PDOStatement
	 * @throws \PDOException
	 */
	public function getStatement() {
		
		if ($this->statement === null) {
			throw new PDOException("There is no PDOStatement object for use.");
		}
		
		return $this->statement;
	}

	/**
	 * @return void
	 */
	public function connect() {
		if (null === $this->connection) {
			try {
				$this->connection = new Connection(
					$this->config->getDsn(), 
					$this->config->get('user'),
					$this->config->get('password'),
					$this->config->get('driver_options')
				);
				$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			} catch (PDOException $e) {
				throw new RuntimeException($e->getMessage(), static::EXCEPTION_CODE, $e);
			}
		}
	}
	
	public function getConnection() {
		if (null === $this->connection) {
			$this->connect();
		}
		return $this->connection;
	}

	/**
	 * @return void
	 */
	public function disconnect() {
		$this->connection = null;
	}
	
	/**
	 * @return $this
	 * @throws RuntimeException
	 */
	public function prepare($sql, array $options = array()) {
		try {
			$this->statement = $this->getConnection()->prepare($sql, $options);
		} catch (PDOException $e) {
			throw new RuntimeException($e->getMessage(), static::EXCEPTION_CODE, $e);
		}
		return $this;
	}
	
	/**
	 * @return $this
	 * @throws RuntimeException
	 */
	public function execute(array $parameters = array()) {
		try {
			$this->getStatement()->execute($parameters);
			$this->numQueries++;
		} catch (PDOException $e) {
			throw new RuntimeException($e->getMessage(), static::EXCEPTION_CODE, $e);
		}
		return $this;
	}

	/**
	 * @return int
	 * @throws RuntimeException
	 */
	public function countAffectedRows() {
		try {
			return $this->getStatement()->rowCount();
		} catch (PDOException $e) {
			throw new RuntimeException($e->getMessage(), static::EXCEPTION_CODE, $e);
		}
	}

	/**
	 * @return int
	 */
	public function getLastInsertId($name = null) {
		return $this->getConnection()->lastInsertId($name);
	}
	
	/**
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function fetch($fetchStyle = null, $cursorOrientation = null, $cursorOffset = null) {
		if ($fetchStyle === null) {
			$fetchStyle = $this->fetchMode;
		}
		try {
			return $this->getStatement()->fetch($fetchStyle, $cursorOrientation, $cursorOffset);
		} catch (PDOException $e) {
			throw new RuntimeException($e->getMessage(), static::EXCEPTION_CODE, $e);
		}
	}

	/**
	 * @return array
	 * @throws RuntimeException
	 */
	public function fetchAll($fetchStyle = null, $column = 0) {
		if ($fetchStyle === null) {
			$fetchStyle = $this->fetchMode;
		}
		try {
			if ($fetchStyle === PDO::FETCH_COLUMN) {
				return $this->getStatement()->fetchAll($fetchStyle, $column);
			} else {
				return $this->getStatement()->fetchAll($fetchStyle);
			}
		} catch (PDOException $e) {
			throw new RuntimeException($e->getMessage(), static::EXCEPTION_CODE, $e);
		}
	}

	/**
	 * @return $this
	 */
	public function select($table, array $bind = array(), $boolOperator = "AND") {
		
		if ($bind) {
			$where = array();
			foreach($bind as $col => $value) {
				unset($bind[$col]);
				$bind[":".$col] = $value;
				$where[] = $col." = :".$col;
			}
		}

		$sql = "SELECT * FROM ".$table.(($bind) ? " WHERE ".implode(" ".$boolOperator." ", $where) : " ");
		
		$this->prepare($sql)->execute($bind);
		
		return $this;
	}
	
	public function selectLike($table, array $bind = array(), $boolOperator = "AND") {
		
		if ($bind) {
			$where = array();
			foreach($bind as $col => $value) {
				unset($bind[$col]);
				$bind[":".$col] = $value;
				$where[] = $col." LIKE :".$col;
			}
		}

		$sql = "SELECT * FROM ".$table.(($bind) ? " WHERE ".implode(" ".$boolOperator." ", $where) : " ");
		
		$this->prepare($sql)->execute($bind);
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function insert($table, array $bind) {

		$cols = implode(", ", array_keys($bind));
		$values = implode(", :", array_keys($bind));

		foreach($bind as $col => $value) {
			unset($bind[$col]);
			$bind[":".$col] = $value;
		}

		$sql = "INSERT INTO ".$table." (".$cols.")  VALUES (:".$values.")";
		
		return (int)$this->prepare($sql)->execute($bind)->getLastInsertId();
	}
	
	/**
	 * @return int
	 */
	public function update($table, array $bind, $where = "") {
		
		$set = array();
		foreach($bind as $col => $value) {
			unset($bind[$col]);
			$bind[":".$col] = $value;
			$set[] = $col." = :".$col;
		}
		
		if (! empty($where) && is_array($where)) {
			$whr = array();
			foreach($where as $key => $value) {
				if (is_numeric($key)) {
					$whr[] = $value;
				} else {
					$whr[] = $key.' = '.$value;
				}
			}
			$where = implode(' AND ', $whr);
		}
		
		$sql = "UPDATE ".$table." SET ".implode(", ", $set).(($where) ? " WHERE ".$where : " ");
		
		return $this->prepare($sql)->execute($bind)->countAffectedRows();
	}
	
	/**
	 * @return int
	 */
	public function delete($table, $where = "") {
		
		if (! empty($where) && is_array($where)) {
			$whr = array();
			foreach($where as $key => $value) {
				if (is_numeric($key)) {
					$whr[] = $value;
				} else {
					$whr[] = $key.' = '.$value;
				}
			}
			$where = implode(' AND ', $whr);
		}
		
		$sql = "DELETE FROM ".$table.(($where) ? " WHERE ".$where : " ");
		
		return $this->prepare($sql)->execute()->countAffectedRows();
	}
	
	/**
	 * @return int
	 */
	public function getNumQueries() {
		return $this->numQueries;
	}
	
	/**
	 * Returns a table "gateway" instance for the given DB table.
	 * 
	 * @param string $name DB table name.
	 * @return \xpl\Database\Table
	 * @throws \RuntimeException if a nonexistant table is requested.
	 */
	public function table($name) {
		
		if (! isset($this->tables[$name])) {
			
			if ($this->getConnection()->getDriverName() !== 'sqlite' && ! $this->isTable($name)) {
				throw new \RuntimeException("Invalid database table: '$name'.");
			}
			
			$this->tables[$name] = new Table($name, $this);
		}
		
		return $this->tables[$name];
	}
	
	public function isTable($name) {
		return in_array($name, $this->getConnection()->getInfoSchema()->getTables(), true);
	}
	
	/**
	 * Provides an accessor to table() method.
	 */
	public function __get($table_name) {
		return $this->table($table_name);
	}
	
}
