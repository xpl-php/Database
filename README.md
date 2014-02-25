Components-Database
===================

Database abstraction layer for PHP 5.3+ using PDO.

###Requirements
 * `PDO`
 * `FluentPDO` (included)
 * `Phpf\Util`
 * `Phpf\Service`
 
##Basic Usage

First, initialize the database settings; the connection itself lazy-loaded.
```php
use Phpf\Database\Database;
Database::init(
	'my_db_name', 
	'my_db_host',
	'my_db_user', 
	'my_db_password',
	'my_db_table_prefix',
	'pdo_driver', // must be a valid PDO driver
);
```

Create and register your table schemas:
```php
Database::i()->registerSchema(
	new Phpf\Database\Table\Schema(array(
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
	)
);
```

If you're using `Phpf\Service`, you can install the table like so:
```php
db_create_table( 'options' );
```

Now create a controller (see `Model\Controller`) and query away:
```php
$cntrl = new MY_Options_Controller();
$cntrl->insert( array(
	'option_name' => 'item1',
	'option_value' => 1
	'cache_option' => 1
) );

$cache_opt1 = $cntrl->select( array('option_name' => 'item1'), 'cache_option');
```
