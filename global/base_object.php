<?php
class BaseObject {
  public $dbConn;
  public $id;

  protected $modelTable;
  protected $modelPlural;
  protected $className;

  public function __construct($database, $id=Null) {
    $this->dbConn = $database;
    $this->id = intval($id);
    $this->className = get_class($this);
  }
  public function __get($property) {
    // A property accessor exists
    if (method_exists($this, $property)) {
      return $this->$property();
    } elseif (property_exists($this, $property)) {
      return $this->$property;
    }
  }
  private function humanizeParameter($parameter) {
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
  public function create_or_update($object, $currentUser=Null) {
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
    //go ahead and create or update this object.
    if ($this->id != 0) {
      //update this object.
      $updateObject = $this->dbConn->stdQuery("UPDATE `".$this->modelTable."` SET ".implode(", ", $params).", `updated_at` = NOW() WHERE ".$whereClause."`id` = ".intval($this->id)." LIMIT 1");
      if (!$updateObject) {
        return False;
      }
    } else {
      // add this object.
      $insertUser = $this->dbConn->stdQuery("INSERT INTO `".$this->modelTable."` SET ".implode(",", $params).", `created_at` = NOW(), `updated_at` = NOW()");
      if (!$insertUser) {
        return False;
      } else {
        $this->id = intval($this->dbConn->insert_id);
      }
    }
    return $this->id;
  }
  public function delete($entries=False) {
    /*
      Deletes objects from the database.
      Takes an array of objects IDs as the input, defaulting to just this object.
      Returns a boolean.
    */
    if ($entries === False) {
      $entries = intval($this->id);
    }
    if (is_numeric($entries)) {
      $entries = [intval($entries)];
    }
    $entryIDs = array();
    foreach ($entries as $entry) {
      if (is_numeric($entry)) {
        $entryIDs[] = intval($entry);
      }
    }
    if (count($entryIDs) > 0) {
      $dropEntries = $this->dbConn->stdQuery("DELETE FROM `".$this->modelTable."` WHERE `id` IN (".implode(",", $entryIDs).") LIMIT ".count($entryIDs));
      if (!$dropEntries) {
        return False;
      }
    }
    return True;
  }

 }
?>