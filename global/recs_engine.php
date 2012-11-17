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
    return ($json ? json_decode($page) : $page);
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
  public function predict($user, $anime) {
    $prediction = dot($this->predictUserFeatures($user), $this->animeFeatures($anime));
    return $prediction;
  }
  public function svdPredict($user, $anime) {
    // fetches the predicted score for user and anime object pairs.
    return floatval($this->get("user", $user->id, "predict", array('anime' => intval($anime->id))));
  }
  public function recommend($user, $n=100) {
    return $this->get("user", $user->id, "recommend", array('n' => intval($n)));
  }
  
}
?>