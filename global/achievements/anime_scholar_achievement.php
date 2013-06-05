<?php
class AnimeScholarAchievement extends BaseAchievement {
  public $id=8;
  protected $name="Anime Scholar";
  protected $points=200;
  protected $description="Your accumulated wisdom watching anime means your friends can rely on you for good recommendations.<br />Have 500 or more anime in your list.";
  protected $imagePath="";
  protected $events=['AnimeList.afterUpdate'];
  protected $dependencies=[7];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->length() >= 500) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return count($this->user($parent)->animeList->uniqueList) >= 500 ? 1.0 : floatval(count($this->user($parent)->animeList->uniqueList)) / 500.0;
  }
  public function progressString(BaseObject $parent) {
    return count($this->user($parent)->animeList->uniqueList)."/500 anime";
  }
}

?>