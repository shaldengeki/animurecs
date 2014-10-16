<?php
class WatchedAllAnimeAchievement extends Achievement {
  public $id=10;
  public $name="Senpai";
  public $points=1000;
  public $description="Many have tried, but virtually all fail. You can honestly say you've seen all there is to see! Let's hope you remember all of it.<br />Watch all the anime on Animurecs.";
  public $imagePath="";
  public $events=['AnimeList.afterUpdate'];
  public $dependencies=[8];

  public function validateUser($event, Model $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->uniqueLength() == Anime::Count($this->app)) {
      return True;
    }
    return False;
  }
  public function progress(Model $parent) {
    return $this->user($parent)->animeList()->uniqueLength() >= Anime::Count($this->app) ? 1.0 : floatval($this->user($parent)->animeList()->uniqueLength()) / Anime::Count($this->app);
  }
  public function progressString(Model $parent) {
    return $this->user($parent)->animeList()->uniqueLength()."/".Anime::Count($this->app)." anime";
  }
}

?>