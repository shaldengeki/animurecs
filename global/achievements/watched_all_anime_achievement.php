<?php
class WatchedAllAnimeAchievement extends BaseAchievement {
  public $id=9;
  protected $name="President of Anime";
  protected $points=1000;
  protected $description="Watch all the anime on Animurecs.";
  protected $imagePath="";
  protected $events=['AnimeList.afterUpdate'];
  protected $dependencies=[8];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->length() >= 200) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return count($this->user($parent)->animeList->uniqueList) >= Anime::count($this->app) ? 1.0 : floatval(count($this->user($parent)->animeList->uniqueList)) / Anime::count($this->app);
  }
  public function progressString(BaseObject $parent) {
    return count($this->user($parent)->animeList->uniqueList)."/".Anime::count($this->app)." anime";
  }
}

?>