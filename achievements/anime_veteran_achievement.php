<?php
class AnimeVeteranAchievement extends Achievement {
  public $id=7;
  public $name="Veteran";
  public $points=100;
  public $description="Have 250 or more anime in your list.";
  public $imagePath="";
  public $events=['AnimeList.afterUpdate'];
  public $dependencies=[6];

  public function validateUser($event, Model $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->uniqueLength() >= 250) {
      return True;
    }
    return False;
  }
  public function progress(Model $parent) {
    return $this->user($parent)->animeList()->uniqueLength() >= 250 ? 1.0 : floatval($this->user($parent)->animeList()->uniqueLength()) / 250.0;
  }
  public function progressString(Model $parent) {
    return $this->user($parent)->animeList()->uniqueLength()."/250 anime";
  }
}
?>