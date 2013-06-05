<?php
class MakeFriendAchievement extends BaseAchievement {
  public $id=4;
  protected $name="Haganai!";
  protected $points=50;
  protected $description="Made a friend on Animurecs.";
  protected $imagePath="img/achievements/4/4.png";
  protected $events=['User.confirmFriend'];
  protected $dependencies=[];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || count($parent->friends()) > 0) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return $this->user($parent)->friends ? 1.0 : 0.0;
  }
  public function progressString(BaseObject $parent) {
    return intval((bool) $this->user($parent)->friends)."/1";
  }
}
?>