<?php
return;
class SenpaiNoticedMeAchievement extends BaseAchievement {
  public $id=11;
  protected $name="Senpai Noticed Me!";
  protected $points=10;
  protected $description="Your heart's beating so quickly, it feels like it's going to burst. Did he really look your way?<br />Have your profile viewed by a user with the 'Senpai' achievement.";
  protected $imagePath="";
  protected $events=['User.viewProfile'];
  protected $dependencies=[];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    // not implemented yet ;_;
    if ($this->alreadyAwarded($this->user($parent))) {
      return True;
    }
    return False;
  }
  public function progress(BaseObject $parent) {
    return $this->validateUser(Null, $parent) ? 1.0 : 0.0;
  }
  public function progressString(BaseObject $parent) {
    return intval($this->validateUser(Null, $parent))."/1";
  }
}

?>