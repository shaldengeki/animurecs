<?php
class ImportMALAchievement extends Achievement {
  public $id=3;
  public $name="Moving In";
  public $points=25;
  public $description="Imported your MAL.";
  public $imagePath="img/achievements/3/3.png";
  public $events=['User.importMAL'];
  public $dependencies=[];

  public function validateUser($event, Model $parent, array $updateParams=Null) {
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
  public function progress(Model $parent) {
    return $this->validateUser(Null, $parent) ? 1.0 : 0.0;
  }
  public function progressString(Model $parent) {
    return intval($this->validateUser(Null, $parent))."/1";
  }
}

?>