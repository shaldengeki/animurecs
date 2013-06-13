<?php
class SomeKindOfMonausicaaAchievement extends BaseAchievement {
  public $id=9;
  protected $name="Some Kind of Monausicaa";
  protected $points=500;
  protected $description="You've done the impossible. You've surpassed the SAT's most-prolific anime watcher!<br />Watch more anime as monausicaa.";
  protected $imagePath="";
  protected $events=['AnimeList.afterUpdate'];
  protected $dependencies=[8];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    $nausicaa = new User($this->user($parent)->app, Null, "monausicaa");
    if ($this->alreadyAwarded($this->user($parent)) || count($parent->listSection(2)) > count($nausicaa->animeList()->listSection(2))) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    $nausicaa = new User($this->user($parent)->app, Null, "monausicaa");
    return count($this->user($parent)->animeList()->listSection(2)) > count($nausicaa->animeList()->listSection(2)) ? 1.0 : floatval(count($this->user($parent)->animeList()->listSection(2))) / (count($nausicaa->animeList()->listSection(2)) + 1);
  }
  public function progressString(BaseObject $parent) {
    $nausicaa = new User($this->user($parent)->app, Null, "monausicaa");
    return count($this->user($parent)->animeList()->listSection(2))."/".(count($nausicaa->animeList()->listSection(2)) + 1)." anime";
  }
}
?>