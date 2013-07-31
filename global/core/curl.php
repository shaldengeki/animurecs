<?php
class CurlException extends Exception {
  public function __construct($message = null, $code = 0, Exception $previous = null) {
    parent::__construct($message, $code, $previous);
  }
  public function display() {
    // displays end user-friendly output explaining the exception that occurred.
    echo "A cURL occurred: ".$this->message.". The staff has been notified; sorry for the inconvenience!";
  }
}

class Curl {
  use Loggable;

  protected $curl, $url, $cookie, $agent, $encoding, $referer;
  public function __construct($url) {
    $this->reset();
    $this->unlog()->url($url);
  }
  public function reset() {
    if ($this->curl) {
      curl_close($this->curl);
    }
    $this->curl = curl_init();
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, True);
    curl_setopt($this->curl, CURLOPT_MAXREDIRS, 2);
    $this->cookie("")
      ->agent("Animurecs")
      ->encoding("gzip,deflate")
      ->referer(Config::ROOT_URL)
      ->ssl(False)
      ->timeout(1000)
      // ->connectTimeout(500)
      ->follow();
    return $this;
  }
  public function url($url) {
    curl_setopt($this->curl, CURLOPT_URL, $url);
    return $this;
  }
  public function cookie($cookie) {
    curl_setopt($this->curl, CURLOPT_COOKIE, $cookie);
    return $this;
  }
  public function agent($agent) {
    curl_setopt($this->curl, CURLOPT_USERAGENT, $agent);
    return $this;
  }
  public function encoding($encoding) {
    curl_setopt($this->curl, CURLOPT_ENCODING, $encoding);
    return $this;
  }
  public function referer($referer) {
    curl_setopt($this->curl, CURLOPT_REFERER, $referer);
    return $this;
  }
  public function ssl($ssl=True) {
    curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, $ssl);
    return $this;
  }
  public function follow($follow=True) {
    curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, $follow);
    return $this;
  }
  public function timeout($timeout) {
    curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, $timeout);
    return $this;
  }
  public function connectTimeout($connectTimeout) {
    curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, $connectTimeout);
    return $this;
  }
  public function fields($fields) {
    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $fields);
    return $this;
  }
  public function get() {
    $result = curl_exec($this->curl);
    $curlError = curl_error($this->curl);
    if ($this->canLog()) {
      $this->logger->err("Got URL: ".curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL));
      $this->logger->err("Transfer info: ".print_r(curl_getinfo($this->curl), True));
      $this->logger->err("Result: ".$result);
      $this->logger->err("Error: ".$curlError);
    }
    $this->reset();
    if ($curlError || !$result) {
      throw new CurlException("Error: ".$curlError."\nResult: ".$result);
    } else {
      return $result;      
    }
  }
  public function post() {
    curl_setopt($this->curl, CURLOPT_POST, True);
    return $this->get();
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

  $curl = new Curl("http://myanimelist.net/malappinfo.php?u=".rawurlencode($username)."&status=all&type=".rawurlencode($type));
  $xmlPage = $curl->get();

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

    if ($lastUpdated === '0000-00-00') {
      if ($endDate === '0000-00-00') {
        if (!$startDate) {
          $time = $nowTime;
        } else {
          $time = new DateTime($startDate, $serverTimezone);
        }
      } else {
        $time = new DateTime($endDate, $serverTimezone);
      }
    } else {
      $time = new DateTime('@'.$lastUpdated, $serverTimezone);
    }
    $animeList[intval($animeID)] = [
      'anime_id' => $animeID,
      'episode' => $episode,
      'score' => $score,
      'status' => $status,
      'time' => $time->format("Y-m-d H:i:s")
    ];
  }
  return $animeList;
}
?>