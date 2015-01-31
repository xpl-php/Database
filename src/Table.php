<?php

namespace xpl\Database;

use xpl\Data\AdapterInterface;

class Table {
	
	/**
	 * @var string
	 */
	protected $table_name;
	
	/**
	 * @var \xpl\Data\AdapterInterface
	 */
	protected $adapter;
	
	public function __construct($table_name, AdapterInterface $adapter) {
		$this->table_name = $table_name;
		$this->adapter = $adapter;
	}
	
	public function getAdapter() {
		return $this->adapter;
	}
	
	public function fetch(array $conditions = array(), $bool_operator = 'AND') {
		
		$this->getAdapter()->select($this->table_name, $conditions, $bool_operator);

		if (! $row = $this->getAdapter()->fetch()) {
			return null;
		}

		return $row;
	}
	
	public function fetchAll(array $conditions = array(), $bool_operator = 'AND') {
			
		$this->getAdapter()->select($this->table_name, $conditions, $bool_operator);
		
		return $this->getAdapter()->fetchAll();
	}
	
	public function fetchAllLike(array $conditions = array(), $bool_operator = 'AND') {
		
		$this->getAdapter()->selectLike($this->table_name, $conditions, $bool_operator);
		
		return $this->getAdapter()->fetchAll();
	}
	
	public function insert(array $data) {
		return $this->getAdapter()->insert($this->table_name, $data);
	}
	
	public function update(array $data, array $conditions = array()) {
		return $this->getAdapter()->update($this->table_name, $data, $conditions);
	}
	
	public function delete(array $conditions = array()) {
		return $this->getAdapter()->delete($this->table_name, $conditions);
	}
	
}

