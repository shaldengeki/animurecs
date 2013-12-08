<?php
class TestAchievement extends BaseAchievement {
  // toy example of an achievement.
  public $id=1;
  public $name="13/f/cali";
  public $points=10;
  public $description="Updated your bio.";
  public $imagePath="img/achievements/1/1.png";
  public $events=['User.afterUpdate'];
  public $dependencies=[];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || (isset($updateParams['about']) && $updateParams['about'] != $parent->about && mb_strlen($updateParams['about']) > 0)) {
      return True;
    }
    return False;
  }
  public function level() {
    return 1;
  }
  public function progress(BaseObject $parent) {
    return $this->user($parent)->about ? 1.0 : 0.0;
  }
  public function progressString(BaseObject $parent) {
    return intval((bool) $this->user($parent)->about)."/1";
  }
}

?>