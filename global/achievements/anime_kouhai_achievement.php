<?php
class AnimeKouhaiAchievement extends BaseAchievement {
  public $id=5;
  protected $name="Kouhai";
  protected $points=30;
  protected $description="You've got a couple of titles tucked under your belt. But don't get cocky! A vast expanse of genres lies unexplored.<br />Have 50 or more anime in your list.";
  protected $imagePath="";
  protected $events=['AnimeList.afterUpdate'];
  protected $dependencies=[2];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->uniqueLength() >= 50) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return $this->user($parent)->animeList()->uniqueLength() >= 50 ? 1.0 : floatval($this->user($parent)->animeList()->uniqueLength()) / 50.0;
  }
  public function progressString(BaseObject $parent) {
    return $this->user($parent)->animeList()->uniqueLength()."/50 anime";
  }
}
?>