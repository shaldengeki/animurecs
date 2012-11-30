<?php
class BaseObject {
  public $dbConn;
  public $id;

  protected $modelTable, $modelPlural, $modelName;

  public function __construct(DbConn $database, $id=Null) {
    $this->dbConn = $database;
    $this->id = intval($id);
    $this->modelName = $this->modelPlural = $this->modelTable = Null;
  }
  public function __get($property) {
    // A property accessor exists
    if (method_exists($this, $property)) {
      return $this->$property();
    } elseif (property_exists($this, $property)) {
      return $this->$property;
    }
  }
  public function modelName() {
    if ($this->modelName === Null) {
      $this->modelName = get_class($this);
    }
    return $this->modelName;
  }
  protected function humanizeParameter($parameter) {
    // takes a parameter name like created_at
    // returns a human-friendly name like createdAt
    $paramParts = explode("_", $parameter);
    $newName = $paramParts[0];
    foreach (array_slice($paramParts, 1) as $part) {
      $newName .= ucfirst($part);
    }
    return $newName;
  }
  public function getInfo() {
    // retrieves (from the database) all properties of this object in the object's table.
    $info = $this->dbConn->queryFirstRow("SELECT * FROM `".$this->modelTable."` WHERE `id` = ".intval($this->id)." LIMIT 1");
    if (!$info) {
      throw new Exception('ID Not Found');
    }
    foreach ($info as $key=>$value) {
      if (is_numeric($value)) {
        $value = ( (int) $value == $value ? (int) $value : (float) $value);
      }
      $this->{$this->humanizeParameter($key)} = $value;
    }
  }
  public function returnInfo($param) {
    // sets object property if not set, then returns requested property.
    if ($this->$param === Null) {
      $this->getInfo();
    }
    return $this->$param;
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // by default, don't let anybody do anything unless they're staff, in which case allow them to do everything.
    if ($authingUser->isModerator() || $authingUser->isAdmin()) {
      return True;
    }
    return False;
  }
  public function create_or_update(array $object, User $currentUser=Null) {
    // creates or updates a object based on the parameters passed in $object and this object's attributes.
    // assumes the existence of updated_at and created_at fields in the database.
    // returns False if failure, or the ID of the object if success.
    $params = array();
    foreach ($object as $parameter => $value) {
      if (!is_array($value)) {
        $params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
      }
    }
    if ($currentUser !== Null) {
      $whereClause = "`user_id` = ".intval($currentUser->id)." && ";
    }
    $params[] = '`updated_at` = NOW()';
    //go ahead and create or update this object.
    if ($this->id != 0) {
      //update this object.
      $updateObject = $this->dbConn->stdQuery("UPDATE `".$this->modelTable."` SET ".implode(", ", $params)." WHERE ".$whereClause."`id` = ".intval($this->id)." LIMIT 1");
      if (!$updateObject) {
        return False;
      }
    } else {
      // add this object.
      $params[] = '`created_at` = NOW()';
      $insertUser = $this->dbConn->stdQuery("INSERT INTO `".$this->modelTable."` SET ".implode(",", $params));
      if (!$insertUser) {
        return False;
      } else {
        $this->id = intval($this->dbConn->insert_id);
      }
    }
    return $this->id;
  }
  public function delete(array $entries=Null) {
    /*
      Deletes objects from the database.
      Takes an array of objects IDs as the input, defaulting to just this object.
      Returns a boolean.
    */
    if ($entries === Null) {
      $entries = [intval($this->id)];
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
      $dropEntries = $this->dbConn->stdQuery("DELETE FROM `".$this->modelTable."` WHERE `id` IN (".implode(",", $entryIDs).") LIMIT ".count($entryIDs));
      if (!$dropEntries) {
        return False;
      }
    }
    return True;
  }
  public function url($action="show", array $params=Null, $id=Null) {
    // returns the url that maps to this object and the given action.
    if ($id === Null) {
      $id = intval($this->id);
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output($this->modelTable)."/".($action !== "index" ? intval($id)."/".escape_output($action)."/" : "").($params !== Null ? "?".$urlParams : "");
  }
  public function link($action="show", $text="Show", $raw=False, array $params=Null, array $urlParams=Null, $id=Null) {
    // returns an HTML link to the current object's profile, with text provided.
    $linkParams = [];
    if (is_array($params)) {
      foreach ($params as $key => $value) {
        $linkParams[] = escape_output($key)."='".escape_output($value)."'";
      }
    }
    return "<a href='".$this->url($action, $urlParams, $id)."' ".implode(" ", $linkParams).">".($raw ? $text : escape_output($text))."</a>";
  }

 }
?>