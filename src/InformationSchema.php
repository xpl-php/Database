<?php

namespace xpl\Database;

use PDO;

/**
 * Provides access to introspection of the database schema.
 */
class InformationSchema {
	
	/**
	 * @var \xpl\Database\Connection
	 */
	protected $connection;
	
	protected $tables;
	protected $keys;
	protected $columns = array();
	protected $has_many = array();
	protected $belongs_to = array();
	
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	/**
	 * Returns list of tables in database.
	 * 
	 * Caches the results in memory.
	 * 
	 * @param boolean $force_refresh [Optional] Force a query to refresh the cache. Default false.
	 * 
	 */
	public function getTables($force_refresh = false) {
		
		if (null === $this->tables || true === $force_refresh) {
			
			$this->tables = array();
				
			switch ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME)) {
				
				case 'mysql' :
					$sql = "SHOW TABLES";
					break;
				
				case 'pgsql' :
					$sql = "SELECT CONCAT(table_schema,'.',table_name) AS name FROM information_schema.tables "
						."WHERE table_type = 'BASE TABLE' AND table_schema NOT IN ('pg_catalog','information_schema')";
					break;
				
				case 'sqlite' :
					$sql = 'SELECT name FROM sqlite_master WHERE type = "table"';
					break;
					
				default :
					throw new \RuntimeException("Meta not supported");
			}
			
			$result = $this->connection->query($sql);
			$result->setFetchMode(PDO::FETCH_NUM);
			
