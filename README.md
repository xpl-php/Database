Components-Database
===================

Database abstraction layer for PHP 5.3+ using PDO.

###Requirements
 * `PDO`
 * `FluentPDO` (included)
 * `Phpf\Util`
 
##Basic Usage

First, initialize the database settings; the connection itself is lazy-loaded.
```php
use Phpf\Database\Database;

Database::init(
	'db_name', 
	'db_host',
	'db_user', 
	'db_password',
	'db_table_prefix',
	'pdo_driver', // must be a valid PDO driver
);
```

#### Schemas

Table schemas represent a single database table. They allow models to validate data, such as columns and indexes.

Create and register your table schemas. Note that the Database is a singleton, accessible via `instance()`.
```php
Database::instance()->registerSchema(
	new \Phpf\Database\Table\Schema(array(
		'table_basename' => 'user',
		'columns' => array(
			'id' => 'INT(10) NOT NULL AUTO_INCREMENT',
			'name' => 'VARCHAR(255) NOT NULL',
			'email' => 'VARCHAR(255) NOT NULL',
			'password' => 'VARCHAR(255) NOT NULL'
		),
		'primary_key' => 'id',
		'unique_keys' => array(
			'name' => 'name'
		),
		'keys' => array(
			'email' => 'email'
		),
	)
);
```
Install the tables using your favorite method, or use `\Phpf\Database\Sql\Writer`.

#### Models

Now we need to create a model (see `Orm\Model`). Models operate on a single table through its `Database\Table` object (created when a schema is registered). Models wrap around their Table objects to call corresponding Database methods.

Models must define the method `getTableBasename()`, which must return the table's base name (i.e. without a prefix). In this example, the user model would return `user`.

```php
$cntrl = new UserModel();
$cntrl->getTableBasename(); // returns 'user'
$rows_affected = $cntrl->insert( array(
	'name' => 'This Guy',
	'email' => 'thisguy@gmail.com',
	'password' => 'gobbldygook'
) );

if ( 0 !== $rows_affected ){
        $new_user_id = $cntrl->select(array('name' => 'This Guy'), 'id');
}
```

## Fluent

The Database object wraps FluentPDO, an excellent fluent interface for PDO. To use it, call `Database::instance()->fluent()`.

```php
$db = Database::instance();
$fpdo = $db->fluent();

$fpdo->from('user')
	->where(array('email', 'someguy@gmail.com'))
	// etc...
```
