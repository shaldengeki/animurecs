<?php
abstract class BaseAchievement extends BaseObject {
  protected $name, $description, $imagePath="";
  protected $dependencies=[];

  // getter methods.
  public function __construct(DbConn $database, $id=Null) {
    parent::__construct($database, $id);
  }
  public function name() {
    return $this->returnInfo('name');
  }
  public function description() {
    return $this->returnInfo('description');
  }
  public function imagePath() {
    return $this->returnInfo('imagePath');
  }
  public function user(BaseObject $parent) {
    if (method_exists($parent, 'user') && $parent->user() instanceof User) {
      return $parent->user();
    } elseif ($parent instanceof User) {
      return $parent;
    } else {
      return Null;
    }
  }

  // authentication and validation functions.
  public function allow(User $authingUser, $action, array $params=Null) {
    switch ($action) {
      case 'new':
      case 'edit':
      case 'delete':
        if ($authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'index':
      case 'show':
        return True;
        break;
      default:
        return False;
        break;
    }
  }
  public function alreadyAwarded(User $user) {
    return $user->achievementMask && ($user->achievementMask & pow(2, $this->id - 1));
  }
  public function dependenciesPresent(User $user) {
    foreach ($this->dependencies as $dependency) {
      if (!$dependency->alreadyAwarded($user)) {
        return False;
      }
    }
    return True;
  }

  // function called upon event firing. awards user achievement if they are a valid candidate.
  public function update($event, BaseObject $parent, array $updateParams=Null) {
    if (!$this->alreadyAwarded($this->user($parent)) && $this->dependenciesPresent($this->user($parent)) && $this->validateUser($event, $parent, $updateParams)) {
      // award achievement and notify user.
      redirect_to($this->user($parent)->url(), array("status" => "Congrats, you've been awarded the achievement ".$this->name()."!", "class" => "success"));
    }
  }

  // returns bool indicating whether user is able to recieve this achievement.
  abstract public function validateUser($event, BaseObject $parent, array $updateParams=Null);
}
?>