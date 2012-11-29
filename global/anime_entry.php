<?php

class AnimeEntry extends BaseEntry {
  protected $anime, $animeId, $user, $userId;
  protected $time;
  protected $status, $score, $episode;

  public function __construct(DbConn $database, $id=Null, $user=Null) {
    parent::__construct($database, $id, $user);
    if ($id === 0) {
      $this->anime = new Anime($this->dbConn, 0);
      $this->animeId = $this->userId = 0;
      $this->episode = 0;   
    } else {
      $this->anime = $this->animeId = $this->episode = Null;
    }
    $this->modelTable = "anime_lists";
    $this->modelPlural = "animeLists";
    $this->partName = "episode";
    $this->entryType = "Anime";
    $this->typeVerb = "watching";
    $this->feedType = "Anime";
    $this->entryTypeLower = strtolower($this->entryType);
    $this->typeID = $this->entryTypeLower.'_id';
  }
  public function animeId() {
    return $this->returnInfo('animeId');
  }
  public function anime() {
    if ($this->anime === Null) {
      $this->anime = new Anime($this->dbConn, $this->animeId());
    }
    return $this->anime;
  }
  public function episode() {
    return $this->returnInfo('episode');
  }
}

?>