<?php

/**
 * @package Quakelive API
 * @author Adam Klvač <adam@klva.cz>
 */

namespace Quakelive\Profile;
use Quakelive, DOMDocument, DOMXPath;

/**
 * Represents Quakelive player's profile statistics
 */
class Statistics extends Quakelive\Profile\Result {

	/** url mask to profile page */
	const QUAKELIVE_STATISTICS_URL = 'http://www.quakelive.com/profile/statistics/%s';

	/**
	 * Fetches data from server
	 * @param Quakelive\Profile $profile
	 * @return Quakelive\ArrayHash
	 */
	public static function fetch(Quakelive\Profile $profile) {

		// Fetch HTML document
		$url = @sprintf(self::QUAKELIVE_STATISTICS_URL, $profile->getNickname());
		if(!$url)
			throw new Quakelive\ApiException('Invalid ' . __CLASS__ . '::QUAKELIVE_STATISTICS_URL');
		$dom = new DOMDocument;
		@$dom->loadHTMLFile($url); // Suppress errors in HTML document
		if(!$dom->doctype)
			throw new Quakelive\RequestException('Unable to fetch data from server');

		// Create finder
		$finder = new DOMXPath($dom);

		// Check wheter player exists
		if($finder->query("//div[@id = 'prf_player_name']")->item(0)->nodeValue === Quakelive::UNKNOWN_PLAYER)
			throw new Quakelive\RequestException('No such player');


		// Result array
		$data = new Quakelive\ArrayHash;
		$data->weapons = new Quakelive\ArrayHash;

		// Weapon names
		$weapons = $finder->query("//div[@class = 'col_weapon']");
		$offsets = array();
		foreach($weapons as $row) {
			if(trim($row->nodeValue) === '') continue;
			$weapon = new Quakelive\ArrayHash;
			$weapon->name = $row->nodeValue;
			$offsets[] = $offset = str_replace(array('-', ' '), '', strtolower($row->nodeValue));
			$data->weapons->{$offset} = $weapon;
		}

		// Frags
		$weapons = $finder->query("//div[@class = 'col_frags']");
		$wid = 0;
		foreach($weapons as $row) {
			$value = str_replace(',', '', $row->nodeValue);
			if(!is_numeric($value)) continue;
			$data->weapons->{$offsets[$wid]}->frags = (int) $value;
			$wid++;
		}

		// Hits/shots, accuracy
		$weapons = $finder->query("//div[@class = 'col_accuracy']");
		$wid = 1;
		foreach($weapons as $row) {
			if($row->nodeValue === 'Accuracy' || $row->nodeValue === 'N/A') continue;
			preg_match('/^Hits: (.+?) Shots: (.+?)$/', $row->attributes->getNamedItem('title')->nodeValue, $matches);
			$hits = (int) str_replace(',', '', $matches[1]);
			$shots = (int) str_replace(',', '', $matches[2]);
			$data->weapons->{$offsets[$wid]}->hits = $hits;
			$data->weapons->{$offsets[$wid]}->shots = $shots;
			$data->weapons->{$offsets[$wid]}->accuracy = $shots > 0 ? $hits / $shots * 100 : 0;
			$wid++;
		}

		// Usage
		$weapons = $finder->query("//div[@class = 'col_usage']");
		$wid = 0;
		foreach($weapons as $row) {
			if($row->nodeValue === 'Use') continue;
			$data->weapons->{$offsets[$wid]}->use = (int) str_replace('%', '', $row->nodeValue);
			$wid++;
		}

		return $data;

	}

}
