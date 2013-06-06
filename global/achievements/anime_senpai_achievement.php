<?php
class WatchedAllAnimeAchievement extends BaseAchievement {
  public $id=10;
  protected $name="Senpai";
  protected $points=1000;
  protected $description="Many have tried, but virtually all fail. You can honestly say you've seen all there is to see! Let's hope you remember all of it.<br />Watch all the anime on Animurecs.";
  protected $imagePath="";
  protected $events=['AnimeList.afterUpdate'];
  protected $dependencies=[9];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->uniqueLength() == Anime::count($this->app)) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return $this->user($parent)->animeList->uniqueLength() >= Anime::count($this->app) ? 1.0 : floatval($this->user($parent)->animeList->uniqueLength()) / Anime::count($this->app);
  }
  public function progressString(BaseObject $parent) {
    return $this->user($parent)->animeList->uniqueLength()."/".Anime::count($this->app)." anime";
  }
}

?>