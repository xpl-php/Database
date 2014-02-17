<?php
/**
 * @package Wells.Database
 * @subpackage DB
 */

namespace Wells\Database;

class DB {
	
	/**
	 * Database name
	 * @var string
	 */
	protected $_name;
	
	/**
	 * Database user name
	 * @var string
	 */
	protected $_user;
	
	/**
	 * Database user password
	 * @var string
	 */
	protected $_password;
	
	/**
	 * Database host
	 * @var string
	 */
	protected $_host;
	
	/**
	 * Database charset
	 * @var string
	 */
	protected $_charset;
	
	/**
	 * Database collate
	 * @var string
	 */
	protected $_collate;
	
	/**
	 * Database table prefix
	 * @var string
	 */
	protected $_prefix;
	
	/**
	 * The database connection handle.
	 * @var resource
	 */
	protected $dbh;
	
	/**
	 * Format specifiers for DB columns. Columns not listed here default to %s. 
	 * Initialized during schema construct/table creation.
	 * Keys are column names, values are format types: 'ID' => '%d'
	 * @var array
	 */
	public $field_types		= array();

	/**
	 * Array of DB_Table objects
	 * @var array
	 */
	public $tables			= array();
	
	/**
	 * Array of 'basename' => 'table' strings
	 * @var array
	 */
	public $table_names		= array();
	
	/**
	 * Whether DB is ready to query
	 * @var bool
	 */
	public $ready 			= false;
	
	/**
	 * Whether to show errors.
	 * @var bool
	 */
	public $show_errors 	= true;
	
	/**
	 * Whether to suppress errors.
	 * @var bool
	 */
	public $suppress_errors = false;
	
	public $insert_id;
	
	public $result;
	
	public $num_queries;
	
	static protected $_instance;
	
