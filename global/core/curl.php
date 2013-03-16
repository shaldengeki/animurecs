<?php
function hitPage($page, $cookieString="", $ssl=False, $referer=Config::ROOT_URL) {
	$ch = curl_init();
	if ($ssl) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE); 
	}
	curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
	curl_setopt($ch, CURLOPT_USERAGENT, "Animurecs");
	curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
	curl_setopt($ch, CURLOPT_URL, $page);
	curl_setopt($ch, CURLOPT_REFERER, $referer);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
	// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
	// curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
	$ret = curl_exec($ch);
	if (curl_error($ch)) {
		if (Config::DEBUG_ON) {
			print_r(curl_error($ch));
		}
		curl_close($ch);
		return False;
	} else {
		curl_close($ch);
		return $ret;
	}
}

function hitForm($url, $formFields, $cookieString="", $ssl=False, $referer=Config::ROOT_URL) {
	$ch = curl_init();
	if ($ssl) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE); 
	}
	curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
	curl_setopt($ch, CURLOPT_USERAGENT, "Animurecs");
	curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_REFERER, $referer);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $formFields);
	curl_setopt($ch, CURLOPT_URL, $url);
	// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
	// curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
	$ret = curl_exec($ch);
	if (curl_error($ch)) {
		curl_close($ch);
		return False;
	} else {
    curl_close($ch);
    return $ret;
	}
}

function get_enclosed_string($haystack, $needle1, $needle2="", $offset=0) {
	if ($needle1 == "") {
		$needle1_pos = 0;
	} else {
		$needle1_pos = strpos($haystack, $needle1, $offset) + strlen($needle1);
		if ($needle1_pos === FALSE || ($needle1_pos != 0 && !$needle1_pos) || $needle1_pos > strlen($haystack)) {
			return false;
		}
	}
	if ($needle2 == "") {
		$needle2_pos = strlen($haystack);
	} else {
		$needle2_pos = strpos($haystack, $needle2, $needle1_pos);
		if ($needle2_pos === FALSE || !$needle2_pos) {
			return false;
		}
	}
	if ($needle1_pos > $needle2_pos || $needle1_pos < 0 || $needle2_pos < 0 || $needle1_pos > strlen($haystack) || $needle2_pos > strlen($haystack)) {
		return false;
	}
	
    $enclosed_string = substr($haystack, $needle1_pos, $needle2_pos - $needle1_pos);
    return $enclosed_string;
}

function get_last_enclosed_string($haystack, $needle1, $needle2="") {
	//this is the last, smallest possible enclosed string.
	//position of first needle is as close to the end of the haystack as possible
	//position of second needle is as close to the first needle as possible
	if ($needle2 == "") {
		$needle2_pos = strlen($haystack);
	} else {
		$needle2_pos = strrpos($haystack, $needle2);
		if ($needle2_pos === FALSE) {
			return false;
		}
	}
	if ($needle1 == "") {
		$needle1_pos = 0;
	} else {
		$needle1_pos = strrpos(substr($haystack, 0, $needle2_pos), $needle1) + strlen($needle1);
		if ($needle1_pos === FALSE) {
			return false;
		}
	}
	if ($needle2 != "") {
		$needle2_pos = strpos($haystack, $needle2, $needle1_pos);
		if ($needle2_pos === FALSE) {
			return false;
		}
	}
    $enclosed_string = substr($haystack, $needle1_pos, $needle2_pos - $needle1_pos);
    return $enclosed_string;
}

function get_biggest_enclosed_string($haystack, $needle1, $needle2="") {
	//this is the largest possible enclosed string.
	//position of last needle is as close to the end of the haystack as possible.
	
	if ($needle1 == "") {
		$needle1_pos = 0;
	} else {
		$needle1_pos = strpos($haystack, $needle1) + strlen($needle1);
		if ($needle1_pos === FALSE) {
			return false;
		}
	}
	if ($needle2 == "") {
		$needle2_pos = strlen($haystack);
	} else {
		$needle2_pos = strrpos($haystack, $needle2, $needle1_pos);
		if ($needle2_pos === FALSE) {
			return false;
		}
	}
    $enclosed_string = substr($haystack, $needle1_pos, $needle2_pos - $needle1_pos);
    return $enclosed_string;
}

function parseMALList($username, $type="anime") {
	// hits a MAL list of the given type for the given username.
	// returns an associative array containing the resultant XML, or False if an error occurred.

  $outputTimezone = new DateTimeZone(Config::OUTPUT_TIMEZONE);
  $serverTimezone = new DateTimeZone(Config::SERVER_TIMEZONE);
  $nowTime = new DateTime("now", $serverTimezone);

	$xmlPage = hitPage("http://myanimelist.net/malappinfo.php?u=".urlencode($username)."&status=all&type=".urlencode($type));
	if (!$xmlPage) {
		return False;
	}
	$listXML = new DOMDocument();
	$listXML->loadXML($xmlPage);
	$animeNodes = $listXML->getElementsByTagName('anime');
	$animeList = [];
	foreach ($animeNodes as $animeNode) {
		$animeID = intval($animeNode->getElementsByTagName('series_animedb_id')->item(0)->nodeValue);
		$episode = intval($animeNode->getElementsByTagName('my_watched_episodes')->item(0)->nodeValue);
		$startDate = $animeNode->getElementsByTagName('my_start_date')->item(0)->nodeValue;
		$endDate = $animeNode->getElementsByTagName('my_finish_date')->item(0)->nodeValue;
		$lastUpdated = intval($animeNode->getElementsByTagName('my_last_updated')->item(0)->nodeValue);
		$status = intval($animeNode->getElementsByTagName('my_status')->item(0)->nodeValue);
		$score = intval($animeNode->getElementsByTagName('my_score')->item(0)->nodeValue);

		if ($startDate === '0000-00-00') {
			if ($endDate === '0000-00-00') {
				if (!$lastUpdated) {
					$time = $nowTime;
				} else {
					$time = new DateTime('@'.$lastUpdated, $outputTimezone);
				}
			} else {
				$time = new DateTime($endDate, $outputTimezone);
			}
		} else {
			$time = new DateTime($startDate, $outputTimezone);
		}
		$time->setTimezone($serverTimezone);

		$animeList[intval($animeID)] = array(
			'anime_id' => $animeID,
			'episode' => $episode,
			'score' => $score,
			'status' => $status,
			'time' => $time->format("Y-m-d H:i:s")
		);
	}
	return $animeList;
}
?>