<?php

namespace Phpf\Database\Table\Object;

abstract class ModelAware extends \Phpf\Database\Table\Object {
	
	protected $model_class;
	
	protected $model;
	
	public function model() {
		
		if (! isset($this->model_class)) {
			throw new \RuntimeException("Cannot get model - no class set.");
		}
		
		$class = $this->model_class;
		
		return $class::instance();
	}
	
	public function schema() {
		return $this->model()->schema();
	}
	
	public function setModelClass($class) {
		$this->model_class = $class;
		return $this;
	}
	
	public function getPrimaryKey() {
		return $this->model()->getPrimaryKey();
	}
	
	public function getTableName() {
		return $this->model()->getTableName();
	}
	
	public function getRelations($table = null) {
		return $this->schema()->getRelations($table);
	}
	
	public function getModel() { return $this->model(); }
	
	public function getSchema() { return $this->model()->schema(); }
	
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
	
}
