<?php
class SomeKindOfMonausicaaAchievement extends BaseAchievement {
  public $id=9;
  public $name="Some Kind of Monausicaa";
  public $points=500;
  public $description="You've done the impossible. You've surpassed the SAT's most-prolific anime watcher!<br />Watch more anime as monausicaa.";
  public $imagePath="";
  public $events=['AnimeList.afterUpdate'];
  public $dependencies=[8];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    $nausicaa = User::Get($this->user($parent)->app, ['username' => 'monausicaa']);
    if ($this->alreadyAwarded($this->user($parent)) || count($parent->listSection(2)) > count($nausicaa->animeList()->listSection(2))) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    $nausicaa = User::Get($this->user($parent)->app, ['username' => 'monausicaa']);
    return count($this->user($parent)->animeList()->listSection(2)) > count($nausicaa->animeList()->listSection(2)) ? 1.0 : floatval(count($this->user($parent)->animeList()->listSection(2))) / (count($nausicaa->animeList()->listSection(2)) + 1);
  }
  public function progressString(BaseObject $parent) {
    $nausicaa = User::Get($this->user($parent)->app, ['username' => 'monausicaa']);
    return count($this->user($parent)->animeList()->listSection(2))."/".(count($nausicaa->animeList()->listSection(2)) + 1)." anime";
  }
}
?>