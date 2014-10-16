<?php
class AnimeRegularAchievement extends BaseAchievement {
  public $id=6;
  public $name="Regular";
  public $points=75;
  public $description="Have 100 or more anime in your list.";
  public $imagePath="";
  public $events=['AnimeList.afterUpdate'];
  public $dependencies=[5];

  public function validateUser($event, Model $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->uniqueLength() >= 100) {
      return True;
    }
    return False;
  }
  public function progress(Model $parent) {
    return $this->user($parent)->animeList()->uniqueLength() >= 100 ? 1.0 : floatval($this->user($parent)->animeList()->uniqueLength()) / 100.0;
  }
  public function progressString(Model $parent) {
    return $this->user($parent)->animeList()->uniqueLength()."/100 anime";
  }
}
?>