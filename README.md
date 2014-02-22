Components-Database
===================

{PHP}

###Requirements
 * `PDO`
 * `FluentPDO` (included)
 * `Wells\Util`
 
##Basic Usage

First, initialize the database settings. The connection is lazy-loaded, which means you do not have to manually call `Wells\Database\Database::connect()`.
```php
Wells\Database\Database::init(
	'my_db_name', 
	'my_db_host',
	'my_db_user', 
	'my_db_password',
	'my_db_table_prefix',
	'pdo_driver', // must be valid PDO driver
);
```

Create and register your table schemas:
```php
register_schema( 
	new Wells\Database\Table\Schema(array(
		'table_basename' => 'options',
		'columns' => array(
			'option_name' => 'VARCHAR(120) NOT NULL',
			'option_value' => 'LONGTEXT NOT NULL',
			'cache_option' => 'TINYINT NOT NULL default 0'
		),
		'primary_key' => 'option_name',
		'unique_keys' => array(
			'option_name_cached' => array('option_name', 'cache_option')
		),
		'keys' => array(
			'cache_option' => 'cache_option'
		),
	))
);
```

Create and register your object controllers:
```php
register_model_controller( new MY_Options_Controller() );
```

Install table:
```php
db_create_table( 'options' );
```

Query away:
```php
$options = get_model_controller('options');
$options->insert( array(
	'option_name' => 'item1',
	'option_value' => 1
	'cache_option' => 1
) );
```
