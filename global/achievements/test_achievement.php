<?php

class TestAchievement extends BaseAchievement {
  // toy example of an achievement.
  protected $name="13/f/cali";
  protected $description="Updated your bio.";

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if (isset($updateParams['about']) && $updateParams['about'] != $parent->about && strlen($updateParams['about']) > 0) {
      return True;
    }
    return False;
  }
}

?>