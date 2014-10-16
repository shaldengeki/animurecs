<?php
class StartingAnimeCareerAchievement extends BaseAchievement {
  public $id=2;
  public $name="Starting My Anime Career";
  public $points=10;
  public $description="You've embarked on an epic journey. Where will it take you? Only time will tell.<br />Added an anime to your list.";
  public $imagePath="img/achievements/2/2.png";
  public $events=['AnimeList.afterUpdate'];
  public $dependencies=[];

  public function validateUser($event, Model $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->length() > 0) {
      return True;
    }
    return False;
  }
  public function progress(Model $parent) {
    return $this->user($parent)->animeList()->length ? 1.0 : 0.0;
  }
  public function progressString(Model $parent) {
    return $this->user($parent)->animeList()->length."/1";
  }
}

?>