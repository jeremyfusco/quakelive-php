<?php

/**
 * @package Quakelive API
 * @author Adam Klvač <adam@klva.cz>
 */

namespace Quakelive\Profile;
use Quakelive, DOMDocument, DOMXPath, DateInterval;

/**
 * Represents Quakelive player's profile summary information
 * @property-read string $nickname
 * @property-read string $flag
 * @property-read string $flag->image
 * @property-read string $accountType
 * @property-read string $model
 * @property-read string $model->image
 * @property-read string $avatar
 * @property-read Quakelive\DateTime $registered
 * @property-read Quakelive\ArrayHash $timePlayed
 * @property-read Quakelive\DateTime $timePlayed->ranked
 * @property-read Quakelive\DateTime $timePlayed->unranked
 * @property-read Quakelive\DateTime|NULL $lastGame
 * @property-read int $wins
 * @property-read int $losses
 * @property-read int $quits
 * @property-read int $frags
 * @property-read int $deaths
 * @property-read int $hits
 * @property-read int $shots
 * @property-read double $accuracy
 * @property-read Quakelive\ArrayHash $favorites
 * @property-read string $favorites->arena
 * @property-read string $favorites->arena->image
 * @property-read string $favorites->gametype
 * @property-read string $favorites->gametype->image
 * @property-read string $favorites->weapon
 * @property-read string $favorites->weapon->image
 * @property-read string $bio
 * @property-read string|NULL $clan
 * @property-read int|NULL $clan->id
 */
class Summary extends Quakelive\Profile\Result {

	/** url mask to profile page */
	const QUAKELIVE_PROFILE_URL = 'http://www.quakelive.com/profile/summary/%s';

