<?php
/**
 * @package Phpf.Database
 * @subpackage Database
 */

namespace Phpf\Database;

use PDO;
use Exception;

class Database {
	
	/**
	 * Whether to debug queries.
	 * @var boolean
	 */
	public $debug;
	
	/**
	 * Number of queries run
	 * @var int
	 */
	public $num_queries;
	
	/**
	 * Database Name
	 * @var string
	 */
	protected $name;
	
	/**
	 * Database Host
	 * @var string
	 */
	protected $host;
	
	/**
	 * Database User
	 * @var string
	 */
	protected $user;
	
	/**
	 * Database Password
	 * @var string
	 */
	protected $pass;
	
	/**
	 * Database Driver
	 * @var string
	 */
	protected $driver;
	
	/**
	 * Table prefix
	 * @var string
	 */
	protected $prefix;
	
	/**
	 * FluentPDO object
	 * @var FluentPDO
	 */
	protected $fpdo;
	
	/**
	 * Whether currently connected
	 * @var boolean
	 */
	protected static $connected;
	
	/**
	 * Singleton instance.
	 * @var Database
	 */
	protected static $_instance;
	
	/**
	 * Returns singleton.
	 */
	public static function instance(){
		if ( ! isset(self::$_instance) )
			self::$_instance = new self();
		return self::$_instance;
	}
	
	/**
	 * Rests $num_queries and $connected
	 */
	private function __construct(){
		$this->num_queries = 0;
		self::$connected = false;
	}
	
	/**
	 * Sets database connection settings as properties.
	 */
	public static function init($dbName, $dbHost, $dbUser, $dbPass, $tablePrefix = '', $dbDriver = 'mysql'){
		
		$_this = self::instance();
		
		$_this->name = $dbName;
		$_this->host = $dbHost;
		$_this->user = $dbUser;
		$_this->pass = $dbPass;
		$_this->prefix = $tablePrefix;
		
		// Skip driver check if reinitializing with same driver.
		if ( isset($_this->driver) && $dbDriver == $_this->driver )
			return;
		
		$drivers = PDO::getAvailableDrivers();
		
		if ( !in_array($dbDriver, $drivers) ){
			throw new Exception("Invalid database driver $dbDriver.");
		}
		
		$_this->setDriver($dbDriver);
	}
	
	/**
	 * Connects to the database using settings from init().
	 */
	public static function connect(){
		
		$_this = self::instance();
		
		$dsn = $_this->getDriver() . ':dbname='. $_this->name . ';host=' . $_this->host;
		
		$_this->fpdo = new \FluentPDO\FluentPDO( new PDO($dsn, $_this->user, $_this->pass) );
				
		self::$connected = true;
	}
	
	/**
	 * Destroys the current database connection.
	 */
	public static function disconnect(){
		$_this = self::instance();
		unset($_this->fpdo);
		self::$connected = false;
	}
	
	/**
	 * Returns true if connected to the database.
	 */
	public function isConnected(){
		$val = self::$connected;
		return $val;
	}
	
	/**
	 * Whether to debug FluentPDO
	 */
	public function setDebug( $value ){
			
		$this->debug = (bool) $value;
		
		if ( $this->isConnected() ){
			$this->fpdo->debug = $this->debug;
		}
		
		return $this;
	}
	
	/**
	 * Sets the database driver (string).
	 */
	public function setDriver( $driver ){
		$this->driver = strtolower($driver);
		return $this;
	}
	
	/**
	 * Returns database driver string.
	 */
	public function getDriver(){
		return $this->driver;
	}
	
	/**
	 * Sets the database table prefix.
	 * 
	 * If already connected, disconnects and reinitializes using current
	 * connection settings, then reconnects.
	 */
	public function setPrefix( $prefix ){
			
		if ( $this->isConnected() ){
			self::disconnect();
			self::init($this->name, $this->host, $this->user, $this->pass, $prefix, $this->driver);
			self::connect();
		} else {
			$this->prefix = $prefix;
		}
		
		return $this;
	}
	
	/**
	 * Returns the database's table prefix.
	 */
	public function getPrefix(){
		return $this->prefix;
	}
	
