<?php
abstract class BaseObject {
  // base class for database objects.

  public $app, $dbConn, $id=Null;
  public $modelTable, $modelUrl, $modelPlural, $modelName;

  protected $createdAt, $updatedAt;
  protected $observers = array();

  public function __construct(Application $app, $id=Null) {
    $this->app = $app;
    $this->dbConn = $app->dbConn;
    $this->id = intval($id);
    $this->modelName = $this->modelPlural = $this->modelTable = $this->modelUrl = Null;
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
  public function modelUrl() {
    if ($this->modelUrl === Null) {
      $this->modelUrl = $this->modelTable;
    }
    return $this->modelUrl;
  }
  public function humanizeParameter($parameter) {
    // takes a parameter name like created_at
    // returns a human-friendly name like createdAt
    $paramParts = explode("_", $parameter);
    $newName = $paramParts[0];
    foreach (array_slice($paramParts, 1) as $part) {
      $newName .= ucfirst($part);
    }
    return $newName;
  }
  public function set(array $params) {
    // generic setter. humanizes parameter names.
    foreach ($params as $key=>$value) {
      if (is_numeric($value)) {
        $value = ( (int) $value == $value ? (int) $value : (float) $value);
      }
      if (($key == 'created_at' || $key == 'updated_at') && !($value instanceof DateTime)) {
        $value = new DateTime($value, new DateTimeZone(Config::SERVER_TIMEZONE));
      }
      $this->{$this->humanizeParameter($key)} = $value;
    }
    return $this;
  }
  public function getInfo() {
    // retrieves (from the database) all properties of this object in the object's table.
    try {
      $info = $this->dbConn->queryFirstRow("SELECT * FROM `".$this->modelTable."` WHERE `id` = ".intval($this->id)." LIMIT 1");
    } catch (DbException $e) {
      throw new DbException($this->modelName().' ID not found: '.$this->id);
    }
    $this->set($info);
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
      $this->createdAt = $this->returnInfo('createdAt');
    }
    return $this->createdAt;
  }
  public function updatedAt() {
    if ($this->updatedAt === Null) {
      $this->updatedAt = $this->returnInfo('updatedAt');
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
  // event names are of the form modelName.eventName
  // e.g. User.afterCreate
  public function before_create() {
    $this->app->fire($this->modelName().'.beforeCreate', $this);
  }
  public function after_create() {
    $this->app->fire($this->modelName().'.afterCreate', $this);
  }
  public function before_update($updateParams=Null) {
    $this->app->fire($this->modelName().'.beforeUpdate', $this, $updateParams);
  }
  public function after_update($updateParams=Null) {
    $this->app->fire($this->modelName().'.afterUpdate', $this, $updateParams);
  }
  public function before_delete() {
    $this->app->fire($this->modelName().'.beforeDelete', $this);
  }
  public function after_delete() {
    $this->app->fire($this->modelName().'.afterDelete', $this);
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
  public function view($view="index", array $params=Null) {
    $file = joinPaths(Config::APP_ROOT, 'views', $this->modelTable, "$view.php");
    if (file_exists($file)) {
      ob_start();
      include($file);
      return ob_get_clean();
    }
    return False;
  }
  public function render() {
    echo $this->app->render($this->view($this->app->action));
    exit;
  }
  public function url($action="show", $format=Null, array $params=Null, $id=Null) {
    // returns the url that maps to this object and the given action.
    if ($id === Null) {
      $id = intval($this->id);
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output($this->modelUrl())."/".($action !== "index" ? intval($id)."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
  public function link($action="show", $text="Show", $format=Null, $raw=False, array $params=Null, array $urlParams=Null, $id=Null) {
    // returns an HTML link to the current object's profile, with text provided.
    $linkParams = [];
    if (is_array($params) && $params) {
      foreach ($params as $key => $value) {
        $linkParams[] = escape_output($key)."='".escape_output($value)."'";
      }
    }
    return "<a href='".$this->url($action, $format, $urlParams, $id)."' ".implode(" ", $linkParams).">".($raw ? $text : escape_output($text))."</a>";
  }
  public function ajaxLink($action="show", $text="Show", $source=Null, $target=Null, $raw=False, array $params=Null, array $urlParams=Null, $id=Null) {
    if (!is_array($params)) {
      $params = [];
    }
    if ($source !== Null) {
      $params['data-url'] = $source;
    }
    if ($target !== Null) {
      $params['data-target'] = $target;
    }
    return $this->link($action, $text, Null, $raw, $params, $urlParams, $id);
  }

 }
?>