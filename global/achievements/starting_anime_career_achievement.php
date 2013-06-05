<?php
class StartingAnimeCareerAchievement extends BaseAchievement {
  public $id=2;
  protected $name="Starting My Anime Career";
  protected $points=10;
  protected $description="You've embarked on an epic journey. Where will it take you? Only time will tell.<br />Added an anime to your list.";
  protected $imagePath="img/achievements/2/2.png";
  protected $events=['AnimeList.afterUpdate'];
  protected $dependencies=[];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->length() > 0) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return $this->user($parent)->animeList->length ? 1.0 : 0.0;
  }
  public function progressString(BaseObject $parent) {
    return $this->user($parent)->animeList->length."/1";
  }
}

?>