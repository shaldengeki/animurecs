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
    $nausicaa = new User($parent->app, Null, "monausicaa");
    if ($this->alreadyAwarded($this->user($parent)) || $parent->uniqueLength > $nausicaa->animeList()->uniqueLength()) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    $nausicaa = new User($parent->app, Null, "monausicaa");
    return $this->user($parent)->animeList()->uniqueLength() >= $nausicaa->animeList()->uniqueLength() ? 1.0 : floatval($this->user($parent)->animeList()->uniqueLength()) / $nausicaa->animeList()->uniqueLength();
  }
  public function progressString(BaseObject $parent) {
    $nausicaa = new User($parent->app, Null, "monausicaa");
    return $this->user($parent)->animeList()->uniqueLength()."/".$nausicaa->animeList()->uniqueLength()." anime";
  }
}
?>