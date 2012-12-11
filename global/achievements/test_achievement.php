<?php

class TestAchievement extends BaseAchievement {
  // toy example of an achievement.
  const ID = 1;
  public function update($event, $parent, $updateParams=Null) {
    if (!$this->alreadyAwarded($parent) && isset($updateParams['about']) && $updateParams['about'] != $parent->about && strlen($updateParams['about']) > 0) {
      // if this was a real achievement, we'd update the user's achievement mask here.
      redirect_to($parent->url(), array('achievement_id' => $this::ID, 'status' => "Hey, congrats on updating your bio! :3", 'class' => 'success'));
    }
  }
}

?>