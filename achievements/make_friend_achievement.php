<?php
class MakeFriendAchievement extends BaseAchievement {
  public $id=4;
  public $name="Haganai!";
  public $points=50;
  public $description="Made a friend on Animurecs.";
  public $imagePath="img/achievements/4/4.png";
  public $events=['User.confirmFriend'];
  public $dependencies=[];

  public function validateUser($event, Model $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || count($parent->friends()) > 0) {
      return True;
    }
    return False;
  }
  public function progress(Model $parent) {
    return $this->user($parent)->friends ? 1.0 : 0.0;
  }
  public function progressString(Model $parent) {
    return intval((bool) $this->user($parent)->friends)."/1";
  }
}
?>