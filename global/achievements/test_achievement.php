<?php
class TestAchievement extends BaseAchievement {
  // toy example of an achievement.
  public $id=1;
  protected $name="13/f/cali";
  protected $points=10;
  protected $description="Updated your bio.";
  protected $imagePath="img/achievements/1/1.png";
  protected $events=['User.afterUpdate'];
  protected $dependencies=[];

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