	static public final function i(){
		if ( ! isset( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}
	
	public static function init( 
		$name = DATABASE_NAME, 
		$user = DATABASE_USER, 
		$pass = DATABASE_PASSWORD, 
		$host = DATABASE_HOST, 
		$charset = DATABASE_CHARSET, 
		$collate = DATABASE_COLLATE,
		$prefix = DATABASE_TABLE_PREFIX
	){
		$_this = self::i();
		
		if ( ! defined( 'SAVEQUERIES' ) )
			define( 'SAVEQUERIES', false );
		
		register_shutdown_function( array( $_this, 'shutdown' ) );
		
		$_this->_name = $name;
		$_this->_user = $user;
		$_this->_password = $pass;
		$_this->_host = $host;
		$_this->_charset = $charset;
		$_this->_collate = $collate;
		
		$_this->db_connect();
		
		$_this->set_prefix( $prefix );
	}
	
	/**
	* Registers a database table schema.
	*
	* Schemas allows us to create tables (both PHP object representations 
	* and actual (My)SQL tables), which can then be used, e.g. by models. 
	* The schema object resides within its table object after creation.
	*
	* @param	DB_Table_Schema	$schema		Table schema object
	* @return	void
	*/
	public function register_schema( Table\Schema $schema ){
		
		// add column formats that aren't strings
		foreach( $schema->columns as $col => $settings ){
			$format = $schema->get_column_format( $col );
			if ( '%s' !== $format )
				$this->field_types[ $col ] = $format;
		}
		
		// create table object with db access
		$this->tables[ $schema->table ] = new Table( $schema, $this );
		
		$this->table_names[ $schema->table_basename ] = $schema->table;
		
		return $this;
	}
	
	/**
	* Replaces a table basename with its prefix name.
	* Does not perform any validation.
	*
	* @param	string 	$table	Table name or basename
	* @return	string 			Table name
	*/
	public function filter_table_name( $table ){
		
		if ( isset( $this->table_names[ $table ] ) )
			return $this->get_prefix() . $table;
		
		return $table;	
	}
	
	public function get_table_names(){
		return $this->table_names;	
	}
	
	/**
	* Returns true if table is a valid table name.
	*
	* @param	string 		$table		Table name
	* @return	DB_Table 				True if valid, else false.
	*/
	public function is_valid_table( $table ){
		return in_array( $table, $this->table_names );
	}
	
	/**
	* Return if a DB_Table object is set.
	*
	* @param	string 	$table	Table name
	* @return	bool			Whether the DB_Table object exists.
	*/
	public function table_isset( $table ){
		return isset( $this->tables[ $table ] );	
	}
	
	/**
	* Returns true if a table exists in the db.
	*
	* Useful for checking if create/delete queries worked.
	* 
	* @param	string 	$table	Table name
	* @return	bool			True if table actually exists
	*/
	public function table_exists( $table ){
		
		$table = $this->filter_table_name( $table );
		
		foreach ( $this->get_col("SHOW TABLES", 0) as $tablename ) {
			if ( $tablename == $table ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	* Returns the DB table prefix string
	*/
	public function get_prefix(){
		return $this->_prefix;	
	}
	
	public function shutdown(){
		return true;	
	}
	
	/**
	* Return a registered DB_Table object.
	*
	* @param	string 	$table	Table name
	* @return	DB_Table		Table object
	*/
	public function table( $table ){
		
		$table = $this->filter_table_name( $table );
		
		if ( ! $this->is_valid_table( $table ) ){
			trigger_error( "Invalid table '$table'" );	
			return null;
		}
		 
		if ( ! $this->table_isset( $table ) ){
			trigger_error( "Valid but unregistered table '$table'." );	
			return null;
		}
		
		return $this->tables[ $table ];
	}
	
	/**
	* Return a registered DB_Table's DB_Table_Schema object.
	*
	* @param	string 		$table		Table name
	* @return	DB_Table_Schema			Table schema object
	*/
	public function schema( $table ){
		
		$table = $this->filter_table_name( $table );
		
		if ( ! $this->is_valid_table( $table ) || ! $this->table_isset( $table ) )
			return null;
		
		return $this->table( $table )->get_schema();
	}
	
	/**
	 * Prepares a SQL query for safe execution. Uses sprintf()-like syntax.
	 *
	 * The following directives can be used in the query format string:
	 *   %d (integer)
	 *   %f (float)
	 *   %s (string)
	 *   %% (literal percentage sign - no argument needed)
	 *
	 * All of %d, %f, and %s are to be left unquoted in the query string and they need an argument passed for them.
	 * Literals (%) as parts of the query must be properly written as %%.
	 *
	 * This function only supports a small subset of the sprintf syntax; it only supports %d (integer), %f (float), and %s (string).
	 * Does not support sign, padding, alignment, width or precision specifiers.
	 * Does not support argument numbering/swapping.
	 *
	 * May be called like {@link http://php.net/sprintf sprintf()} or like {@link http://php.net/vsprintf vsprintf()}.
	 *
	 * Both %d and %s should be left unquoted in the query string.
	 *
	 * <code>
	 * wpdb::prepare( "SELECT * FROM `table` WHERE `column` = %s AND `field` = %d", 'foo', 1337 )
	 * wpdb::prepare( "SELECT DATE_FORMAT(`field`, '%%c') FROM `table` WHERE `column` = %s", 'foo' );
	 * </code>
	 *
	 * @param string $query Query statement with sprintf()-like placeholders
	 * @param array|mixed $args The array of variables to substitute into the query's placeholders if being called like
	 * 	{@link http://php.net/vsprintf vsprintf()}, or the first variable to substitute into the query's placeholders if
	 * 	being called like {@link http://php.net/sprintf sprintf()}.
	 * @param mixed $args,... further variables to substitute into the query's placeholders if being called like
	 * 	{@link http://php.net/sprintf sprintf()}.
	 * @return null|false|string Sanitized query string, null if there is no query, false if there is an error and string
	 * 	if there was something to prepare
	 */
	public function prepare( $query, $args ) {
		if ( is_null( $query ) )
			return;

		$args = func_get_args();
		array_shift( $args );
		// If args were passed as an array (as in vsprintf), move them up
		if ( isset( $args[0] ) && is_array($args[0]) )
			$args = $args[0];
		$query = str_replace( "'%s'", '%s', $query ); // in case someone mistakenly already singlequoted it
		$query = str_replace( '"%s"', '%s', $query ); // doublequote unquoting
		$query = preg_replace( '|(?<!%)%f|' , '%F', $query ); // Force floats to be locale unaware
		$query = preg_replace( '|(?<!%)%s|', "'%s'", $query ); // quote the strings, avoiding escaped strings like %%s
		array_walk( $args, array( $this, 'escape_by_ref' ) );
		return @vsprintf( $query, $args );
	}

	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	public function query( $query ) {
		if ( ! $this->ready )
			return false;
		
		$return_val = 0;
		$this->flush();

		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";

		// Keep track of the last query for debug..
		$this->last_query = $query;

		if ( SAVEQUERIES )
			$this->timer_start();

		$this->result = @mysql_query( $query, $this->dbh );
		$this->num_queries++;

		if ( SAVEQUERIES )
			$this->queries[] = array( $query, $this->timer_stop(), $this->getCaller() );

		// If there is an error then take note of it..
		if ( $this->last_error = mysql_error( $this->dbh ) ) {
			// Clear insert_id on a subsequent failed insert.
			if ( $this->insert_id && preg_match( '/^\s*(insert|replace)\s/i', $query ) )
				$this->insert_id = 0;

			$this->printError();
			return false;
		}

		if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $query ) ) {
			$return_val = $this->result;
		} elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ) {
			$this->rows_affected = mysql_affected_rows( $this->dbh );
			// Take note of the insert_id
			if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				$this->insert_id = mysql_insert_id($this->dbh);
			}
			// Return number of rows affected
			$return_val = $this->rows_affected;
		} else {
			$num_rows = 0;
			while ( $row = @mysql_fetch_object( $this->result ) ) {
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			// Log number of rows the query returned
			// and return number of rows selected
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
		}

		return $return_val;
	}

	/**
	 * Insert a row into a table.
	 *
	 * <code>
	 * wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 * wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 * </code>
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function insert( $table, $data, $format = null ) {
		return $this->insertReplaceHelper( $table, $data, $format, 'INSERT' );
	}

	/**
	 * Replace a row into a table.
	 *
	 * @see Database::insert()
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows affected, or false on error.
	 */
	public function replace( $table, $data, $format = null ) {
		return $this->insertReplaceHelper( $table, $data, $format, 'REPLACE' );
	}

	/**
	 * Update a row in the table
	 *
	 * <code>
	 * wpdb::update( 'table', array( 'column' => 'foo', 'field' => 'bar' ), array( 'ID' => 1 ) )
	 * wpdb::update( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( 'ID' => 1 ), array( '%s', '%d' ), array( '%d' ) )
	 * </code>
	 *
	 * @param string $table table name
	 * @param array $data Data to update (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array $where A named array of WHERE clauses (in column => value pairs). Multiple clauses will be joined with ANDs. Both $where columns and $where values should be "raw".
	 * @param array|string $format Optional. An array of formats to be mapped to each of the values in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where. If string, that format will be used for all of the items in $where. A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $where will be treated as strings.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		if ( ! is_array( $data ) || ! is_array( $where ) )
			return false;

		$formats = $format = (array) $format;
		$bits = $wheres = array();
		foreach ( (array) array_keys( $data ) as $field ) {
			if ( !empty( $format ) )
				$form = ( $form = array_shift( $formats ) ) ? $form : $format[0];
			elseif ( isset($this->field_types[$field]) )
				$form = $this->field_types[$field];
			else
				$form = '%s';
			$bits[] = "`$field` = {$form}";
		}

		$where_formats = $where_format = (array) $where_format;
		foreach ( (array) array_keys( $where ) as $field ) {
			if ( !empty( $where_format ) )
				$form = ( $form = array_shift( $where_formats ) ) ? $form : $where_format[0];
			elseif ( isset( $this->field_types[$field] ) )
				$form = $this->field_types[$field];
			else
				$form = '%s';
			$wheres[] = "`$field` = {$form}";
		}

		$sql = "UPDATE `$table` SET " . implode( ', ', $bits ) . ' WHERE ' . implode( ' AND ', $wheres );
		return $this->query( $this->prepare( $sql, array_merge( array_values( $data ), array_values( $where ) ) ) );
	}

	/**
	 * Delete a row in the table
	 *
	 * <code>
	 * wpdb::delete( 'table', array( 'ID' => 1 ) )
	 * wpdb::delete( 'table', array( 'ID' => 1 ), array( '%d' ) )
	 * </code>
	 *
	 * @param string $table table name
	 * @param array $where A named array of WHERE clauses (in column => value pairs). Multiple clauses will be joined with ANDs. Both $where columns and $where values should be "raw".
	 * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where. If string, that format will be used for all of the items in $where. A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $where will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete( $table, $where, $where_format = null ) {
		if ( ! is_array( $where ) )
			return false;

		$bits = $wheres = array();

		$where_formats = $where_format = (array) $where_format;

		foreach ( array_keys( $where ) as $field ) {
			if ( !empty( $where_format ) ) {
				$form = ( $form = array_shift( $where_formats ) ) ? $form : $where_format[0];
			} elseif ( isset( $this->field_types[ $field ] ) ) {
				$form = $this->field_types[ $field ];
			} else {
				$form = '%s';
			}

			$wheres[] = "$field = $form";
		}

		$sql = "DELETE FROM $table WHERE " . implode( ' AND ', $wheres );
		return $this->query( $this->prepare( $sql, $where ) );
	}

	/**
	 * Retrieve one variable from the database.
	 *
	 * Executes a SQL query and returns the value from the SQL result.
	 * If the SQL result contains more than one column and/or more than one row, this function returns the value in the column and row specified.
	 * If $query is null, this function returns the value in the specified column and row from the previous SQL result.
	 *
	 * @param string|null $query Optional. SQL query. Defaults to null, use the result from the previous query.
	 * @param int $x Optional. Column of value to return. Indexed from 0.
	 * @param int $y Optional. Row of value to return. Indexed from 0.
	 * @return string|null Database query result (as string), or null on failure
	 */
	public function get_var( $query = null, $x = 0, $y = 0 ) {
		$this->func_call = "\$db->get_var(\"$query\", $x, $y)";
		if ( $query )
			$this->query( $query );

		// Extract var out of cached results based x,y vals
		if ( !empty( $this->last_result[$y] ) ) {
			$values = array_values( get_object_vars( $this->last_result[$y] ) );
		}

		// If there is a value return it else return null
		return ( isset( $values[$x] ) && $values[$x] !== '' ) ? $values[$x] : null;
	}

	/**
	 * Retrieve one row from the database.
	 *
	 * Executes a SQL query and returns the row from the SQL result.
	 *
	 * @param string|null $query SQL query.
	 * @param string $output Optional. one of ARRAY_A | ARRAY_N | OBJECT constants. Return an associative array (column => value, ...),
	 * 	a numerically indexed array (0 => value, ...) or an object ( ->column = value ), respectively.
	 * @param int $y Optional. Row to return. Indexed from 0.
	 * @return mixed Database query result in format specified by $output or null on failure
	 */
	public function get_row( $query = null, $output = OBJECT, $y = 0 ) {
		$this->func_call = "\$db->get_row(\"$query\",$output,$y)";
		if ( $query )
			$this->query( $query );
		else
			return null;

		if ( !isset( $this->last_result[$y] ) )
			return null;

		if ( $output == OBJECT ) {
			return $this->last_result[$y] ? $this->last_result[$y] : null;
		} elseif ( $output == ARRAY_A ) {
			return $this->last_result[$y] ? get_object_vars( $this->last_result[$y] ) : null;
		} elseif ( $output == ARRAY_N ) {
			return $this->last_result[$y] ? array_values( get_object_vars( $this->last_result[$y] ) ) : null;
		} else {
			$this->printError( " \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N" );
		}
	}

	/**
	 * Retrieve one column from the database.
	 *
	 * Executes a SQL query and returns the column from the SQL result.
	 * If the SQL result contains more than one column, this function returns the column specified.
	 * If $query is null, this function returns the specified column from the previous SQL result.
	 *
	 * @param string|null $query Optional. SQL query. Defaults to previous query.
	 * @param int $x Optional. Column to return. Indexed from 0.
	 * @return array Database query result. Array indexed from 0 by SQL result row number.
	 */
	public function get_col( $query = null , $x = 0 ) {
		if ( $query )
			$this->query( $query );

		$new_array = array();
		// Extract the column values
		for ( $i = 0, $j = count( $this->last_result ); $i < $j; $i++ ) {
			$new_array[$i] = $this->get_var( null, $x, $i );
		}
		return $new_array;
	}

	/**
	 * Retrieve an entire SQL result set from the database (i.e., many rows)
	 *
	 * Executes a SQL query and returns the entire SQL result.
	 *
	 * @param string $query SQL query.
	 * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants. With one of the first three, return an array of rows indexed from 0 by SQL result row number.
	 * 	Each row is an associative array (column => value, ...), a numerically indexed array (0 => value, ...), or an object. ( ->column = value ), respectively.
	 * 	With OBJECT_K, return an associative array of row objects keyed by the value of each row's first column's value. Duplicate keys are discarded.
	 * @return mixed Database query results
	 */
	public function get_results( $query = null, $output = OBJECT ) {
		$this->func_call = "\$db->get_results(\"$query\", $output)";

		if ( $query )
			$this->query( $query );
		else
			return null;

		$new_array = array();
		if ( $output == OBJECT ) {
			// Return an integer-keyed array of row objects
			return $this->last_result;
		} elseif ( $output == OBJECT_K ) {
			// Return an array of row objects with keys from column 1
			// (Duplicates are discarded)
			foreach ( $this->last_result as $row ) {
				$var_by_ref = get_object_vars( $row );
				$key = array_shift( $var_by_ref );
				if ( ! isset( $new_array[ $key ] ) )
					$new_array[ $key ] = $row;
			}
			return $new_array;
		} elseif ( $output == ARRAY_A || $output == ARRAY_N ) {
			// Return an integer-keyed array of...
			if ( $this->last_result ) {
				foreach( (array) $this->last_result as $row ) {
					if ( $output == ARRAY_N ) {
						// ...integer-keyed row arrays
						$new_array[] = array_values( get_object_vars( $row ) );
					} else {
						// ...column name-keyed row arrays
						$new_array[] = get_object_vars( $row );
					}
				}
			}
			return $new_array;
		}
		return null;
	}
	
	/**
	 * Retrieve column metadata from the last query.
	 *
	 * @param string $info_type Optional. Type one of name, table, def, max_length, not_null, primary_key, multiple_key, unique_key, numeric, blob, type, unsigned, zerofill
	 * @param int $col_offset Optional. 0: col name. 1: which table the col's in. 2: col's max length. 3: if the col is numeric. 4: col's type
	 * @return mixed Column Results
	 */
	public function get_col_info( $info_type = 'name', $col_offset = -1 ) {
		$this->loadColInfo();

		if ( $this->col_info ) {
			if ( $col_offset == -1 ) {
				$i = 0;
				$new_array = array();
				foreach( (array) $this->col_info as $col ) {
					$new_array[$i] = $col->{$info_type};
					$i++;
				}
				return $new_array;
			} else {
				return $this->col_info[$col_offset]->{$info_type};
			}
		}
	}	
	
	/**
	 * Real escape, using mysql_real_escape_string()
	 *
	 * @see mysql_real_escape_string()
	 * @param  string $string to escape
	 * @return string escaped
	 */
	public function real_esc( $string ) {
		
		if ( $this->dbh )
			return mysql_real_escape_string( $string, $this->dbh );

		$class = get_class( $this );
		
		trigger_error("$class must set a database connection for use with escaping.", E_USER_NOTICE );
		
		return addslashes( $string );
	}

	/**
	 * Escape data. Works on arrays.
	 *
	 * @param  string|array $data
	 * @return string|array escaped
	 */
	public function esc( $data ) {
		
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				if ( is_array($v) )
					$data[$k] = $this->esc( $v );
				else
					$data[$k] = $this->real_esc( $v );
			}
		} else {
			$data = $this->real_esc( $data );
		}

		return $data;
	}

