<?php

namespace Phpf\Database\Table\Object;

class CollectionGroup extends Collection {
	
	protected $collections;
	
	public function addCollection($id, Collection $collection) {
		$this->set('collection_'.$id, $collection);
		return $this;
	}
	
	public function getCollection($id) {
		return $this->exists('collection_'.$id) ? $this->get('collection_'.$id) : null;
	}
	
	public function getCollections() {
		
		if (! isset($this->collections)) {
				
			$this->collections = array();
			
			foreach($this->data as $key => $value) {
				if (0 === strpos($key, 'collection_')) {
					$this->collections[substr($key, 11)] = $value;
				}
			}
		}
		
		return $this->collections;
	}
	
	public function collectionExists($id) {
		return $this->exists('collection_'.$id);
	}
	
}