			foreach ($result as $row) {
				$this->tables[$row[0]] = $row[0];
			}
		}
		
		return $this->tables;
	}

	/**
	 * Returns reflection information about a table.
	 * 
	 * Caches the results in memory.
	 * 
	 * @param string $table Table name.
	 * @param boolean $force_refresh [Optional] Force a query to refresh the cache. Default false.
	 * @return array Column info
	 */
	public function getColumns($table, $force_refresh = false) {
		
		if (! isset($this->columns[$table]) || true === $force_refresh) {
			
			$meta = array();
			
			switch ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME)) {
					
				case 'pgsql' :
					list($schema, $table) = stristr($table, '.') ? explode(".", $table) : array('public', $table);
					$result = $this->connection->prepareExecute(
						"SELECT c.column_name, c.column_default, c.data_type, "
						."(SELECT MAX(constraint_type) AS constraint_type FROM information_schema.constraint_column_usage cu "
	            		."JOIN information_schema.table_constraints tc ON tc.constraint_name = cu.constraint_name "
	            		."AND tc.constraint_type = 'PRIMARY KEY' "
	            		."WHERE cu.column_name = c.column_name AND cu.table_name = c.table_name) AS constraint_type "
	            		."FROM information_schema.columns c "
	            		."WHERE c.table_schema = ".$this->connection->quote($schema)
	            		." AND c.table_name = ".$this->connection->quote($table)
					);
					$result->setFetchMode(PDO::FETCH_ASSOC);
					foreach ($result as $row) {
						$meta[$row['column_name']] = array(
							'pk' => $row['constraint_type'] == 'PRIMARY KEY',
							'type' => $row['data_type'],
							'blob' => preg_match('/(text|bytea)/', $row['data_type']),
						);
						if (stristr($row['column_default'], 'nextval')) {
							$meta[$row['column_name']]['default'] = null;
						} else if (preg_match("/^'([^']+)'::(.+)$/", $row['column_default'], $match)) {
							$meta[$row['column_name']]['default'] = $match[1];
						} else {
							$meta[$row['column_name']]['default'] = $row['column_default'];
						}
					}
					$this->columns[$table] = $meta;
					break;
					
				case 'sqlite' :
					$result = $this->connection->query("PRAGMA table_info(".$this->connection->quoteName($table).")");
					$result->setFetchMode(PDO::FETCH_ASSOC);
					foreach ($result as $row) {
						$meta[$row['name']] = array(
							'pk' => $row['pk'] == '1',
							'type' => $row['type'],
							'default' => null,
							'blob' => preg_match('/(TEXT|BLOB)/', $row['type']),
						);
					}
					$this->columns[$table] = $meta;
					break;
					
				default :
					$result = $this->connection->prepareExecute("select COLUMN_NAME, COLUMN_DEFAULT, DATA_TYPE, COLUMN_KEY "
						."from INFORMATION_SCHEMA.COLUMNS where TABLE_SCHEMA = DATABASE() and TABLE_NAME = :table_name", 
						array(':table_name' => $table)
					);
					$result->setFetchMode(PDO::FETCH_ASSOC);
					foreach ($result as $row) {
						$meta[$row['COLUMN_NAME']] = array(
							'pk' => $row['COLUMN_KEY'] == 'PRI',
							'type' => $row['DATA_TYPE'],
							'default' => in_array($row['COLUMN_DEFAULT'], array('NULL', 'CURRENT_TIMESTAMP')) 
								? null 
								: $row['COLUMN_DEFAULT'],
							'blob' => preg_match('/(TEXT|BLOB)/', $row['DATA_TYPE']),
						);
					}
					$this->columns[$table] = $meta;
					break;
			}
		}
		
		return $this->columns[$table];
	}

	/**
	 * Returns a list of foreign keys for a table.
	 */
	public function getForeignKeys($table) {
		
		$meta = array();
		
		switch ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME)) {
				
			case 'mysql' :
				foreach ($this->loadKeys() as $info) {
					if ($info['table_name'] === $table) {
						$meta[] = array(
							'table' => $info['table_name'],
							'column' => $info['column_name'],
							'referenced_table' => $info['referenced_table_name'],
							'referenced_column' => $info['referenced_column_name'],
						);
					}
				}
				return $meta;
			
			case 'pgsql' :
				list($schema, $table) = stristr($table, '.') ? explode(".", $table) : array('public', $table);
				$result = $this->connection->query("SELECT kcu.column_name AS column_name, ccu.table_name "
					."AS referenced_table_name, ccu.column_name AS referenced_column_name "
					."FROM information_schema.table_constraints AS tc "
					."JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name "
					."JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name "
					."WHERE constraint_type = 'FOREIGN KEY' AND tc.table_name='".$table."' AND tc.table_schema = '".$schema."'"
				);
				$result->setFetchMode(PDO::FETCH_ASSOC);
				foreach ($result as $row) {
					$meta[] = array(
						'table' => $table,
						'column' => $row['column_name'],
						'referenced_table' => $row['referenced_table_name'],
						'referenced_column' => $row['referenced_column_name'],
					);
				}
				return $meta;
			
			case 'sqlite' :
				$sql = "PRAGMA foreign_key_list(".$this->connection->quoteName($table).")";
				$result = $this->connection->query($sql);
				$result->setFetchMode(PDO::FETCH_ASSOC);
				foreach ($result as $row) {
					$meta[] = array(
						'table' => $table,
						'column' => $row['from'],
						'referenced_table' => $row['table'],
						'referenced_column' => $row['to'],
					);
				}
				return $meta;
			
			default :
				throw new \RuntimeException("Meta not supported");
		}
	}

	/**
	 * Returns a list of foreign keys that refer a table.
	 */
	public function getReferencingKeys($table) {
		
		$meta = array();
		
		switch ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME)) {
		
			case 'mysql' :
				foreach ($this->loadKeys() as $info) {
					if ($info['referenced_table_name'] === $table) {
						$meta[] = array(
							'table' => $info['table_name'],
							'column' => $info['column_name'],
							'referenced_table' => $info['referenced_table_name'],
							'referenced_column' => $info['referenced_column_name'],
						);
					}
				}
				return $meta;
			
			case 'pgsql' :
			case 'sqlite' :
				foreach ($this->getTables() as $tbl) {
					if ($tbl != $table) {
						foreach ($this->getForeignKeys($tbl) as $info) {
							if ($info['referenced_table'] == $table) {
								$meta[] = $info;
							}
						}
					}
				}
				return $meta;
			
			default :
				throw new \RuntimeException("Meta not supported.");
		}
	}

	public function belongsTo($tablename) {
			
		if (! isset($this->belongs_to[$tablename])) {
			
			$this->belongs_to[$tablename] = array();
			
			foreach ($this->getForeignKeys($tablename) as $info) {
				$name = preg_replace('/_id$/', '', $info['column']);
				$this->belongs_to[$tablename][$name] = $info;
			}
		}
		
		return $this->belongs_to[$tablename];
	}

	public function hasMany($tablename) {
		
		if (! isset($this->has_many[$tablename])) {
		
			$this->has_many[$tablename] = array();
		
			foreach ($this->getReferencingKeys($tablename) as $info) {
				$name = $info['table'];
				$this->has_many[$tablename][$name] = $info;
			}
		}
		
		return $this->has_many[$tablename];
	}

	/**
	 * @internal
	 */
	protected function loadKeys() {
		
		if (! isset($this->keys)) {
			
			$this->keys = array();
			
			$sql = "SELECT TABLE_NAME AS `table_name`, COLUMN_NAME AS `column_name`, "
				."REFERENCED_COLUMN_NAME AS `referenced_column_name`, REFERENCED_TABLE_NAME AS `referenced_table_name` "
				."FROM information_schema.KEY_COLUMN_USAGE "
				."WHERE TABLE_SCHEMA = DATABASE() "
				."AND REFERENCED_TABLE_SCHEMA = DATABASE()";
				
			$result = $this->connection->query($sql);
			$result->setFetchMode(PDO::FETCH_ASSOC);
			
			foreach ($result as $row) {
				$this->keys[] = $row;
			}
		}
		
		return $this->keys;
	}

}
