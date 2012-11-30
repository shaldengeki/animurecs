<?php

class AnimeEntry extends BaseEntry {
  protected $anime, $animeId;
  protected $episode;
  protected $list;

  public function __construct(DbConn $database, $id=Null, $params=Null) {
    parent::__construct($database, $id, $params);
    $this->modelTable = "anime_lists";
    $this->modelPlural = "animeLists";
    $this->entryType = "Anime";
    if ($id === 0) {
      $this->anime = new Anime($this->dbConn, 0);
      $this->animeId = $this->userId = 0;
      $this->episode = 0;
      $this->list = new AnimeList($database, 0);
    } else {
      if (!is_numeric($this->animeId)) {
        $this->animeId = Null;
      }
      $this->anime = $this->episode = Null;
      if (!isset($this->list)) {
        $this->list = $this->user()->animeList;
      }
    }
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
  public function formatFeedEntry(User $currentUser) {
    // fetch the previous feed entry and compare values against current entry.

    $outputTimezone = new DateTimeZone(OUTPUT_TIMEZONE);
    $serverTimezone = new DateTimeZone(SERVER_TIMEZONE);
    $nowTime = new DateTime("now", $outputTimezone);

    $diffInterval = $nowTime->diff($this->time());
    $prevEntry = $this->list->prevEntry($this->anime()->id, $this->time());

    $statusChanged = (bool) ($this->status() != $prevEntry->status());
    $scoreChanged = (bool) ($this->score() != $prevEntry->score());
    $partChanged = (bool) ($this->{$this->list->partName}() != $prevEntry->{$this->list->partName}());
    
    // concatenate appropriate parts of this status text.
    $statusTexts = [];
    if ($statusChanged) {
      $statusTexts[] = $this->list->statusStrings[intval((bool)$prevEntry)][intval($this->status())];
    }
    if ($scoreChanged) {
      $statusTexts[] = $this->list->scoreStrings[intval($this->score() == 0)][intval($statusChanged)];
    }
    if ($partChanged && ($this->{$this->list->partName} != $this->anime()->{$this->list->partName."Count"} || $this->status() != 2)) {
      $statusTexts[] = $this->list->partStrings[intval($statusChanged || $scoreChanged)];
    }
    $statusText = implode(" ", $statusTexts);

    // replace placeholders.
    $statusText = str_replace("[TYPE_VERB]", $this->list->typeVerb, $statusText);
    $statusText = str_replace("[PART_NAME]", $this->list->partName, $statusText);
    $statusText = str_replace("[TITLE]", $this->anime()->link("show", $this->anime()->title), $statusText);
    $statusText = str_replace("[SCORE]", $this->score(), $statusText);
    $statusText = str_replace("[PART]", $this->{$this->list->partName}, $statusText);
    $statusText = str_replace("[TOTAL_PARTS]", $this->anime()->{$this->list->partName."Count"}, $statusText);
    $statusText = ucfirst($statusText).".";

    return array('title' => $this->user()->link("show", $this->user()->username), 'text' => $statusText);
  }

}

?>