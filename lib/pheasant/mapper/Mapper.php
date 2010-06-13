<?php

namespace pheasant\mapper;

/**
 * A persistence interface for a domain object
 */
interface Mapper
{
	/**
	 * Saves a domain object, either creating it or updating it
	 * @return void
	 */
	public function save($object);

	/**
	 * Deletes a domain object
	 */
	public function delete($object);

	/**
	 * Finds all domain objects, returns an iterator for iterating over the result
	 * @return Iterator
	 */
	public function find($sql=null, $params=array());

	/**
	 * Hydrates an array into an object
	 * @return DomainObject
	 */
	public function hydrate($array);
}