	/**
	 * Fetches data from server
	 * @param Quakelive\Profile $profile
	 * @return Quakelive\ArrayHash
	 */
	public static function fetch(Quakelive\Profile $profile) {

		// Fetch HTML document
		$url = @sprintf(self::QUAKELIVE_PROFILE_URL, $profile->getNickname());
		if(!$url)
			throw new Quakelive\ApiException('Invalid ' . __CLASS__ . '::QUAKELIVE_PROFILE_URL');
		$dom = new DOMDocument;
		@$dom->loadHTMLFile($url); // Suppress errors in HTML document
		if(!$dom->doctype)
			throw new Quakelive\RequestException('Unable to fetch data from server');

		// Create finder
		$finder = new DOMXPath($dom);

		// Result array
		$data = new Quakelive\ArrayHash;

		// Nickname
		$data->nickname = $finder->query("//div[@id = 'prf_player_name']")->item(0)->nodeValue;
		if($data->nickname === Quakelive::UNKNOWN_PLAYER)
			throw new Quakelive\RequestException('No such player');

		// Flag
		$data->flag = new Quakelive\ArrayHash;
		$flag = $finder->query("//img[@class = 'playerflag']")->item(0);
		$data->flag->__toString = $flag->attributes->getNamedItem('title')->nodeValue;
		$data->flag->image = $flag->attributes->getNamedItem('src')->nodeValue;

		// Account type
		$data->accountType = substr($finder->query("//div[@id = 'qlv_profileTopLeft']")->item(0)->attributes->getNamedItem('class')->nodeValue, 8);

		// Model
		$div = $finder->query("//div[@class = 'prf_imagery']/div[1]")->item(0);
		preg_match('/url\(([^)]+?)\)/', $div->attributes->getNamedItem('style')->nodeValue, $matches);
		$data->model = new Quakelive\ArrayHash;
		$data->model->__toString = $div->attributes->getNamedItem('title')->nodeValue;
		$data->model->image = $matches[1];
		$data->avatar = str_replace('/body_md/', '/icon_xl/', $matches[1]);

		// Member since
		$data->registered = Quakelive\DateTime::from('M. j, Y', trim($finder->query("//div[@class = 'prf_vitals']/p[1]/text()[following::br][2]")->item(0)->wholeText));

		// Time played & last game
		if($finder->query("//div[@class = 'prf_vitals']/p/b[2]")->item(0)->nodeValue === 'Time Played:') {

			$data->timePlayed = new Quakelive\ArrayHash;
			preg_match('/^Ranked Time: ((\d+?)\.){0,1}((\d+?):){0,1}(\d+?):(\d+) Unranked Time: ((\d+?)\.){0,1}((\d+?):){0,1}(\d+?):(\d+)$/', $finder->query("//div[@class = 'prf_vitals']/p/span[@class = 'text_tooltip'][1]")->item(0)->attributes->getNamedItem('title')->nodeValue, $matches);

			// Ranked time
			$data->timePlayed->ranked = new DateInterval(sprintf("P%dDT%dH%dM%sS", $matches[2], $matches[4], $matches[5], $matches[6]));
			$data->timePlayed->unranked = new DateInterval(sprintf("P%dDT%dH%dM%sS", $matches[8], $matches[10], $matches[11], $matches[12]));

			// Last game
			$data->lastGame = Quakelive\DateTime::from('m/d/Y g:i A', $finder->query("//div[@class = 'prf_vitals']/p/span[@class = 'text_tooltip'][2]")->item(0)->attributes->getNamedItem('title')->nodeValue);

		} else { // Player has never played online
			$interval = new DateInterval('PT0H0M0S');
			$data->timePlayed = new Quakelive\ArrayHash;
			$data->timePlayed->ranked = $interval;
			$data->timePlayed->unranked = $interval;
			$data->lastGame = null;
		}

		// Vital stats prepare
		$pathVitals = $finder->query("//div[@class = 'prf_vitals']/p/text()[following::br]");

		// Wins
		$data->wins = intval(trim(str_replace(',', '', $pathVitals->item(5)->wholeText)));

		// Losses/quits
		$data->losses = intval(trim(str_replace(',', '', substr($pathVitals->item(6)->wholeText, 0, strpos($pathVitals->item(6)->wholeText, '/')))));
		$data->quits = intval(trim(str_replace(',', '', substr($pathVitals->item(6)->wholeText, strpos($pathVitals->item(6)->wholeText, '/') + 1))));

		// Frags/deaths
		$data->frags = intval(trim(str_replace(',', '', substr($pathVitals->item(7)->wholeText, 0, strpos($pathVitals->item(7)->wholeText, '/')))));
		$data->deaths = intval(trim(str_replace(',', '', substr($pathVitals->item(7)->wholeText, strpos($pathVitals->item(7)->wholeText, '/') + 1))));

		// Hits/shots, accuracy
		$data->hits = intval(trim(str_replace(',', '', substr($pathVitals->item(8)->wholeText, 0, strpos($pathVitals->item(8)->wholeText, '/')))));
		$data->shots = intval(trim(str_replace(',', '', substr($pathVitals->item(8)->wholeText, strpos($pathVitals->item(8)->wholeText, '/') + 1))));
		$data->accuracy = $data->shots > 0 ? $data->hits / $data->shots * 100 : 0;

		// Favorites
		$data->favorites = new Quakelive\ArrayHash;

		// Favorite arena
		$data->favorites->arena = new Quakelive\ArrayHash;
		$data->favorites->arena->__toString = trim($finder->query("//p[@class = 'prf_faves']/text()[following::div[@class = 'cl']]")->item(1)->wholeText);
		$data->favorites->arena->image = $data->favorites->arena === Quakelive::NONE ? null : sprintf('http://cdn.quakelive.com/web/2012121800/images/levelshots/lg/%s_v2012121800.0.jpg', strtolower(preg_replace('/[^a-z0-9]/i', '', $data->favorites->arena)));

		// Game type
		$data->favorites->gametype = new Quakelive\ArrayHash;
		$data->favorites->gametype->__toString = trim($finder->query("//p[@class = 'fivepxv prf_faves'][1]/text()[following::div[@class = 'cl']][2]")->item(0)->wholeText);
		$data->favorites->gametype->image = $data->favorites->gametype == Quakelive::NONE ? null : str_replace(array('/xsm/', '.gif'), array('/sm/', '.png'), $finder->query("//p[@class = 'fivepxv prf_faves'][1]/img")->item(0)->attributes->getNamedItem('src')->nodeValue);

		// Weapon
		$data->favorites->weapon = new Quakelive\ArrayHash;
		$data->favorites->weapon->__toString = trim($finder->query("//p[@class = 'fivepxv prf_faves'][2]/text()[following::div[@class = 'cl']][2]")->item(0)->wholeText);
		$data->favorites->weapon->image = $data->favorites->weapon == Quakelive::NONE ? null : str_replace(array('/sm/', '.gif'), array('/md/', '.png'), $finder->query("//p[@class = 'fivepxv prf_faves'][2]/img")->item(0)->attributes->getNamedItem('src')->nodeValue);

		// Bio
		$data->bio = (string) @$finder->query("//div[@class = 'prf_bio']/p")->item(0)->nodeValue;

		// Clan
		$clan = $finder->query("//a[@class = 'clan']")->item(0);
		if($clan) {
			/**
			 * @todo $summary->clan will be Quakelive\Clan with lazy access.
			 */
			$data->clan = new Quakelive\ArrayHash;
			$data->clan->name = $clan->nodeValue;
			$data->clan->id = substr($clan->attributes->getNamedItem('href')->nodeValue, 16);
		} else $data->clan = null;

		return $data;

	}

}
