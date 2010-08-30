<?php

namespace Pheasant;

class Property
{
	public $name, $options, $type;

	public function __construct($name, $type)
	{
		$this->name = $name;
		$this->type = $type;
		$this->options = Options::fromString($type->params);
	}

	public function __toString()
	{
		return $this->name;
	}

	public function callGet($object, $key)
	{
		return $object->get($key);
	}

	public function callSet($object, $key, $value)
	{
		return $object->set($key, $value);
	}
}
