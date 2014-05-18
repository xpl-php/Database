<?php
/**
 * @package Phpf\Database
 */

namespace Phpf\Database;

use PDO;

class Database
{

	/**
	 * Whether to debug queries.
	 * @var boolean
	 */
	public $debug;

	/**
	 * Number of queries run during request.
	 * @var int
	 */
	public $num_queries;
	
	/**
	 * Whether currently connected.
	 * @var boolean
	 */
	protected $connected;
	
	/**
	 * FluentPDO object.
	 * @var FluentPDO
	 */
	protected $fpdo;
	
	/**
	 * Table objects.
	 * @var array
	 */
	protected $tables = array();

	/**
	 * The primary database name.
	 * @var string
	 */
	protected static $primary_db;
	
	/**
	 * Configuration objects for each database.
	 * @var array
	 */
	protected static $config = array();

	/**
	 * Database instances.
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * Returns database instance.
	 * 
	 * @param string $db Database name, or null for primary (if set).
	 * @return \Phpf\Database\Database Database instance.
	 * @throws RuntimeException if no database given and no primary set.
	 * @throws InvalidArgumentException if database given has no config object.
	 */
	public static function instance($db = null) {
		
		if (! isset($db)) {
				
			if (! isset(static::$primary_db)) {
				throw new \RuntimeException("Can not get database instance - no database given and no primary set.");
			}
			
			$db = static::$primary_db;
		}
		
		if (! isset(static::$instances[$db])) {
				
			if (! isset(static::$config[$db])) {
				throw new \InvalidArgumentException("Unknown database given: '$db'.");
			}
			
			static::$instances[$db] = new static($db);
		}
		
		return static::$instances[$db];
	}

	/**
	 * Rests $num_queries and $connected
	 */
	public function __construct($database) {
		
		$this->database = $database;
		$this->num_queries = 0;
		$this->connected = false;

		// FluentPDO autoloader
		autoload_namespace('FluentPDO', __DIR__);
	}

	/**
	 * Connects database using using config settings of given DB.
	 */
	public static function connect($db = null) {
			
		$database = static::instance($db);
		
		if (! $database->isConnected()) {
			
			$conf = static::getConfig($db);
			
			$pdo = new PDO($conf->getDsn(), $conf->getUser(), $conf->getPassword());
			
			$database->fpdo = new \FluentPDO\FluentPDO($pdo);
			
			$database->connected = true;
		}
	}

	/**
	 * Destroys the current database connection.
	 */
	public static function disconnect($db = null) {
			
		$database = static::instance($db);
		
		if ($database->isConnected()) {
			unset($database->fpdo);
			$database->connected = false;
		}
	}
	
	/**
	 * Destroys and and re-establishes connection.
	 */
	public static function reconnect($db = null, array $info = null) {
		
		// disconnect
		static::disconnect($db);
		
		if (! empty($info)) {
			
			// get current config
			$config = static::getConfig($db);
			
			// Merge the new settings and set
			static::config($config->import($info));
		}
		
		// connect
		static::connect($db);
	}
	
	/**
	 * Adds a db config object.
	 * 
	 * @param Phpf\Database\Config $conf Configuration object.
	 * @return void
	 */
	public static function config(Config $conf) {
		
		static::$config[$conf->getDatabase()] = $conf;
		
		if ($conf->isPrimary()) {
			static::$primary_db = $conf->getDatabase();
		}
	}
	
	/**
	 * Gets the config object for a database.
	 * 
	 * @param string|null $database Database name, or null for primary.
	 * @return Phpf\Database\Config Object if set, otherwise null.
	 */
	public static function getConfig($database = null) {
		if (! isset($database)) {
			$database = static::$primary_db;
		}
		return isset(static::$config[$database]) ? static::$config[$database] : null;
	}
	
	/**
	 * Returns true if connected to the database.
	 */
	public function isConnected() {
		return $this->connected;
	}

	/**
	 * Sets the database table prefix.
	 *
	 * If already connected, disconnects and reinitializes using current
	 * connection settings, then reconnects.
	 */
	public function setTablePrefix($prefix) {

		if ($this->isConnected()) {
			static::reconnect($this->database, array('prefix' => $prefix));
		} else {
			$config = static::getConfig($this->database);
			$config->setTablePrefix($prefix);
			static::config($config);
		}

		return $this;
	}

	/**
	 * Returns the database's table prefix.
	 */
	public function getTablePrefix() {
		return static::getConfig($this->database)->getTablePrefix();
	}

