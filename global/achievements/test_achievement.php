<?php

class TestAchievement extends BaseAchievement {
  // toy example of an achievement.
  public $id=1;
  protected $name="13/f/cali";
  protected $points=10;
  protected $description="Updated your bio.";
  protected $imagePath="img/achievements/1/1.png";
  protected $events=array('User.afterUpdate');
  protected $dependencies=[];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || (isset($updateParams['about']) && $updateParams['about'] != $parent->about && strlen($updateParams['about']) > 0)) {
      return True;
    }
    return False;
  }
}

?>