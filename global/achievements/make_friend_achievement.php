<?php
class MakeFriendAchievement extends BaseAchievement {
  public $id=4;
  protected $name="Hookin' Up";
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
}
?>