	/**
	 * Whether to debug FluentPDO
	 */
	public function setDebug($value) {

		$this->debug = (bool)$value;

		if ($this->isConnected()) {
			$this->fpdo->debug = $this->debug;
		}

		return $this;
	}

	/**
	 * Tries to convert a table's basename to a full (prefixed) table name.
	 *
	 * Does not perform any validation.
	 */
	public function filterTableName($table) {

		if (isset($this->tables[$table]))
			return $table;

		return $this->getTablePrefix().$table;
	}

	/**
	 * Registers a database table schema.
	 *
	 * Schemas allows us to create tables (both PHP object representations
	 * and actual SQL tables), which can then be used, e.g. by models.
	 * The schema object resides within its table object after creation.
	 *
	 * @param	Table\Schema	$schema		Table schema object
	 * @return	$this
	 */
	public function registerSchema(Table\Schema $schema) {

		$schema->setTablePrefix($this->getTablePrefix());

		$this->tables[$schema->getName()] = new Table($schema, $this);

		return $this;
	}

	public function getTables() {
		return $this->tables;
	}

	/**
	 * Return a registered Table object.
	 *
	 * @param	string 	$table	Table name
	 * @return	Table			Table object
	 */
	public function table($table) {

		$table = $this->filterTableName($table);

		if (! isset($this->tables[$table])) {
			return null;
		}

		return $this->tables[$table];
	}

	/**
	 * Return a registered table's Schema object.
	 *
	 * @param	string 		$table		Table name
	 * @return	Schema		Table schema object
	 */
	public function schema($table) {

		$table = $this->table($table);

		if (empty($table))
			return null;

		return $table->schema();
	}

	/**
	 * Returns the PDO instance.
	 */
	public function pdo() {

		if (! $this->isConnected()) {
			static::connect($this->database);
		}

		return $this->fpdo->pdo;
	}

	/**
	 * Returns the FluentPDO instance.
	 */
	public function fluent() {

		if (! $this->isConnected()) {
			static::connect($this->database);
		}

		return $this->fpdo;
	}

	/**
	 * Performs a database query using PDO's query() method directly.
	 */
	public function query($sql) {
		
		$this->num_queries++;
		
		return $this->pdo()->query($sql);
	}

	/**
	 * Performs a select query using FluentPDO
	 */
	public function select($table, $where, $select = '*', $asObjects = true) {

		if (! $this->isConnected()) {
			static::connect($this->database);
		}

		if ('*' !== $select) {
			$result = $this->fpdo->from($table)->where($where)->asObject($asObjects)->fetch($select);
		} else {
			$result = $this->fpdo->from($table)->where($where)->asObject($asObjects)->fetchAll();
		}

		$this->num_queries++;

		return $result;
	}

	/**
	 * Performs an insert query using FluentPDO
	 */
	public function insert($table, array $data) {

		if (! $this->isConnected()) {
			static::connect($this->database);
		}

		$this->num_queries++;

		return $this->fpdo->insertInto($table)->values($data)->execute();
	}

	/**
	 * Performs an update query using FluentPDO
	 */
	public function update($table, array $data, $key, $keyValue = null) {

		if (! $this->isConnected()) {
			static::connect($this->database);
		}

		$this->num_queries++;

		return $this->fpdo->update($table)->set($data)->where($key, $keyValue)->execute();
	}

	/**
	 * Performs a delete query using FluentPDO
	 */
	public function delete($table, $key, $keyValue = null) {

		if (! $this->isConnected()) {
			static::connect($this->database);
		}

		$this->num_queries++;

		return $this->fpdo->deleteFrom($table)->where($key, $keyValue)->execute();
	}

	/**
	 * Returns indexed array of installed table names.
	 */
	public function getInstalledTables() {

		$array = $this->pdo()->query('show tables')->fetchAll();
		
		return array_kpull($array, 0);
	}

	/**
	 * Returns true if a table exists in the db.
	 *
	 * Useful for checking if create/delete queries worked.
	 *
	 * @param	string 	$table	Table name
	 * @return	bool			True if table actually exists
	 */
	public function isTableInstalled($table) {

		$table = $this->filterTableName($table);

		foreach ( $this->getInstalledTables() as $tablename ) {
			if ($tablename == $table) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Attempts to forward calls to FluentPDO
	 */
	function __call($func, $args) {

		if (is_callable(array($this->fpdo, $func))) {
			return call_user_func_array(array($this->fpdo, $func), $args);
		}
	}

}
