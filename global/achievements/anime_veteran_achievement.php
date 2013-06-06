<?php
class AnimeVeteranAchievement extends BaseAchievement {
  public $id=7;
  protected $name="Veteran";
  protected $points=100;
  protected $description="Have 250 or more anime in your list.";
  protected $imagePath="";
  protected $events=['AnimeList.afterUpdate'];
  protected $dependencies=[6];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->length() >= 250) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return count($this->user($parent)->animeList->uniqueList) >= 250 ? 1.0 : floatval(count($this->user($parent)->animeList->uniqueList)) / 250.0;
  }
  public function progressString(BaseObject $parent) {
    return count($this->user($parent)->animeList->uniqueList)."/250 anime";
  }
}
?>