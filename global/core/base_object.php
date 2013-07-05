<?php

class ValidationException extends AppException {
  private $params;
  public function __construct($params, $app, $messages=Null, $code=0, AppException $previous=Null) {
    parent::__construct($app, $messages, $code, $previous);
    $this->params = $params;
  }
  public function __toString() {
    return "ValidationException:\n".$this->getFile().":".$this->getLine()."\nParams: ".print_r($this->params, TRUE)."Messages: ".$this->formatMessages()."\nStack trace:\n".$this->getTraceAsString()."\n";
  }
  public function display() {
    return "<p class='error'>One or more fields you entered in this form was incorrect:".$this->listMessages()."Please correct this and try again!</p>";
  }
}

abstract class BaseObject {
  // base class for database objects.

  public $app, $dbConn, $id=Null;

  public static $MODEL_TABLE, $MODEL_URL, $MODEL_PLURAL, $MODEL_NAME;

  protected $createdAt, $updatedAt;
  protected $observers = [];

  public function __construct(Application $app, $id=Null) {
    $this->app = $app;
    $this->dbConn = $app->dbConn;
    $this->id = intval($id);
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
    } else {
      throw new AppException($this->app, "Requested attribute does not exist: ".$property." on: ".static::MODEL_NAME());
    }
  }
  public static function MODEL_NAME() {
    return static::$MODEL_NAME === Null ? get_called_class() : static::$MODEL_NAME;
  }
  public static function MODEL_URL() {
    if (static::$MODEL_URL !== Null) {
      return static::$MODEL_URL;
    } else {
      return static::$MODEL_TABLE;
    }
  }
  public static function first($app) {
    $className = static::MODEL_NAME();
    return new $className($app, intval($app->dbConn->table(static::$MODEL_TABLE)->fields('id')->order('id ASC')->limit(1)->firstValue()));
  }
  public static function count($app, $params=Null) {
    $params = $params ? $params : [];
    return intval($app->dbConn->table(static::$MODEL_TABLE)->fields("COUNT(*)")->where($params)->count());
  }
  public static function find($app, $params=Null) {
    if ($params === Null) {
      return static::first($app);
    }
    $className = static::MODEL_NAME();
    $findID = $app->dbConn->table(static::$MODEL_TABLE)->fields('id')->where($params)->limit(1)->firstValue();
    return new $className($app, intval($findID));
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
      if (($key == 'created_at' || $key == 'updated_at' || $key == 'approved_on') && !($value instanceof DateTime)) {
        $value = new DateTime($value, $this->app->serverTimeZone);
      }
      $this->{$this->humanizeParameter($key)} = $value;
    }
    return $this;
  }
  public function getInfo() {
    // retrieves (from the cache or database) all direct properties of this object (not lists of other objects).
    if ($this->id === Null) {
      // should never reach here!
      throw new DbException(static::MODEL_NAME().' with null ID not found in database');
    }
    $cacheKey = static::MODEL_NAME()."-".intval($this->id);
    $cas = "";
    $info = $this->app->cache->get($cacheKey, $foo, $cas);
    if ($this->app->cache->resultCode() === Memcached::RES_NOTFOUND) {
      // key is not yet set in cache. fetch from DB and set it in cache.
      try {
        $info = $this->dbConn->table(static::$MODEL_TABLE)->where(['id' => $this->id])->limit(1)->firstRow();
      } catch (DbException $e) {
        throw new DbException(static::MODEL_NAME().' ID not found: '.$this->id);
      }
      // set cache for this object.
      $this->app->cache->set($cacheKey, $info);
    }
    $this->set($info);
    return True;
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
    $validationErrors = [];
    if (!$object) {
      $validationErrors[] = "Object must have some attributes set";
    }
    if (isset($object['id']) && ( !is_numeric($object['id']) || intval($object['id']) != $object['id'] || intval($object['id']) < 0) ) {
      $validationErrors[] = "Object ID must be an integer greater than 0";
    }
    if (isset($object['created_at']) && !strtotime($object['created_at'])) {
      $validationErrors[] = "Malformed created-at time";
    }
    if (isset($object['updated_at']) && !strtotime($object['updated_at'])) {
      $validationErrors[] = "Malformed updated-at time";
    }
    if ($validationErrors) {
      throw new ValidationException($object, $this->app, $validationErrors);
    } else {
      return True;
    }
  }

  protected function fireParentEvents($eventName, $params=Null) {
    $currentClass = new ReflectionClass($this);
    while ($currentClass = $currentClass->getParentClass()) {
      if ($params != Null) {
        $this->app->fire($currentClass->getName().'.'.$eventName, $this, $params);
      } else {
        $this->app->fire($currentClass->getName().'.'.$eventName, $this);
      }
    }
  }

  // event handlers for objects.
  // event names are of the form modelName.eventName
  // e.g. User.afterCreate
  // events cascade up the object hierarchy
  public function fire($event, $params=Null) {
    $this->app->fire(static::MODEL_NAME().'.'.$event, $this, $params);
    $this->fireParentEvents($event, $params);
  }
  // shorthand methods.
  public function beforeCreate($createParams) {
    $this->fire('beforeCreate', $createParams);
  }
  public function afterCreate($createParams) {
    $this->fire('afterCreate', $createParams);
  }
  public function beforeUpdate($updateParams) {
    $this->fire('beforeUpdate', $updateParams);
  }
  public function afterUpdate($updateParams) {
    $this->fire('afterUpdate', $updateParams);
  }
  public function beforeDelete() {
    $this->fire('beforeDelete');
  }
  public function afterDelete() {
    $this->fire('afterDelete');
  }

  public function create_or_update(array $object, array $whereConditions=Null) {
    // creates or updates a object based on the parameters passed in $object and this object's attributes.
    // assumes the existence of updated_at and created_at fields in the database.
    // returns False if failure, or the ID of the object if success.
    $this->validate($object);

    //go ahead and create or update this object.
    $this->dbConn->table(static::$MODEL_TABLE);
    if (!isset($object['updated_at'])) {
      $this->dbConn->set(['updated_at=NOW()']);
    }

    if ($this->id != 0) {
      $whereConditions['id'] = $this->id;
      //update this object.
      $this->beforeUpdate($object);
      $this->dbConn->set($object)->where($whereConditions === Null ? [] : $whereConditions)->limit(1);
      if (!$this->dbConn->update()) {
        throw new DbException("Could not update ".static::$MODEL_TABLE.": ".$this->dbConn->queryString());
      }
      $this->set($object);
      $modelName = static::MODEL_NAME();
      $newObject = new $modelName($this->app, $this->id);
      $newObject->afterUpdate($object);
    } else {
      // add this object.
      $this->dbConn->set(['created_at=NOW()']);
      $this->beforeCreate([$object]);
      if (!$this->dbConn->set($object)->insert()) {
        throw new DbException("Could not insert into ".static::$MODEL_TABLE.": ".$this->dbConn->queryString());
      } else {
        $this->id = intval($this->dbConn->lastInsertId);
      }
      $modelName = $this->MODEL_NAME();
      $newObject = new $modelName($this->app, $this->id);
      $newObject->afterCreate($object);
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
      throw new ValidationException($this, $this->app, "Invalid ID to delete");
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
    $this->beforeDelete();
    if ($entryIDs) {
      if (!$this->dbConn->table(static::$MODEL_TABLE)->where(['id' => $entryIDs])->limit(count($entryIDs))->delete()) {
        throw new DbException("Could not delete from ".static::$MODEL_TABLE.": ".$deleteQuery);
      }
    }
    $this->afterDelete();
    return True;
  }
  public function view($view="index", array $params=Null) {
    $file = joinPaths(Config::APP_ROOT, 'views', static::MODEL_URL(), "$view.php");
    if (file_exists($file)) {
      ob_start();
      include($file);
      return ob_get_clean();
    }
    // Should never get here!
    throw new AppException($this->app, "Requested view not found: ".$file);
  }
  public function render() {
    return $this->app->render($this->view($this->app->action));
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
    return "/".rawurlencode(self::MODEL_URL())."/".($action !== "index" ? rawurlencode($id)."/".rawurlencode($action) : "").($format !== Null ? ".".rawurlencode($format) : "").($params !== Null ? "?".$urlParams : "");
  }
  public function link($action="show", $text="Show", $format=Null, $raw=False, array $params=Null, array $urlParams=Null, $id=Null) {
    // returns an HTML link to the current object's profile, with text provided.
    $linkParams = [];
    if ($action == "delete") {
      $urlParams['csrf_token'] = $this->app->csrfToken;
    }
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
  public function input($attr, array $params=Null) {
    if ($params === Null) {
      $params = [];
    }
    $defaultVals = ['name' => escape_output(self::MODEL_URL())."[".escape_output($attr)."]"];
    $defaultVals['id'] = $defaultVals['name'];
    $humanizedAttr = $this->humanizeParameter($attr);
    try {
      if (method_exists($this, $humanizedAttr) && $this->$humanizedAttr()) {
        $defaultVals['value'] = $this->$humanizedAttr();
      } elseif (property_exists($this, $humanizedAttr) && $this->$humanizedAttr) {
        $defaultVals['value'] = $this->$humanizedAttr;
      }
    } catch (DbException $e) {
      $defaultVals['value'] = '';
    }
    $params = array_merge($defaultVals, $params);
    return $this->app->input($params);
  }
  public function textarea($attr, array $params=Null, $textValue=Null) {
    if ($params === Null) {
      $params = [];
    }
    $defaultVals = ['name' => escape_output(self::MODEL_URL())."[".escape_output($attr)."]"];
    $defaultVals['id'] = $defaultVals['name'];
    $humanizedAttr = $this->humanizeParameter($attr);
    if (method_exists($this, $humanizedAttr) && $this->$humanizedAttr()) {
      $defaultVals['value'] = $this->$humanizedAttr();
    } elseif (property_exists($this, $humanizedAttr) && $this->$humanizedAttr) {
      $defaultVals['value'] = $this->$humanizedAttr;
    }
    $params = array_merge($defaultVals, $params);
    return $this->app->textarea($params, $textValue);
  }
  public function image($path, array $params=Null) {
    $imageParams = [];
    if ($params) {
      foreach ($params as $key => $value) {
        $imageParams[] = escape_output($key)."='".escape_output($value)."'";
      }
    }
    return "<img src='".joinPaths(Config::ROOT_URL, escape_output($path))."' ".implode(" ", $imageParams)." />";
  }

 }
?>