<?php

abstract class BaseEntry extends BaseObject {
  // feed entry class object.
  use Commentable;

  public static $ENTRY_TYPE, $TYPE_ID, $PART_NAME = "";

  protected $user, $userId;
  protected $time;
  protected $status, $score;

  // this is the parent object to which the entry belongs.
  // e.g. for an animelist entry on a user's page, it'd be the animelist
  // and for a comment entry on the user's page, it'd be the comment
  public $object;

  public function __construct(Application $app, $id=Null, $params=Null) {
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->time = new DateTime("now", $this->app->serverTimeZone);
      $this->status = $this->score = 0;
      $this->comments = [];
    } else {
      $this->user = $this->userId = $this->time = $this->status = $this->score = $this->comments = Null;
    }
    if (is_array($params)) {
      foreach ($params as $key=>$value) {
        $this->{$this->humanizeParameter($key)} = $value;
      }
    }
  }
  public function userId() {
    if ($this->userId === Null) {
      $this->userId = $this->user !== Null ? $this->user->id : $this->returnInfo('userId');
    }
    return $this->userId;
  }
  public function user() {
    if ($this->user === Null) {
      $this->user = new User($this->app, $this->userId());
    }
    return $this->user;
  }
  public function time() {
    return new DateTime($this->returnInfo('time'), $this->app->serverTimeZone);
  }
  public function status() {
    return $this->returnInfo('status');
  }
  public function score() {
    return $this->returnInfo('score');
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'new':
      case 'comment':
        if ($authingUser->loggedIn()) {
          return True;
        }
        return False;
        break;
      case 'edit':
      case 'delete':
        if ($authingUser->id == $this->userId() || $authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'index':
        if ($authingUser->isAdmin()) {
          return True;
        }
        return False;
        break;
      case 'show':
        return True;
        break;
      default:
        return False;
        break;
    }
  }
  public function validate(array $entry) {
    // validates a pending base_entry creation or update.
    $validationErrors = [];

    try {
      parent::validate($entry);
    } catch (ValidationException $e) {
      $validationErrors = array_merge($validationErrors, $e->messages);
    }

    if (!isset($entry['user_id']) || !is_integral($entry['user_id']) || intval($entry['user_id']) < 1) {
      $validationErrors[] = "User ID must be valid";
    }
    try {
      $user = new User($this->app, intval($entry['user_id']));
      $user->getInfo();
    } catch (DbException $e) {
      $validationErrors[] = "User ID must exist";
    }

    if (!isset($entry[static::$TYPE_ID]) || !is_integral($entry[static::$TYPE_ID]) || intval($entry[static::$TYPE_ID]) < 1) {
      $validationErrors[] = static::$ENTRY_TYPE." ID must be valid";
    }
    try {
      $parentMedia = new static::$ENTRY_TYPE($this->app, intval($entry[static::$TYPE_ID]));
      $parentMedia->getInfo();
    } catch (DbException $e) {
      $validationErrors[] = static::$ENTRY_TYPE." ID must exist";
    }

    if (isset($entry['time']) && $entry['time'] && !strtotime($entry['time'])) {
      $validationErrors[] = "Entry time must be a parseable date-time stamp";
    }

    if (!isset($entry['status']) || !is_integral($entry['status']) || !isset(statusArray()[intval($entry['status'])])) {
      $validationErrors[] = "Entry status be one of 0,1,2,3,4,6";
    }

    if (isset($entry['score']) && (!is_numeric($entry['score']) || floatval($entry['score']) < 0 || floatval($entry['score']) > 10)) {
      $validationErrors[] = "Entry score must be numeric and between 0 and 10";
    }

    if (isset($entry[static::$PART_NAME]) && (!is_integral($entry[static::$PART_NAME]) || intval($entry[static::$PART_NAME]) < 0 || ($parentMedia->{static::$PART_NAME."Count"}() > 0 && intval($entry[static::$PART_NAME]) > $parentMedia->{static::$PART_NAME."Count"}()))) {
      $validationErrors[] = static::$PART_NAME." number must be integral and at least 0, less than the ".static::$PART_NAME." count of its parent.";
    }

    if ($validationErrors) {
      throw new ValidationException($this->app, $entry, $validationErrors);
    }
    return True;
  }
  public function create_or_update(array $entry, array $whereConditions=Null) {
    /*
      Creates or updates an existing list entry for the current user.
      Takes an array of entry parameters.
      Returns the resultant list entry ID.
    */
    // validate this entry prior to doing anything else.
    $this->validate($entry);

    foreach ($entry as $parameter => $value) {
      if (!is_array($value)) {
        if (is_numeric($value)) {
          $entry[$parameter] = intval($value);
        }
      }
    }

    // check to see if this is an update.
    $this->app->dbConn->table(static::$MODEL_TABLE)->set($entry);
    if ($this->id != 0) {
      $this->beforeUpdate($entry);
      $this->app->dbConn->where(['id' => $entry['id']])->limit(1)->update();
      $returnValue = intval($entry['id']);
      $this->afterUpdate();
    } else {
      $this->beforeCreate($entry);
      if (!isset($entry['time'])) {
        $this->app->dbConn->set(['time=NOW()']);
      }
      $this->app->dbConn->insert();
      $returnValue = intval($this->app->dbConn->lastInsertId);
      $entry['id'] = $returnValue;
      $this->afterCreate($entry);
    }
    return $returnValue;
  }

  // all feed entry classes must implement a way to format said feed entries into markup.
  abstract public function formatFeedEntry();
}

?>