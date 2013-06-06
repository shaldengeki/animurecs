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
    if ($this->alreadyAwarded($this->user($parent)) || $parent->length() >= 100) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return count($this->user($parent)->animeList->uniqueList) >= 100 ? 1.0 : floatval(count($this->user($parent)->animeList->uniqueList)) / 100.0;
  }
  public function progressString(BaseObject $parent) {
    return count($this->user($parent)->animeList->uniqueList)."/100 anime";
  }
}
?>