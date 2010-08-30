<?php

namespace Pheasant;
use \Pheasant;

/**
 * An object which represents an entity in the problem domain.
 */
class DomainObject
{
	private $_data = array();
	private $_changed = array();
	private $_saved=false;

	/**
	 * The final constructer which initializes the object. Subclasses
	 * can implement {@link constructor()} instead
	 */
	final public function __construct()
	{
		$pheasant = Pheasant::instance();
		$pheasant->initialize($this);

		// call user-defined constructor
		call_user_func_array(array($this,'construct'),
			func_get_args());
	}

	/**
	 * Template function for configuring a domain object.
	 */
	protected static function initialize($builder, $pheasant)
	{
	}

	/**
	 * Template function for constructing a domain object instance, called on
	 * each object construction
	 */
	protected function construct()
	{
		foreach(func_get_args() as $arg)
			if(is_array($arg)) $this->load($arg);
	}

	/**
	 * Returns an Identity object for the domain object
	 * @return Identity
	 */
	public function identity()
	{
		return $this->schema()->identity($this);
	}

	/**
	 * Returns whether the object has been saved
	 * @return bool
	 */
	public function isSaved()
	{
		return $this->_saved;
	}

	/**
	 * Saves the domain object via the associated mapper
	 * @chainable
	 */
	public function save()
	{
		$mapper = Pheasant::instance()->mapperFor($this);
		$mapper->save($this);
		$this->_saved = true;
		$this->_changed = array();
		return $this;
	}

	/**
	 * Change the objects saved state
	 * @chainable
	 */
	public function markSaved($value=true)
	{
		$this->_saved = $value;
		return $this;
	}

	/**
	 * Returns a key=>val array of properties that have changed since the last save
	 * @return array
	 */
	public function changes()
	{
		$changes = array();
		foreach(array_unique($this->_changed) as $key)
			$changes[$key] = $this->get($key, false);

		return $changes;
	}

	/**
	 * Clears the changes array
	 * @chainable
	 */
	public function clearChanges()
	{
		$this->_changed = array();
		return $this;
	}

	/**
	 * Returns the object as an array
	 * @return array
	 */
	public function toArray()
	{
		return $this->_data;
	}

	/**
	 * Returns the Schema registered for this class. Can be called non-statically.
	 * @return Schema
	*/
	public static function schema()
	{
		return Pheasant::instance()->schema(isset($this)
			? $this : get_called_class());
	}

	// ----------------------------------------
	// static helpers

	/**
	 * Creates an instance from an array, bypassing the constructor
	 */
	public static function fromArray($array, $saved=false)
	{
		$className = get_called_class();

		// hack that uses object deserialization to bypass constructor
		$object = unserialize(sprintf('O:%d:"%s":0:{}',
			strlen($className),
			$className));

		$object->load($array);

		// saved implies cleared changes
		if($saved)
			$object->markSaved(true)->clearChanges();

		return $object;
	}

	/**
	 * Delegates find calls through to the finder
	 */
	public static function __callStatic($method, $params)
	{
		if(preg_match('/^find/',$method))
		{
			$class = get_called_class();
			$finder = Pheasant::instance()->finderFor($class);
			array_unshift($params, $class);
			return call_user_func_array(array($finder, $method), $params);
		}
		else
		{
			throw new \BadMethodCallException("No static method $method available");
		}
	}

	/**
	 * Creates and saves a array or arrays as domain objects
	 * @return array of saved domain objects
	 */
	public static function import($records)
	{
		$objects = array();
		$schema = Pheasant::instance()->schema(get_called_class());

		foreach($records as $record)
		{
			$object = $schema->hydrate($record, false);
			$object->save();
			$objects []= $object;
		}

		return $objects;
	}

	/**
	 * Return the class name of the domain object
	 */
	public static function className()
	{
		return get_called_class();
	}

	// ----------------------------------------
	// container extension

	/**
	 * Gets a property
	 */
	public function get($prop)
	{
		return isset($this->_data[$prop]) ? $this->_data[$prop] : null;
	}

	/**
	 * Sets a property
	 */
	public function set($prop, $value)
	{
		$this->_data[$prop] = $value;
		$this->_changed[] = $prop;
		return $this;
	}

	/**
	 * Whether the object has a property
	 */
	public function has($prop)
	{
		return isset($this->_data[$prop]);
	}

	/**
	 * Loads an array of values into the object, optionally marking the object saved
	 * @chainable
	 */
	public function load($array)
	{
		foreach($array as $key=>$value)
			$this->set($key, $value);

		return $this;
	}

	/**
	 * Compares the properties of one domain object to that of another
	 */
	public function equals($object)
	{
		return $this->toArray() == $object->toArray();
	}

	// ----------------------------------------
	// object interface

	public function __get($key)
	{
		return $this->schema()->{$key}->callGet($this, $key);
	}

	public function __set($key, $value)
	{
		return $this->schema()->{$key}->callSet($this, $key, $value);
	}

	public function __isset($key)
	{
		return isset($this->schema()->$key);
	}
}
