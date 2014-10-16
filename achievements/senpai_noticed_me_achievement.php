<?php
return;
class SenpaiNoticedMeAchievement extends BaseAchievement {
  public $id=11;
  public $name="Senpai Noticed Me!";
  public $points=10;
  public $description="Your heart's beating so quickly, it feels like it's going to burst. Did he really look your way?<br />Have your profile viewed by a user with the 'Senpai' achievement.";
  public $imagePath="";
  public $events=['User.viewProfile'];
  public $dependencies=[];

  public function validateUser($event, Model $parent, array $updateParams=Null) {
    // not implemented yet ;_;
    if ($this->alreadyAwarded($this->user($parent))) {
      return True;
    }
    return False;
  }
  public function progress(Model $parent) {
    return $this->validateUser(Null, $parent) ? 1.0 : 0.0;
  }
  public function progressString(Model $parent) {
    return intval($this->validateUser(Null, $parent))."/1";
  }
}

?>