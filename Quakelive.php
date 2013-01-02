<?php

/**
 * @package Quakelive API
 * @author Adam Klvač <adam@klva.cz>
 * @version 1.0.0
 */

require_once(__DIR__ . '/Exceptions.php');
require_once(__DIR__ . '/ArrayHash.php');
require_once(__DIR__ . '/DateTime.php');
require_once(__DIR__ . '/Profile/Profile.php');
require_once(__DIR__ . '/Profile/Summary.php');

/**
 * Quakelive API wrapper
 */
class Quakelive {

	/** library version */
	const VERSION = '1.0.0';

	/** standart account */
	const ACCOUNT_STANDART = 'premium_status_0';

	/** premium account */
	const ACCOUNT_PREMIUM = 'premium_status_1';

	/** pro account */
	const ACCOUNT_PRO = 'premium_status_2';

	/** duel */
	const GAMETYPE_DUEL = 'Duel';

	/** none statement */
	const NONE = 'None';

	/** unknown player */
	const UNKNOWN_PLAYER = 'Unknown Player';

	/**
	 * Returns quakelive player's profile
	 * @param string $nickname
	 * @return Quakelive\Profile
	 * @throws Quakelive\ApiException
	 */
	public static function getProfile($nickname) {
		return new Quakelive\Profile($nickname);
	}

}