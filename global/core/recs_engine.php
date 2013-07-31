<?php

class RecsEngine {
  use Loggable;
  protected $host, $port;
  
  public function __construct($host=Null, $port=Null) {
    $this->host = $host;
    $this->port = $port;
    $this->unlog();
  }
  public function __get($property) {
    // A property accessor exists
    if (method_exists($this, $property)) {
      return $this->$property();
    } elseif (property_exists($this, $property)) {
      return $this->$property;
    }
  }
  public function get($model, $id, $action, array $params=Null, $json=True) {
    if ($params === Null) {
      $requestFields = "";
    } else {
      $requestFields = http_build_query($params);
    }
    $url = "http://".$this->host.":".intval($this->port)."/".rawurlencode($model)."/".intval($id)."/".rawurlencode($action)."?".$requestFields;
    $curl = new Curl($url);
    if ($this->canLog()) {
      $this->logger->err("Getting recs URL: ".$url);
      $curl->log($this->logger);
    }
    $page = $curl->get();
    return $page ? ($json ? json_decode($page, True) : $page) : False;
  }
  public function animeAverage(Anime $anime) {
    return $this->get("anime", $anime->id, "average", Null, False);
  }
  public function animeFeatures(Anime $anime) {
    return $this->get("anime", $anime->id, "features");
  }
  public function userFeatures(User $user) {
    return $this->get("user", $user->id, "features");
  }
  public function predict(User $user, $anime, $start=0, $n=20) {
    // fetches the predicted score for a user and a list of (or just one) anime
    if (is_array($anime)) {
      $animeIDs = [];
      foreach ($anime as $a) {
        $animeIDs[intval($a->id)] = intval($a->id);
      }
    } else {
      $animeIDs = [intval($anime->id) => intval($anime->id)];
    }
    return $this->get("user", $user->id, "predict", ['start' => intval($start), 'n' => intval($n), 'anime' => $animeIDs]);
  }
  public function recommend(User $user, $start=0, $n=20) {
    return $this->get("user", $user->id, "recommend", ['start' => intval($start), 'n' => intval($n)]);
  }
  public function similarAnime(Anime $anime, $start=0, $n=20) {
    // fetches the top n most feature-similar anime for a given anime.
    return $this->get("anime", $anime->id, "similar", ['start' => intval($start), 'n' => intval($n)]);
  }
  public function similarUsers(User $user, $n=20) {
    // fetches the top n most feature-similar users for a given user.
    return $this->get("user", $user->id, "similar", ['n' => intval($n)]);
  }
  public function compatibilities(User $user, $users) {
    // fetches compatibilities between a user and a single user or group of users.
    if (is_array($users)) {
      $userIDs = [];
      foreach ($users as $u) {
        $userIDs[intval($u->id)] = intval($u->id);
      }
    } else {
      $userIDs = [intval($users->id) => intval($users->id)];
    }
    return $this->get("user", $user->id, "compatibilities", ['users' => $userIDs]);
  } 
}
?>