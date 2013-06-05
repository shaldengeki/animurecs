<?php
class ImportMALAchievement extends BaseAchievement {
  public $id=3;
  protected $name="Moving In";
  protected $points=25;
  protected $description="Imported your MAL.";
  protected $imagePath="img/achievements/3/3.png";
  protected $events=['User.importMAL'];
  protected $dependencies=[];

  public function validateUser($event, BaseObject $parent, array $updateParams=Null) {
    if ($this->alreadyAwarded($this->user($parent))) {
      return True;
    }
    $updateParams = $updateParams ? $updateParams : [];
    foreach ($updateParams as $value) {
      if ($value) {
        return True;
      }
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