	/**
	 * Escapes content by reference for insertion into the database, for security
	 *
	 * @param string $string to escape
	 * @return void
	 */
	public function escape_by_ref( &$string ) {
		if ( ! is_float( $string ) )
			$string = $this->real_esc( $string );
	}

	/**
	 * The database character collate.
	 *
	 * @return string The database character collate.
	 */
	public function sql_charset_collate() {
		$charset_collate = '';
		if ( ! empty( $this->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $this->charset";
		if ( ! empty( $this->collate ) )
			$charset_collate .= " COLLATE $this->collate";
		return $charset_collate;
	}
	
	/**
	 * Connect to and select database
	 */
	public function db_connect( $reconnect = false ) {
		
		if ( ! $reconnect && is_resource( $this->dbh ) )
			return false; // denied!
		
		$this->is_mysql = true;

		$new_link 		= defined( 'MYSQL_NEW_LINK' ) ? MYSQL_NEW_LINK : true;
		$client_flags	= defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0;

		if ( DEBUG ) {
			$error_reporting = false;
			if ( defined( 'E_DEPRECATED' ) ) {
				$error_reporting = error_reporting();
				error_reporting( $error_reporting ^ E_DEPRECATED );
			}
			
			$this->dbh = mysql_connect( $this->_host, $this->_user, $this->_password, $new_link, $client_flags );
			
			if ( false !== $error_reporting )
				error_reporting( $error_reporting );
			
		} else {
			$this->dbh = @mysql_connect( $this->_host, $this->_user, $this->_password, $new_link, $client_flags );
		}

		if ( ! $this->dbh ) {
			$this->bail( "Could not establish database connection.", 'db_connect_fail' );
			return;
		}

		$this->set_charset( $this->dbh );

		$this->ready = true;

		$this->db_select( $this->_name, $this->dbh );
	}
	
	/**
	 * Selects a database using the current database connection.
	 *
	 * The database name will be changed based on the current database
	 * connection. On failure, the execution will bail and display an DB error.
	 *
	 * @param string $db MySQL database name
	 * @param resource $dbh Optional link identifier.
	 * @return null Always null.
	 */
	public function db_select( $db, $dbh = null ) {
		
		if ( is_null($dbh) )
			$dbh = $this->dbh;

		if ( !@mysql_select_db( $db, $dbh ) ) {
			$this->ready = false;
			#wp_load_translations_early();
			$this->bail( sprintf( __( '<h1>Can&#8217;t select database</h1>
<p>We were able to connect to the database server (which means your username and password is okay) but not able to select the <code>%1$s</code> database.</p>
<ul>
<li>Are you sure it exists?</li>
<li>Does the user <code>%2$s</code> have permission to use the <code>%1$s</code> database?</li>
<li>On some systems the name of your database is prefixed with your username, so it would be like <code>username_%1$s</code>. Could that be the problem?</li>
</ul>' ), htmlspecialchars( $db, ENT_QUOTES ), htmlspecialchars( $this->_user, ENT_QUOTES ) ), 'db_select_fail' );
			return;
		}
	}
	
	/**
	 * Sets the connection's character set.
	 *
	 * @param resource $dbh     The resource given by mysql_connect
	 * @param string   $charset The character set (optional)
	 * @param string   $collate The collation (optional)
	 */
	public function set_charset( $dbh, $charset = null, $collate = null ) {
		
		if ( ! isset( $charset ) ) $charset = $this->_charset;
		if ( ! isset( $collate ) ) $collate = $this->_collate;
		
		if ( $this->db_supports( 'collation' ) && ! empty( $charset ) ) {
			
			if ( function_exists( 'mysql_set_charset' ) && $this->db_supports( 'set_charset' ) ) {
				mysql_set_charset( $charset, $dbh );
			} else {
				$query = $this->prepare( 'SET NAMES %s', $charset );
				if ( ! empty( $collate ) )
					$query .= $this->prepare( ' COLLATE %s', $collate );
				mysql_query( $query, $dbh );
			}
		}
	}

	/**
	 * Sets the table prefix.
	 *
	 * @param string $prefix Alphanumeric name for the new prefix.
	 * @param bool $set_table_names Optional. Whether the table names, should be updated or not.
	 * @return string|error Old prefix or Notice on error
	 */
	public function set_prefix( $new_prefix, $set_table_names = true ) {

		if ( preg_match( '|[^a-z0-9_]|i', $new_prefix ) ){
			trigger_error( 'Invalid database prefix' );
			return null;
		}
		
		if ( isset( $this->_prefix ) )
			$old_prefix = $this->_prefix;
		
		$this->_prefix = $new_prefix;

		if ( $set_table_names && ! empty( $this->table_names ) ) {
			
			foreach ( $this->tables_names as $basename => $prefixed_table )
				$this->table_names[ $basename ] = $new_prefix . $basename;
			
			$this->tablesInit();
		}
	}
	
	/**
	 * Determine if a database supports a particular feature.
	 *
	 * @param string $db_cap The feature to check for.
	 * @return bool
	 */
	public function db_supports( $db_cap ) {
		$version = $this->db_version();
		switch ( strtolower( $db_cap ) ) {
			case 'collation' :    // @since 2.5.0
			case 'group_concat' : // @since 2.7.0
			case 'subqueries' :   // @since 2.7.0
				return version_compare( $version, '4.1', '>=' );
			case 'set_charset' :
				return version_compare( $version, '5.0.7', '>=' );
		};
		return false;
	}

	/**
	 * The database version number.
	 *
	 * @return false|string false on failure, version number on success
	 */
	public function db_version() {
		return preg_replace( '/[^0-9.].*/', '', mysql_get_server_info( $this->dbh ) );
	}
	
	/**
	 * Returns number of queries executed.
	 */
	public function get_num_queries(){
		return isset( $this->num_queries ) ? $this->num_queries : 0;
	}
	
	/**
	 * Starts the timer, for debugging purposes.
	 *
	 * @return true
	 */
	public function timer_start() {
		$this->time_start = microtime( true );
		return true;
	}

	/**
	 * Stops the debugging timer.
	 *
	 * @return float Total time spent on the query, in seconds
	 */
	public function timer_stop() {
		return ( microtime( true ) - $this->time_start );
	}

	/**
	 * Enables showing of database errors.
	 *
	 * @param bool $show Whether to show or hide errors
	 * @return bool Old value for showing errors.
	 */
	public function show_errors( $show = true ) {
		$errors = $this->show_errors;
		$this->show_errors = $show;
		return $errors;
	}

	/**
	 * Whether to suppress database errors.
	 *
	 * @param bool $suppress Optional. New value. Defaults to true.
	 * @return bool Old value
	 */
	public function suppress_errors( $suppress = true ) {
		$errors = $this->suppress_errors;
		$this->suppress_errors = (bool) $suppress;
		return $errors;
	}

	/**
	 * Kill cached query results.
	 *
	 * @return void
	 */
	public function flush() {
		$this->last_result = array();
		$this->col_info    = null;
		$this->last_query  = null;
		$this->rows_affected = $this->num_rows = 0;
		$this->last_error  = '';

		if ( is_resource( $this->result ) )
			mysql_free_result( $this->result );
	}
	
	/**
	 * Retrieve the name of the function that called.
	 *
	 * Searches up the list of functions until it reaches
	 * the one that would most logically had called this method.
	 *
	 * @return string The name of the calling function
	 */
	protected function getCaller() {
		return debug_backtrace_summary( __CLASS__ );
	}
	
	/**
	 * Load the column metadata from the last query.
	 *
	 * @access protected
	 */
	protected function loadColInfo() {
		if ( $this->col_info )
			return;
		for ( $i = 0; $i < @mysql_num_fields( $this->result ); $i++ ) {
			$this->col_info[ $i ] = @mysql_fetch_field( $this->result, $i );
		}
	}

	/**
	* Reinitializes tables after a prefix change
	*/
	protected function tablesInit(){
		
		if ( empty( $this->tables ) )
			return;
		
		$table_objects = array_values( $this->tables );
		
		unset( $this->tables );
		
		foreach( $table_objects as $object ){
			$this->tables[ $this->get_prefix() . $object->name ] = $object;
		}
	}
	
	/**
	 * Print SQL/DB error.
	 *
	 * @global array $EZSQL_ERROR Stores error information of query and error string
	 *
	 * @param string $str The error to display
	 * @return bool False if the showing of errors is disabled.
	 */
	protected function printError( $str = '' ) {
		global $EZSQL_ERROR;

		if ( !$str )
			$str = mysql_error( $this->dbh );
		$EZSQL_ERROR[] = array( 'query' => $this->last_query, 'error_str' => $str );

		if ( $this->suppress_errors )
			return false;

		if ( $caller = $this->getCaller() )
			$error_str = sprintf( 'Database error %1$s for query %2$s made by %3$s', $str, $this->last_query, $caller );
		else
			$error_str = sprintf( 'Database error %1$s for query %2$s', $str, $this->last_query );

		error_log( $error_str );

		// Are we showing errors?
		if ( ! $this->show_errors )
			return false;

		// If there is an error then take note of it
		$str   = htmlspecialchars( $str, ENT_QUOTES );
		$query = htmlspecialchars( $this->last_query, ENT_QUOTES );

		print "<div id='error'>
			<p class='dberror'><strong>Database error:</strong> [$str]<br />
			<code>$query</code></p>
			</div>";
	}

	/**
	 * Helper function for insert and replace.
	 *
	 * Runs an insert or replace query based on $type argument.
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @param string $type Optional. What type of operation is this? INSERT or REPLACE. Defaults to INSERT.
	 * @return int|false The number of rows affected, or false on error.
	 */
	protected function insertReplaceHelper( $table, $data, $format = null, $type = 'INSERT' ) {
		if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ) ) )
			return false;
		$this->insert_id = 0;
		$formats = $format = (array) $format;
		$fields = array_keys( $data );
		$formatted_fields = array();
		foreach ( $fields as $field ) {
			if ( !empty( $format ) )
				$form = ( $form = array_shift( $formats ) ) ? $form : $format[0];
			elseif ( isset( $this->field_types[$field] ) )
				$form = $this->field_types[$field];
			else
				$form = '%s';
			$formatted_fields[] = $form;
		}
		$sql = "{$type} INTO `$table` (`" . implode( '`,`', $fields ) . "`) VALUES (" . implode( ",", $formatted_fields ) . ")";
		return $this->query( $this->prepare( $sql, $data ) );
	}

	/**
	 * Wraps errors in a nice header and footer and dies.
	 *
	 * Will not die if $show_errors is false.
	 *
	 * @param string $message The Error message
	 * @param string $error_code Optional. A Computer readable string to identify the error.
	 * @return false|void
	 */
	protected function bail( $message, $error_code = '500' ) {
		if ( ! $this->show_errors ) {
			$this->error = $message;
			return false;
		}
		die($message);
	}
	

}