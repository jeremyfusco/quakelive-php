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
	 * @param Quakelive\Profile $profile
	 * @param bool $statistics_parsed
	 */
	public function __construct(Quakelive\Profile $profile, $statistics_parsed) {
		$this->data = self::fetch($profile, $statistics_parsed);
		$this->data->freeze();
	}

	/**
	 * Fetches data from server
	 * @param Quakelive\Profile $profile
	 * @param bool $statistics_parsed
	 * @return Quakelive\ArrayHash
	 */
	public static function fetch(Quakelive\Profile $profile, $statistics_parsed = true) {

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

		// Records
		$table = $finder->query("//div[@class = 'prf_record']/div/div");
		$data->records = new Quakelive\ArrayHash;
		unset($offset);
		foreach($table as $row) {
			if(!isset($offset) && (!$row->attributes->getNamedItem('class') || $row->nodeValue === '')) continue;
			if($row->attributes->getNamedItem('class')->nodeValue === 'col_st_gametype') {
				$image = $finder->query("img", $row)->item(0);
				if(!$image) break;
				preg_match('/\/xsm\/(.+?)_/', $image->attributes->getNamedItem('src')->nodeValue, $matches);
				$offset = $matches[1];
				$data->records->{$offset} = new Quakelive\ArrayHash;
				$data->records->{$offset}->image = $image->attributes->getNamedItem('src')->nodeValue;
				$data->records->{$offset}->name = trim($row->nodeValue);
			}
			if($row->attributes->getNamedItem('class')->nodeValue === 'col_st_view') {
				unset($offset);
				continue;
			}
			$column = substr($row->attributes->getNamedItem('class')->nodeValue, 7);
			switch($column) {
				case 'gametype':
				break;
				case 'completeperc':
					$data->records->{$offset}->completeperc =
						$data->records->{$offset}->played > 0 ?
						$data->records->{$offset}->finished / $data->records->{$offset}->played * 100 : 0;
				break;
				case 'winperc':
					$data->records->{$offset}->winperc =
						$data->records->{$offset}->played > 0 ?
						$data->records->{$offset}->wins / $data->records->{$offset}->played * 100 : 0;
				break;
				default:
					$data->records->{$offset}->{$column} = intval(str_replace(',', '', $row->nodeValue));
				break;
			}
		}

		// Collect skill levels
		$keys = $finder->query("//div[@class = 'keys']/img");
		$bars = $finder->query("//div[@class = 'bars']/div");
		$data->skills = new Quakelive\ArrayHash;
		foreach($keys as $index => $row) {
			$offset = substr($row->attributes->getNamedItem('class')->nodeValue, 8);
			$data->skills->{$offset} = new Quakelive\ArrayHash;
			$data->skills->{$offset}->name = $row->attributes->getNamedItem('title')->nodeValue;
			$data->skills->{$offset}->image = $row->attributes->getNamedItem('src')->nodeValue;
			preg_match('/^height: (\d+?)%/', $bars->item($index)->attributes->getNamedItem('style')->nodeValue, $matches);
			$data->skills->{$offset}->level = intval($matches[1]) / 25;
		}

		// Parse summary if needed
		if(!$statistics_parsed) $profile->getSummary($dom, $finder);

		return $data;

	}

}
