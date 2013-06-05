<?php
class AnimeVicePresidentAchievement extends BaseAchievement {
  public $id=9;
  protected $name="Anime Vice-President";
  protected $points=500;
  protected $description="Breaching this Have 1000 or more anime in your list.";
  protected $imagePath="";
  protected $events=['AnimeList.afterUpdate'];
  protected $dependencies=[8];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->length() >= 1000) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return count($this->user($parent)->animeList->uniqueList) >= 1000 ? 1.0 : floatval(count($this->user($parent)->animeList->uniqueList)) / 1000.0;
  }
  public function progressString(BaseObject $parent) {
    return count($this->user($parent)->animeList->uniqueList)."/1000 anime";
  }
}
?>