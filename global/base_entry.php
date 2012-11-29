<?php

class BaseEntry extends BaseObject {
  protected $user, $userId;
  protected $time;
  protected $status, $score;

  public function __construct(DbConn $database, $id=Null, $params=Null) {
    parent::__construct($database, $id);
    if ($id === 0) {
      $serverTimezone = new DateTimeZone(SERVER_TIMEZONE);
      $this->time = new DateTime("now", $serverTimezone);
      $this->status = $this->score = 0;   
    } else {
      $this->user = $this->userId = $this->time = $this->status = $this->score = Null;
    }
    $this->modelTable =  $this->modelPlural = $this->partName = $this->entryType = $this->typeVerb = $this->feedType = $this->entryTypeLower = $this->typeID = "";
    if (is_array($params)) {
      foreach ($params as $key=>$value) {
        $this->{$this->humanizeParameter($key)} = $value;
      }
    }
  }
  public function userId() {
    return $this->returnInfo('userId');
  }
  public function user() {
    if ($this->user === Null) {
      $this->user = new User($this->dbConn, $this->userId());
    }
    return $this->user;
  }
  public function time() {
    $serverTimezone = new DateTimeZone(SERVER_TIMEZONE);
    return new DateTime($this->returnInfo('time'), $serverTimezone);
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
      case 'edit':
      case 'delete':
        if ($authingUser->id == $this->userId() || ($authingUser->isModerator() || $authingUser->isAdmin()) ) {
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
  public function create_or_update(array $entry, User $currentUser=Null) {
    /*
      Creates or updates an existing list entry for the current user.
      Takes an array of entry parameters.
      Returns the resultant list entry ID.
    */
    // ensure that this user and list type exist.
    try {
      $user = new User($this->dbConn, intval($entry['user_id']));
      $type = new $this->entryType($this->dbConn, intval($entry[$this->typeID]));
    } catch (Exception $e) {
      return False;
    }
    $params = [];
    foreach ($entry as $parameter => $value) {
      if (!is_array($value)) {
        if (is_numeric($value)) {
            $params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".intval($value);
        } else {
          $params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
        }
      }
    }

    // check to see if this is an update.
    if (isset($this->entries()[intval($entry['id'])])) {
      $updateDependency = $this->dbConn->stdQuery("UPDATE `".$this->modelTable."` SET ".implode(", ", $params)." WHERE `id` = ".intval($entry['id'])." LIMIT 1");
      if (!$updateDependency) {
        return False;
      }
      $returnValue = intval($entry['id']);
    } else {
      if (!isset($entry['time'])) {
        $params[] = "`time` = NOW()";
      }
      $insertDependency = $this->dbConn->stdQuery("INSERT INTO `".$this->modelTable."` SET ".implode(",", $params));
      if (!$insertDependency) {
        return False;
      }
      $returnValue = intval($this->dbConn->insert_id);
    }
    return $returnValue;
  }
  public function delete(array $entries=Null) {
    /*
      Deletes list entries.
      Takes an array of entry ids as input, defaulting to just this entry.
      Returns a boolean.
    */
    if ($entries === Null) {
      $entries = array_keys($this->entries());
    }
    if (is_numeric($entries)) {
      $entries = [intval($entries)];
    }
    $entryIDs = [];
    foreach ($entries as $entry) {
      if (is_numeric($entry)) {
        $entryIDs[] = intval($entry);
      }
    }
    if ($entryIDs) {
      $drop_entries = $this->dbConn->stdQuery("DELETE FROM `".$this->modelTable."` WHERE `id` IN (".implode(",", $entryIDs).") LIMIT ".count($entryIDs));
      if (!$drop_entries) {
        return False;
      }
    }
    return True;
  }
}

?>