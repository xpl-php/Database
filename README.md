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
	'my_db_pdo_driver', // must be valid PDO driver
);
```
