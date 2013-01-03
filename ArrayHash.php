<?php

/**
 * @package Quakelive API
 * @author Adam Klvač <adam@klva.cz>
 */

namespace Quakelive;
use Quakelive, ArrayAccess, Iterator, Serializable;

/**
 * Improved array with frozen state support
 */
class ArrayHash implements ArrayAccess, Iterator, Serializable {

	/** @var bool $frozen */
	private $frozen = false;

	/** @var array */
	private $data = array();

	/** @var array */
	private $freezable = array();

	/**
	 * Freezes the array
	 * @return void
	 */
	public function freeze() {
		$this->frozen = true;
		foreach($this->freezable as $key) {
			$this->data[$key]->freeze();
		}
	}

	/**
	 * @param string $property
	 * @return mixed
	 */
	public function &__get($property) {
		if(!array_key_exists($property, $this->data))
			throw new Quakelive\ApiException("Property '$property' does not exist");
		return $this->data[$property];
	}

	/**
	 * @param string $property
	 * @param mixed $value
	 * @return void
	 */
	public function __set($property, $value) {
		if($this->frozen)
			throw new Quakelive\ApiException("Property '$property' is read only");
		$this->data[$property] = $value;
		if($value instanceof Quakelive\ArrayHash)
			$this->freezable[] = $property;
	}

	/**
	 * @param string $property
	 * @return bool
	 */
	public function __isset($property) {
		return array_key_exists($property, $this->data);
	}

	/**
	 * @param string $property
	 * @return bool
	 */
	public function __unset($property) {
		if($this->frozen)
			throw new Quakelive\ApiException("Property '$property' is read only");
		unset($this->data[$property]);
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->__get('__toString');
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return $this->__isset($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->__set($offset, $value);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->__get($offset);
	}

	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		$this->__unset($offset);
	}

	/**
	 * @return void
	 */
	public function rewind() {
		reset($this->data);
	}

	/**
	 * @return void
	 */
	public function next() {
		next($this->data);
	}

	/**
	 * @return bool
	 */
	public function valid() {
		return $this->__isset($this->key());
	}

	/**
	 * @return scalar
	 */
	public function key() {
		return key($this->data);
	}

	/**
	 * @return mixed
	 */
	public function current() {
		return $this->data[$this->key()];
	}

	/**
	 * @return string
	 */
	public function serialize() {
		return serialize($this->data);
	}

	/**
	 * @return void
	 */
	public function unserialize($serialized) {
		$this->data = unserialize($serialized);
	}

}