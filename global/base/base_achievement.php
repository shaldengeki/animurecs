<?php
abstract class BaseAchievement extends BaseObject {
  protected $name, $description, $imagePath="";
  protected $points=0;
  protected $dependencies=[];

  // getter methods.
  public function __construct(Application $app, $id=Null) {
    // parent::__construct($app, $id);
    parent::__construct($app, $this->id);
  }
  public function name() {
    return $this->returnInfo('name');
  }
  public function description() {
    return $this->returnInfo('description');
  }
  public function points() {
    return $this->returnInfo('points');
  }
  public function imagePath() {
    return $this->returnInfo('imagePath');
  }
  public function dependencies() {
    return $this->returnInfo('dependencies');
  }
  public function imageTag(array $params=Null) {
    $imageParams = [];
    // $params['class'] = isset($params['class']) ? $params['class'] .= " img-circle" : "img-circle";
    if (is_array($params) && $params) {
      foreach ($params as $key => $value) {
        $imageParams[] = escape_output($key)."='".escape_output($value)."'";
      }
    }
    $imgPath = $this->imagePath() ? $this->imagePath() : "http://placehold.it/100x100";
    return "<img src='".joinPaths(Config::ROOT_URL, escape_output($this->imagePath()))."' ".implode(" ", $imageParams)." />";
  }
  public function events() {
    return $this->returnInfo('events');
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
      if (!$this->app->achievements[$dependency]->alreadyAwarded($user)) {
        return False;
      }
    }
    return True;
  }

  // function called upon event firing. awards user achievement if they are a valid candidate and removes user achievement if they aren't.
  public function update($event, BaseObject $parent, array $updateParams=Null) {
    if (!$this->alreadyAwarded($this->user($parent)) && $this->dependenciesPresent($this->user($parent)) && $this->validateUser($event, $parent, $updateParams)) {
      // award achievement and notify user.
      if ($this->user($parent)->addAchievement($this)) {
        $parent->app->delayedMessages[] = "Congrats, you've been awarded the achievement ".$this->name()."!";
      }
    } elseif ($this->alreadyAwarded($this->user($parent)) && (!$this->dependenciesPresent($this->user($parent)) || !$this->validateUser($event, $parent, $updateParams))) {
      if ($this->user($parent)->removeAchievement($this)) {
        $parent->app->delayedMessages[] = "Unfortunately, you no longer meet the requirements for the achievement ".$this->name().", so it's been removed.";
      }
    }
  }

  public function level() {
    if (!$this->dependencies()) {
      return 1;
    } else {
      return max(array_map(function($dep) {return $dep->level() + 1; }, $this->app->achievements[$this->id]->dependencies()));
    }
  }

  public function children() {
    $children = [];
    foreach ($this->app->achievements as $id=>$achievement) {
      if (in_array($this->id, $achievement->dependencies())) {
        $children[] = $achievement;
      }
    }
    return $children;
  }

  // returns bool indicating whether user is able to recieve this achievement.
  abstract public function validateUser($event, BaseObject $parent, array $updateParams=Null);

  // returns float indicating current progress towards this achievement.
  abstract public function progress(BaseObject $parent);

  abstract public function progressString(BaseObject $parent);
}
?>