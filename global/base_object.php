<?php
abstract class BaseObject {
  // base class for database objects.

  public $dbConn;
  public $id;

  protected $modelTable, $modelPlural, $modelName;
  protected $createdAt, $updatedAt;
  protected $_observers = array();

  public function __construct(DbConn $database, $id=Null) {
    $this->dbConn = $database;
    $this->id = intval($id);
    $this->modelName = $this->modelPlural = $this->modelTable = Null;
    if ($id === 0) {
      $this->createdAt = $this->updatedAt = "";
    } else {
      $this->createdAt = $this->updatedAt = Null;
    }
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
      if (DEBUG_ON) {
        throw new Exception($this->modelName().' ID Not Found: '.$this->id);
      } else {
        return;
      }
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
  public function createdAt() {
    if ($this->createdAt === Null) {
      $this->createdAt = new DateTime($this->returnInfo('createdAt'), new DateTimeZone(SERVER_TIMEZONE));
    }
    return $this->createdAt;
  }
  public function updatedAt() {
    if ($this->updatedAt === Null) {
      $this->updatedAt = new DateTime($this->returnInfo('updatedAt'), new DateTimeZone(SERVER_TIMEZONE));
    }
    return $this->updatedAt;
  }

  // all classes must implement allow(), which defines user permissions.
  abstract public function allow(User $authingUser, $action, array $params=Null);

  // also should implement validate(), which takes an array of parameters and ensures that they are valid. returns a bool.
  public function validate(array $object) {
    if (isset($object['id']) && ( !is_numeric($object['id']) || intval($object['id']) != $object['id'] || intval($object['id']) < 0) ) {
      return False;
    }
    if (isset($object['created_at']) && !strtotime($object['created_at'])) {
      return False;
    }
    if (isset($object['updated_at']) && !strtotime($object['updated_at'])) {
      return False;
    }
    return True;
  }

  // event handlers for objects.
  public function bind($event, $observer) {
    // binds a function to an event.
    // can be either anonymous function or string name of class method.
    if (!method_exists($observer, 'update')) {
      if (DEBUG_ON) {
        throw new InvalidArgumentException(sprintf('Invalid observer: %s.', print_r($observer, True)));
      } else {
        return False;
      }
    }
    if (!isset($this->_observers[$event])) {
      $this->_observers[$event] = array($observer);
    } else {
      $elements = array_keys($this->_observers[$event], $o);
      $notinarray = True;
      foreach ($elements as $value) {
        if ($observer === $this->_observers[$event][$value]) {
            $notinarray = False;
            break;
          }
        }
      //check if there already
      if ($notinarray) {
        $this->_observers[$event][] = $observer;
      }
    }
    return array($event, count($this->_observers[$event])-1);
  }
  public function unbind($observer) {
    // callback is array of form [event_name, position]
    // alternatively, also accepts a string for event name.
    if (is_array($observer)) {
      if (count($observer) < 2) {
        return False;
      } elseif (!isset($this->_observers[$observer[0]])) {
        return True;
      }
      unset($this->_observers[$observer[0]][$observer[1]]);
      return !isset($this->_observers[$observer[0]][$observer[1]]);
    } else {
      if (!isset($this->_observers[$observer])) {
        return True;
      }
      unset($this->_observers[$observer]);
      return !isset($this->_observers[$observer]);
    }
  }
  public function fire($event, $updateParams=Null) {
    if (!isset($this->_observers[$event])) {
      return;
    }
    foreach ($this->_observers[$event] as $observer) {
      if (!method_exists($observer, 'update')) {
        continue;
      }
      $observer->update($event, $this, $updateParams);
    }
  }
  public function before_create() {
    $this->fire('beforeCreate');
  }
  public function after_create() {
    $this->fire('afterCreate');
  }
  public function before_update($updateParams=Null) {
    $this->fire('beforeUpdate', $updateParams);
  }
  public function after_update($updateParams=Null) {
    $this->fire('afterUpdate', $updateParams);
  }
  public function before_delete() {
    $this->fire('beforeDelete');
  }
  public function after_delete() {
    $this->fire('afterDelete');
  }
  public function create_or_update(array $object, array $whereConditions=Null) {
    // creates or updates a object based on the parameters passed in $object and this object's attributes.
    // assumes the existence of updated_at and created_at fields in the database.
    // returns False if failure, or the ID of the object if success.
    if (!$this->validate($object)) {
      return False;
    }
    $params = array();
    foreach ($object as $parameter => $value) {
      if (!is_array($value)) {
        $params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
      }
    }
    $params[] = '`updated_at` = NOW()';

    //go ahead and create or update this object.
    if ($this->id != 0) {
      $whereParams = array();
      if ($whereConditions !== Null && is_array($whereConditions)) {
        foreach ($whereConditions as $parameter => $value) {
          if (!is_array($value)) {
            $whereParams[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
          }
        }
      }
      $whereParams[] = "`id` = ".intval($this->id);

      //update this object.
      $this->before_update($object);
      $updateObject = $this->dbConn->stdQuery("UPDATE `".$this->modelTable."` SET ".implode(", ", $params)." WHERE ".implode(", ", $whereParams)." LIMIT 1");
      if (!$updateObject) {
        return False;
      }
      $this->after_update($object);
    } else {
      // add this object.
      $params[] = '`created_at` = NOW()';

      $this->before_create();
      $insertUser = $this->dbConn->stdQuery("INSERT INTO `".$this->modelTable."` SET ".implode(",", $params));
      if (!$insertUser) {
        return False;
      } else {
        $this->id = intval($this->dbConn->insert_id);
      }
      $this->after_create();
    }
    return $this->id;
  }
  public function delete($entries=Null) {
    /*
      Deletes objects from the database.
      Takes an array of objects IDs as the input, defaulting to just this object.
      Returns a boolean.
    */
    if ($entries === Null) {
      $entries = [intval($this->id)];
    }
    if (!is_array($entries) && !is_numeric($entries)) {
      return False;
    }
    if (is_numeric($entries)) {
      $entries = [$entries];
    }
    $entryIDs = [];
    foreach ($entries as $entry) {
      if (is_numeric($entry)) {
        $entryIDs[] = intval($entry);
      }
    }
    $this->before_delete();
    if ($entryIDs) {
      $dropEntries = $this->dbConn->stdQuery("DELETE FROM `".$this->modelTable."` WHERE `id` IN (".implode(",", $entryIDs).") LIMIT ".count($entryIDs));
      if (!$dropEntries) {
        return False;
      }
    }
    $this->after_delete();
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
    if (is_array($params) && $params) {
      foreach ($params as $key => $value) {
        $linkParams[] = escape_output($key)."='".escape_output($value)."'";
      }
    }
    return "<a href='".$this->url($action, $urlParams, $id)."' ".implode(" ", $linkParams).">".($raw ? $text : escape_output($text))."</a>";
  }

 }
?>