	/**
	 * Tries to convert a table's basename to a full (prefixed) table name.
	 * 
	 * Does not perform any validation.
	 */
	public function filterTableName( $table ){
		
		if ( isset( $this->tables[ $table ] ) )
			return $table;
		
		return $this->getPrefix() . $table;
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
	public function registerSchema( Table\Schema $schema ){
		
		$schema->table = $this->getPrefix() . $schema->table_basename;
		
		$this->tables[ $schema->table ] = new Table($schema, $this);
		
		return $this;
	}
	
	/**
	* Return a registered Table object.
	*
	* @param	string 	$table	Table name
	* @return	Table			Table object
	*/
	public function table( $table ){
		
		$table = $this->filterTableName( $table );
		
		if ( ! isset($this->tables[ $table ]) ){
			return null;
		}
		
		return $this->tables[ $table ];
	}
	
	/**
	* Return a registered table's Schema object.
	*
	* @param	string 		$table		Table name
	* @return	Schema		Table schema object
	*/
	public function schema( $table ){
		
		$table = $this->table($table);
		
		if ( empty($table) )
			return null;
		
		return $table->schema();
	}
	
	/**
	 * Returns the PDO instance.
	 */
	public function pdo(){
			
		if ( ! $this->isConnected() ){
			self::connect();
		}
		
		return $this->fpdo->pdo;
	}
	
	/**
	 * Returns the FluentPDO instance.
	 */
	public function fluent(){
			
		if ( ! $this->isConnected() ){
			self::connect();
		}
		
		return $this->fpdo;
	}
	
	/**
	 * Performs a database query using PDO's query() method directly.
	 */
	public function query( $sql ){
		return $this->pdo()->query($sql);
	}
	
	/**
	 * Performs a select query using FluentPDO
	 */
	public function select( $table, $where, $select = '*', $asObjects = true ){
		
		if ( ! $this->isConnected() ){
			self::connect();
		}
		
		if ( '*' !== $select ){
			$result = $this->fpdo->from($table)
				->where($where)
				->asObject($asObjects)
				->fetch($select);
		} else {
			$result = $this->fpdo->from($table)
				->where($where)
				->asObject($asObjects)
				->fetchAll();
		}
		
		$this->num_queries++;
		
		return $result;
	}
	
	/**
	 * Performs an insert query using FluentPDO
	 */
	public function insert( $table, array $data ){
		
		if ( ! $this->isConnected() ){
			self::connect();
		}
		
		$this->num_queries++;
		
		return $this->fpdo->insertInto($table)
			->values($data)
			->execute();
	}
	
	/**
	 * Performs an update query using FluentPDO
	 */
	public function update( $table, array $data, $key, $keyValue = null ){
		
		if ( ! $this->isConnected() ){
			self::connect();
		}
		
		$this->num_queries++;
		
		return $this->fpdo->update($table)
			->set($data)
			->where($key, $keyValue)
			->execute();
	}
	
	/**
	 * Performs a delete query using FluentPDO
	 */
	public function delete( $table, $key, $keyValue = null ){
		
		if ( ! $this->isConnected() ){
			self::connect();
		}
		
		$this->num_queries++;
		
		return $this->fpdo->deleteFrom($table)
			->where($key, $keyValue)
			->execute();
	}
	
	/**
	 * Returns indexed array of installed table names.
	 */
	public function getInstalledTables(){
		
		$array = $this->pdo()
			->query('show tables')
			->fetchAll();
		
		$tables = array();
		
		foreach( $array as $arr ){
			$tables[] = $arr[0];
		}
		
		$this->num_queries++;
		
		return $tables;
	}
	
	/**
	* Returns true if a table exists in the db.
	*
	* Useful for checking if create/delete queries worked.
	* 
	* @param	string 	$table	Table name
	* @return	bool			True if table actually exists
	*/
	public function isTableInstalled( $table ){
		
		$table = $this->filterTableName($table);
		
		foreach ( $this->getInstalledTables() as $tablename ) {
			if ( $tablename == $table ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Attempts to forward calls to FluentPDO
	 */
	function __call( $func, $args ){
		
		if ( is_callable(array($this->fpdo, $func)) ){
			return call_user_func_array(array($this->fpdo, $func), $args);
		}
	}
	
}
