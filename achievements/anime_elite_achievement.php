<?php
class AnimeEliteAchievement extends Achievement {
  public $id=8;
  public $name="Elite";
  public $points=200;
  public $description="Your accumulated wisdom watching anime means your friends can rely on you for good recommendations.<br />Have 500 or more anime in your list.";
  public $imagePath="";
  public $events=['AnimeList.afterUpdate'];
  public $dependencies=[7];

  public function validateUser($event, Model $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->uniqueLength() >= 500) {
      return True;
    }
    return False;
  }
  public function progress(Model $parent) {
    return $this->user($parent)->animeList()->uniqueLength() >= 500 ? 1.0 : floatval($this->user($parent)->animeList()->uniqueLength()) / 500.0;
  }
  public function progressString(Model $parent) {
    return $this->user($parent)->animeList()->uniqueLength()."/500 anime";
  }
}
?>