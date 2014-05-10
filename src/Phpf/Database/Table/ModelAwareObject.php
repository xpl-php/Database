<?php

namespace Phpf\Database\Table;

abstract class ModelAwareObject extends Object {
	
	protected $model_class;
	
	protected $model;
	
	protected $relationships;
	
	public function setModelClass($class) {
		$this->model_class = $class;
		return $this;
	}
	
	public function getModel() {
		
		if (! isset($this->model)) {
			$this->importModel();
		}
		
		return $this->model;
	}
	
	public function getPrimaryKey() {
		return $this->getModel()->getPrimaryKey();
	}
	
	public function getTableName() {
		return $this->getModel()->getTableName();
	}
	
	public function getSchema() {
		return $this->getModel()->schema();
	}
	
	public function hasRelations() {
		return $this->getSchema()->hasRelations();
	}
	
	public function getRelationships() {
		
		if (! isset($this->relationships)) {
			$this->importRelationships();
		}
		
		return $this->relationships;
	}
	
	public function getRelationship($foreign_table) {
		
		if (! isset($this->relationships)) {
			$this->importRelationships();
		}
		
		return isset($this->relationships[$foreign_table]) ? $this->relationships[$foreign_table] : null;
	}
	
	/**
	 * Returns serialized array of object vars.
	 * 
	 * This implementation adds the model class, if set.
	 * 
	 * @return string Serialized array of object data.
	 */
	public function serialize() {
		
		$array = $this->toArray();
		
		if (isset($this->model_class)) {
			$array['model_class'] = $this->model_class;
		}
		
		return serialize($array);
	}
	
	/**
	 * Unserializes and then imports vars.
	 * 
	 * This implementation handles the setting of the model class.
	 * 
	 * @param string $serialized Serialized string of object data.
	 * @return void
	 */
	public function unserialize($serialized) {
			
		$array = unserialize($serialized);
		
		if (isset($array['model_class'])) {
			$this->setModelClass($array['model_class']);
			unset($array['model_class']);
		}
		
		$this->import($array);
	}
	
	protected function importRelationships() {
		
		$this->relationships = array();
		
		$schema = $this->getSchema();
			
		if ($schema->hasRelations()) {
			
			$tablename = $schema->getBasename();
			
			foreach($schema->getRelations() as &$array) {
				
				foreach($array as $foreign_table => &$relation) {
					
					$this->relationships[$foreign_table] = new Relationship($relation, $tablename);
				}
			}
		}
		
		return $this;
	}
	
	protected function importModel() {
		
		if (! isset($this->model_class)) {
			throw new \RuntimeException("Cannot import model - must set model class name first.");
		}
		
		$class = $this->model_class;
		
		$this->model = $class::instance();
		
		return $this;
	}
	
}
