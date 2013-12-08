<?php
class AnimeVeteranAchievement extends BaseAchievement {
  public $id=7;
  public $name="Veteran";
  public $points=100;
  public $description="Have 250 or more anime in your list.";
  public $imagePath="";
  public $events=['AnimeList.afterUpdate'];
  public $dependencies=[6];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->uniqueLength() >= 250) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return $this->user($parent)->animeList()->uniqueLength() >= 250 ? 1.0 : floatval($this->user($parent)->animeList()->uniqueLength()) / 250.0;
  }
  public function progressString(BaseObject $parent) {
    return $this->user($parent)->animeList()->uniqueLength()."/250 anime";
  }
}
?>