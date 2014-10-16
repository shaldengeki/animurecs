<?php
class AnimeKouhaiAchievement extends Achievement {
  public $id=5;
  public $name="Kouhai";
  public $points=30;
  public $description="You've got a couple of titles tucked under your belt. But don't get cocky! A vast expanse of genres lies unexplored.<br />Have 50 or more anime in your list.";
  public $imagePath="";
  public $events=['AnimeList.afterUpdate'];
  public $dependencies=[2];

  public function validateUser($event, Model $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->uniqueLength() >= 50) {
      return True;
    }
    return False;
  }
  public function progress(Model $parent) {
    return $this->user($parent)->animeList()->uniqueLength() >= 50 ? 1.0 : floatval($this->user($parent)->animeList()->uniqueLength()) / 50.0;
  }
  public function progressString(Model $parent) {
    return $this->user($parent)->animeList()->uniqueLength()."/50 anime";
  }
}
?>