<?php
abstract class BaseAchievement {
  // identifying ID for each achievement.
  const ID=0;

  public function alreadyAwarded($parent) {
    return $parent->achievementMask && ($parent->achievementMask & $this::ID);
  }

  abstract public function update($event, $parent, $updateParams=Null);
}
?>