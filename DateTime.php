<?php

/**
 * @package Quakelive API
 * @author Adam Klvač <adam@klva.cz>
 */

namespace Quakelive;
use Serializable;

/**
 * Serializable DateTime class
 * Convertable into string directly
 */
class DateTime extends \DateTime implements Serializable {

	/**
	 * @return string
	 */
	public function serialize() {
		return serialize(array(
			'timestamp' => $this->getTimestamp(),
			'timezone' => $this->getTimezone()
		));
	}

	/**
	 * @param string
	 * @return void
	 */
	public function unserialize($serialized) {
		$this->__construct();
		$data = unserialize($serialized);
		$this->setTimezone($data['timezone']);
		$this->setTimestamp($data['timestamp']);
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->format('Y-m-d H:i:s');
	}

	/**
	 * Create Quakelive\DateTime instance
	 * @param string $format
	 * @param string $time
	 * @return Quakelive\DateTime
	 */
	public static function from($format, $time) {
		$date = \DateTime::createFromFormat($format, $time);
		$new = new self();
		$new->setTimestamp($date->getTimestamp());
		return $new;
	}

}