<?php

namespace Phpf\Database\Table;

use InvalidArgumentException;

class Column {
	
	const INT = 1;
	const TINYINT = 2;
	const BIGINT = 4;
	const FLOAT = 8;
	const DECIMAL = 16;
	const TIME = 32;
	const TEXT = 64;
	const LONGTEXT = 128;
	const VARCHAR = 256;
	const ENUM = 512;
	const INDEX = 1024;
	/** &Alias */ const KEY = 1024;
	const UNIQUE_KEY = 2048;
	const PRIMARY_KEY = 4096;
	const NOT_NULL = 8192;
	const AUTO_INCREMENT = 16384;
	
	protected $name;
	protected $type;
	protected $typeName;
	protected $indexType;
	protected $indexTypeName;
	protected $length;
	protected $minLength;
	protected $maxLength;
	protected $default = null;
	protected $editable = true;
	protected $nullAllowed = true;
	protected $enumValues;
	
	protected static $typeNames = array(
		self::INT => 'int',
		self::TINYINT => 'tinyint',
		self::BIGINT => 'bigint',
		self::FLOAT => 'float',
		self::DECIMAL => 'decimal',
		self::TEXT => 'text',
		self::LONGTEXT => 'longtext',
		self::VARCHAR => 'varchar',
		self::ENUM => 'enum',
	);
	
	protected static $indexNames = array(
		self::INDEX => 'index',
		self::PRIMARY_KEY => 'primary',
		self::UNIQUE_KEY => 'unique',
	);
	
	public function __construct($name, $type, $length = null, $options = 0, $default = null) {
			
		$this->name = $name;
		
		if (! is_int($type)) {
			if (! $type = array_search($type, static::$typeNames, true)) {
				throw new InvalidArgumentException("Unknown column type given - '$type'.");
			}
		}
		
		$this->type = $type;
		$this->typeName = static::$typeNames[$type];
		
		if (isset($length)) {
			$this->setLength($length);
		}
		
		if (isset($default)) {
			$this->default = $default;
		}
		
		if (0 !== $options) {
			$this->setOptions($options);
		}
	}
	
	public function setOptions($opts) {
		
		if ($opts & static::AUTO_INCREMENT) {
			$this->nullAllowed = true;
			$this->editable = false;
		} else if ($opts & static::NOT_NULL) {
			$this->nullAllowed = false;
		}
		
		if ($opts & static::KEY) {
			$this->indexType = static::KEY;
			$this->indexTypeName = static::$indexNames[static::KEY];
		} else if ($opts & static::PRIMARY_KEY) {
			$this->indexType = static::PRIMARY_KEY;
			$this->indexTypeName = static::$indexNames[static::PRIMARY_KEY];
		} else if ($opts & static::UNIQUE_KEY) {
			$this->indexType = static::UNIQUE_KEY;
			$this->indexTypeName = static::$indexNames[static::UNIQUE_KEY];
		}
		
		return $this;
	}
	
	public function setLength($length) {
			
		if (false !== strpos($length, ',') && $this->isVariableLength()) {
			$this->length = $length;
			list($min, $max) = explode(',', $length);
			$this->minLength = (int)$min;
			$this->maxLength = (int)$max;
		} else {
			$this->length = (int)$length;
		}
		
		return $this;
	}
	
	public function setIndexType($type = self::INDEX) {
		
		if (! is_int($type)) {
			
			if (! $type = array_search($type, static::$indexNames, true)) {
				throw new InvalidArgumentException("Unknown index type '$type'.");
			}
		}
		
		$this->indexType = $type;
		$this->indexTypeName = static::$indexNames[$type];
		
		return $this;
	}
	
	public function setEnumValues(array $values) {
		$this->enumValues = $values;
		return $this;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getTypeName() {
		return $this->typeName;
	}
	
	public function getIndexType() {
		return $this->indexType;
	}
	
	public function getIndexTypeName() {
		return $this->indexTypeName;
	}
	
	public function getDefault() {
		
		if (! isset($this->default)) {
			return false;
		}
		
		if ('null' === $this->default) {
			return null;
		}
		
		return $this->default;
	}
	
	public function getMaxLength() {
		return isset($this->maxLength) ? $this->maxLength : $this->length;
	}
	
	public function getMinLength() {
		return isset($this->minLength) ? $this->minLength : $this->isNullAllowed() ? 0 : 1;
	}
	
	public function getLength() {
		return $this->length;
	}
	
	public function getEnumValues() {
		return $this->enumValues;
	}
	
	public function isType($type) {
		return $this->type === $type;
	}
	
	public function isVariableLength() {
		return (static::DECIMAL === $this->type || static::FLOAT === $this->type);
	}
	
	public function isTypeNumeric() {
		return $this->type < 33;
	}
	
	public function isIndex() {
		return isset($this->indexType);
	}
	
	public function isPrimaryKey() {
		return isset($this->indexType) && static::PRIMARY_KEY === $this->indexType;
	}
	
	public function isUniqueKey() {
		return isset($this->indexType) && static::UNIQUE_KEY === $this->indexType;
	}
	
	public function isNullAllowed() {
		return $this->nullAllowed;
	}
	
	public function isRequired() {
		return ! $this->isNullAllowed();
	}
	
	public function validate($value) {
		
		if (is_null($value) && ! $this->isNullAllowed()) {
			throw new InvalidArgumentException("Value cannot be null.");
		}
		
		if ($this->isType(static::ENUM)) {
			
			if (empty($this->enumValues)) {
				throw new \RuntimeException("Cannot validate enumerated value - no valid values set.");
			}
			
			return in_array($value, $this->enumValues, true);
		}
		
		if ($this->isTypeNumeric() && ! is_numeric($value)) {
			throw new InvalidArgumentException("Value must be numeric.");
		}
		
		$length = $this->getLength();
		
		if (0 !== $length && strlen($value) > $length) {
			throw new InvalidArgumentException("Value exceeds maximum allowable length.");
		}
		
		return true;
	}
	
}
