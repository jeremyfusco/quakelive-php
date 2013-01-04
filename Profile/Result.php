<?php

/**
 * @package Quakelive API
 * @author Adam Klvač <adam@klva.cz>
 */

namespace Quakelive\Profile;
use Quakelive, ArrayAccess, Iterator, Serializable;

/**
 * Anchestor of Quakelive\Profile subclasses
 */
abstract class Result implements ArrayAccess, Iterator, Serializable, IResult {

	/** @var Quakelive\ArrayHash */
	protected $data;

	/**
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		return $this->data->{$property};
	}

	/**
	 * @param string $property
	 * @param mixed $value
	 * @return mixed
	 */
	public function __set($property, $value) {
		throw new Quakelive\ApiException('Object is read only');
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return $this->data->offsetExists($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->data->offsetSet($offset, $value);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->data->offsetGet($offset);
	}

	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		$this->data->offsetUnset($offset);
	}

	/**
	 * @return void
	 */
	public function rewind() {
		$this->data->rewind();
	}

	/**
	 * @return void
	 */
	public function next() {
		$this->data->next();
	}

	/**
	 * @return bool
	 */
	public function valid() {
		return $this->data->valid();
	}

	/**
	 * @return scalar
	 */
	public function key() {
		return $this->data->key();
	}

	/**
	 * @return mixed
	 */
	public function current() {
		return $this->data->current();
	}

	/**
	 * @return string
	 */
	public function serialize() {
		return $this->data->serialize();
	}

	/**
	 * @return void
	 */
	public function unserialize($serialized) {
		$this->data->unserialize($serialized);
	}

}

/**
 * Interface for Quakelive result objects
 */
interface IResult {

	/**
	 * Fetches data from server
	 * @param Quakelive\Profile $profile
	 * @return Quakelive\ArrayHash
	 */
	public static function fetch(Quakelive\Profile $profile);

}