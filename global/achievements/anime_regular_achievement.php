<?php
class AnimeRegularAchievement extends BaseAchievement {
  public $id=6;
  protected $name="Regular";
  protected $points=75;
  protected $description="Have 100 or more anime in your list.";
  protected $imagePath="";
  protected $events=['AnimeList.afterUpdate'];
  protected $dependencies=[5];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->uniqueLength() >= 100) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return $this->user($parent)->animeList()->uniqueLength() >= 100 ? 1.0 : floatval($this->user($parent)->animeList()->uniqueLength()) / 100.0;
  }
  public function progressString(BaseObject $parent) {
    return $this->user($parent)->animeList()->uniqueLength()."/100 anime";
  }
}
?>