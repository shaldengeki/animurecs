<?php
class AnimeEliteAchievement extends BaseAchievement {
  public $id=8;
  public $name="Elite";
  public $points=200;
  public $description="Your accumulated wisdom watching anime means your friends can rely on you for good recommendations.<br />Have 500 or more anime in your list.";
  public $imagePath="";
  public $events=['AnimeList.afterUpdate'];
  public $dependencies=[7];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->uniqueLength() >= 500) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return $this->user($parent)->animeList()->uniqueLength() >= 500 ? 1.0 : floatval($this->user($parent)->animeList()->uniqueLength()) / 500.0;
  }
  public function progressString(BaseObject $parent) {
    return $this->user($parent)->animeList()->uniqueLength()."/500 anime";
  }
}
?>