<?php
class GettingStartedAchievement extends BaseAchievement {
  public $id=2;
  protected $name="Getting Started";
  protected $points=10;
  protected $description="Added an entry to your list.";
  protected $imagePath="img/achievements/2/2.png";
  protected $events=['AnimeList.afterUpdate'];
  protected $dependencies=[];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent)) || $parent->length() > 0) {
      return True;
    }
    return False;
  }
}

?>