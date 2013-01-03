<?php

/**
 * @package Quakelive API
 * @author Adam Klvač <adam@klva.cz>
 */

namespace Quakelive;
use Quakelive;

/**
 * Represents Quakelive player's profile
 */
class Profile {

	/** valid nickname mask */
	const VALID_NICKNAME_MASK = '/^[a-z0-9_]{2,15}$/i';

	/** @var string $nicknames */
	private $nickname;

	/** @var Quakelive\Player\Summary */
	private $summary;

	/** @var Quakelive\Player\Statistics */
	private $statistics;

	/**
	 * @param string $nickname
	 */
	public function __construct($nickname) {
		if(!preg_match(self::VALID_NICKNAME_MASK, $nickname))
			throw new Quakelive\ApiException('Nickname must be an alphanumeric string in length from 2 to 15 characters');
		$this->nickname = $nickname;
	}

	/**
	 * Returns profile summary info
	 * @return Quakelive\Profile\Summary
	 */
	public function getSummary() {
		if(!$this->summary) $this->summary = new Quakelive\Profile\Summary($this);
		return $this->summary;
	}

	/**
	 * Returns statistics
	 * @return Quakelive\Profile\Statistics
	 */
	public function getStatistics() {
		if(!$this->statistics) $this->statistics = new Quakelive\Profile\Statistics($this);
		return $this->statistics;
	}

	/**
	 * Returns nickname
	 * @return string
	 */
	public function getNickname() {
		return $this->summary ? $this->summary->nickname : $this->nickname;
	}

}