<?php

class RecsEngine {
  protected $host, $port;
  public function __construct($host=Null, $port=Null) {
    $this->host = $host;
    $this->port = $port;
  }
  public function __get($property) {
    // A property accessor exists
    if (method_exists($this, $property)) {
      return $this->$property();
    } elseif (property_exists($this, $property)) {
      return $this->$property;
    }
  }
  public function get($model, $id, $action, $params=Null, $json=True) {
    if ($params === Null) {
      $requestFields = "";
    } else {
      $requestFields = http_build_query($params);
    }
    $url = "http://".$this->host.":".intval($this->port)."/".urlencode($model)."/".intval($id)."/".urlencode($action)."?".$requestFields;
    $page = hitPage($url);
    // return hitPage($this->host.":".intval($this->port)."/".urlencode($model)."/".intval($id)."/".urlencode($action)."?".$params);
    return $page ? ($json ? json_decode($page) : $page) : False;
  }
  public function animeAverage($anime) {
    return $this->get("anime", $anime->id, "average", Null, False);
  }
  public function animeFeatures($anime) {
    return $this->get("anime", $anime->id, "features");
  }
  public function userFeatures($user) {
    return $this->get("user", $user->id, "features");
  }
  /*
  public function predictUserFeatures($user) {
    $ratings = [];
    foreach ($user->animeList->uniqueList as $id => $rating) {
      if (intval($rating['score']) != 0) {
        $ratings[intval($id)] = intval($rating['score']);
      }
    }
    return $this->get("user", $user->id, "predictFeatures", array('ratings' => $ratings));
  }
  public function updateUserFeatures($user) {
    $ratings = [];
    foreach ($user->animeList->uniqueList as $id => $rating) {
      if (intval($rating['score']) != 0) {
        $ratings[intval($id)] = intval($rating['score']);
      }
    }
    return $this->get("user", $user->id, "updateFeatures", array('ratings' => $ratings));
  }
  */
  public function predict($user, $anime) {
    // fetches the predicted score for user and anime object pairs.
    return floatval($this->get("user", $user->id, "predict", array('anime' => intval($anime->id))));
  }
  public function recommend($user, $n=20) {
    return $this->get("user", $user->id, "recommend", array('n' => intval($n)));
  }
  public function similarAnime($anime, $n=20) {
    // fetches the top n most feature-similar anime for a given anime.
    return $this->get("anime", $anime->id, "similar", array('n' => intval($n)));
  }
  public function similarUsers($user, $n=20) {
    // fetches the top n most feature-similar users for a given user.
    return $this->get("user", $user->id, "similar", array('n' => intval($n)));
  }
  
}
?>