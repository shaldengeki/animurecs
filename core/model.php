<?php

class ModelException extends AppException { }

class InvalidParameterException extends AppException {
  private $args, $expected;
  public function __construct($app, array $args, $expected, $messages=Null, $code=0, AppException $previous=Null) {
    parent::__construct($app, $messages, $code, $previous);
    $this->args = $args;
    $this->expected = $expected;
  }
  public function __toString() {
    return "InvalidParameterException:\n".$this->getFile().":".$this->getLine()."\nParams: ".print_r($this->args, True)."Expected: ".print_r($this->expected, True)."Messages: ".$this->formatMessages()."\nStack trace:\n".$this->getTraceAsString()."\n";
  }
}

class ValidationException extends AppException {
  private $params;
  public function __construct($app, array $params, $messages=Null, $code=0, AppException $previous=Null) {
    parent::__construct($app, $messages, $code, $previous);
    $this->params = $params;
  }
  public function __toString() {
    return "ValidationException:\n".$this->getFile().":".$this->getLine()."\nParams: ".print_r($this->params, True)."Messages: ".$this->formatMessages()."\nStack trace:\n".$this->getTraceAsString()."\n";
  }
  public function display() {
    return "One or more fields you entered in this form was incorrect:".$this->listMessages()."Please correct this and try again!";
  }
}

abstract class Model {
  // base class for database objects.

  public $app, $id=Null;

  /*
    TABLE:  name of database table that this object maps to.
    PLURAL: capitalised pluralised name of this class.
    FIELDS: mapping from object attributes to database fields
            of the form:
            [ 
              'object_attribute_name' => [
                'type' => 'attribute_type',
                'serialize' => True,
                'db' => 'db_column_name'
              ], ...
            ]
            upon construction, an object has a corresponding attribute DB_FIELDS set that reverses object_attribute_name and db_column_name.
            [ 
              'db_column_name' => [
                'type' => 'attribute_type',
                'serialize' => True,
                'attr' => 'object_attribute_name'
              ], ...
            ]
            'serialize' determines whether or not the field is exposed in the serialize() method. No value is interpreted as True.
    JOINS: [
      'join_name' => [
        'obj' => '\\full\\namespace\\path\\to\\object OR {polymorphic_db_field_name}',
        'table' => 'table_name',
        'own_col' => 'own_col_name',
        'join_col' => 'join_col_name',
        'type' => 'one|many|habtm',
        // optional
        'condition' => 'additional condition e.g. type="Anime"'

        // Only if HABTM:
        'join_table' => 'join_table_name',
        'join_table_own_col' => 'name_of_own_col_in_join_table',
        'join_table_join_col' => 'name_of_join_col_in_join_table',
        'join_table_condition' => 'additional condition on join table' (optional)
      ]
    ]
  */


  public static $TABLE, $URL, $PLURAL, $MODEL_NAME, $FIELDS, $JOINS;

  public $createdAt, $updatedAt;
  protected $observers = [];

  public function __construct(Application $app, $id=Null) {
    $this->app = $app;
    $this->id = intval($id);
  }
  public static function DB_FIELD($field) {
    // takes a nice property-name and returns the corresponding db column name.
    if (!isset(static::$FIELDS[$field])) {
      throw new ModelException($this->app, "Unknown field: ".$field);
    }
    return static::$FIELDS[$field]['db'];
  }
  public static function DB_FIELDS() {
    // inverts db_column_name and object_attribute_name in static::$FIELDS
    $invertedFields = [];
    foreach (static::$FIELDS as $attr_name => $attr_props) {
      $invertedFields[$attr_props['db']] = [
        'type' => $attr_props['type'],
        'serialize' => isset($attr_props['serialize']) ? boolval($attr_props['serialize']) : True,
        'attr' => $attr_name
      ];
    }
    return $invertedFields;
  }
  public static function FULL_TABLE_NAME() {
    return Config::DB_NAME.'.'.static::$TABLE;
  }
  public static function FULL_DB_FIELD_NAME($field) {
    // takes a nice property-name and returns the corresponding db.table.column_name
    return static::$TABLE.'.'.static::DB_FIELD($field);
  }
  public static function JOIN_FIELD($field) {
    // takes a nice property-name and returns the corresponding db.table_name__column_name to be used in joins.
    return static::$TABLE.'__'.static::DB_FIELD($field);
  }
  public static function JOIN_FIELDS() {
    // returns a dict of join_field_name => db_field_names.
    $joinFields = [];
    foreach (array_keys(static::$FIELDS) as $field) {
      $joinFields[static::JOIN_FIELD($field)] = static::DB_FIELD($field);
    }
    return $joinFields;
  }
  public static function JOIN_TO_DB_FIELD($join_field) {
    // takes a join field named by static::JOIN_FIELD and returns the proper DB field name.
    foreach (array_keys(static::$FIELDS) as $field) {
      if ($join_field === static::JOIN_FIELD($field)) {
        return static::DB_FIELD($field);
      }
    }
    return False;
  }

  public static function MODEL_NAME() {
    return static::$MODEL_NAME === Null ? get_called_class() : static::$MODEL_NAME;
  }
  public static function MODEL_URL() {
    if (static::$URL !== Null) {
      return static::$URL;
    } else {
      return static::$TABLE;
    }
  }

  public static function Get(\Application $app, $params=Null) {
    if ($params === Null) {
      $params = [];
    }
    $className = static::MODEL_NAME();
    $newObj = Null;
    if (isset($params['id'])) {
      $cacheKey = static::GenerateCacheKeyFromID($params['id']);
      $cacheValue = $app->cache->get($cacheKey, $casToken);
      if ($cacheValue) {
        $newObj = new $className($app, intval($params['id']));
        $newObj->set($cacheValue);
      }
    }
    if ($newObj === Null) {
      $objInfo = $app->dbConn->table(static::$TABLE)
                  ->where($params)
                  ->limit(1)
                  ->firstRow();
      $className = static::MODEL_NAME();
      $newObj = new $className($app, $objInfo[static::$FIELDS['id']['db']]);
      $newObj->set($objInfo);

      if (isset($params['id'])) {
        // cache this entry.
        $app->cache->set($cacheKey, $objInfo);
      }
    }
    return $newObj;
  }
  public static function GetList(\Application $app, $params=Null, $limit=Null) {
    if ($params === Null) {
      $params = [];
    }
    $objs = [];
    $className = static::MODEL_NAME();
    $objQuery = $app->dbConn->table(static::$TABLE)
                  ->where($params)
                  ->order(static::$FIELDS['id']['db']." ASC")
                  ->limit($limit)
                  ->query();
    while ($dbObj = $objQuery->fetch()) {
      try {
        $newObj = new $className($app, $dbObj[static::$FIELDS['id']['db']]);
      } catch (DatabaseException $e) {
        continue;
      }
      $objs[$dbObj[static::$FIELDS['id']['db']]] = $newObj->set($dbObj);
    }
    return $objs;
  }
  public static function Count(\Application $app, $params=Null) {
    if ($params === Null) {
      $params = [];
    }
    return intval($app->dbConn->table(static::$TABLE)
                  ->fields("COUNT(*)")
                  ->where($params)
                  ->count());
  }
  public static function FindById(\Application $app, $id) {
    return static::Get($app, ['id' => $id]);
  }
  public static function FindByIds(\Application $app, array $ids) {
    $className = static::MODEL_NAME();
    $cacheKeys = array_map(function ($id) {
      return static::GenerateCacheKeyFromID($id);
    }, $ids);
    $casTokens = [];
    $cacheValues = $app->cache->get($cacheKeys, $casTokens);
    $returnObjects = [];
    if ($cacheValues) {
      foreach ($cacheValues as $cacheKey=>$cacheValue) {
        if ($cacheValue) {
          $objectID = intval(explode("-", $cacheKey)[1]);
          $returnObj = new $className($app, $objectID);
          $returnObjects[$objectID] = $returnObj->set($cacheValue);
        }
      }
    }
    foreach ($ids as $id) {
      if (!isset($returnObjects[$id])) {
        $returnObjects[$id] = static::Get($app, ['id' => $id]);
      }
    }
    return $returnObjects;
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
  public function cast($value, $type) {
    /*
      casts a given value to a type specified by $type.
    */
    switch ($type) {
      case 'int':
        return (int) $value;
        break;
      case 'float':
        return (float) $value;
        break;
      case 'bool':
        return (boolean) $value;
        break;
      case 'timestamp':
        return new \DateTime('@'.intval($value));
        break;
      case 'date':
        return new \DateTime($value, $this->app->serverTimeZone);
        break;
      case 'str':
      default:
        return $value;
        break;
    }
  }
  public function set(array $params) {
    /* 
      generic setter. Takes an array of params like:
        [
          'db_column_name' => attr_value,
          ...
        ]
      and sets this object's attributes properly.
    */
    $DB_FIELDS = static::DB_FIELDS();
    foreach ($params as $key => $value) {
      if (isset($DB_FIELDS[$key])) {
        $this->{$DB_FIELDS[$key]['attr']} = $this->cast($value, $DB_FIELDS[$key]['type']);
      }
    }
    return $this;
  }
  public static function GenerateCacheKeyFromID($id, $extras=Null) {
    $parts = [static::MODEL_NAME(), $id];
    if ($extras) {
      $parts = array_merge($parts, $extras);
    }
    return implode("-", $parts);
  }
  public function cacheKey($extras=Null) {
    return static::GenerateCacheKeyFromID($this->id, $extras);
  }
  public function load() {
    if ($this->id === Null) {
      // should never reach here!
      $this->app->dbConn->reset();
      throw new DatabaseException(static::MODEL_NAME().' with null ID not found in database');
    }
    // include all fields.
    foreach (array_keys(static::$FIELDS) as $field) {
      $this->app->dbConn->fields(static::FULL_DB_FIELD_NAME($field));
    }

    $includes = func_get_args();
    if ($includes) {
      foreach ($includes as $include) {
        if (isset(static::$JOINS[$include])) {
          $thisJoin = static::$JOINS[$include];
          $className = $thisJoin['obj'];
          foreach (array_keys($className::$FIELDS) AS $field) {
            $this->app->dbConn->fields($className::FULL_DB_FIELD_NAME($field).' AS '.$className::JOIN_FIELD($field));
          }

          switch ($thisJoin['type']) {
            case 'many':
              $this->{$include} = [];
              $this->app->dbConn->join($thisJoin['table']." ON ".static::$TABLE.".".$thisJoin['own_col']."=".$thisJoin['table'].".".$thisJoin['join_col'].( isset($thisJoin['condition']) ? " AND ".$thisJoin['condition'] : "" ));
              break;
            case 'one':
              $this->{$include} = Null;
              $this->app->dbConn->join($thisJoin['table']." ON ".static::$TABLE.".".$thisJoin['own_col']."=".$thisJoin['table'].".".$thisJoin['join_col'].( isset($thisJoin['condition']) ? " AND ".$thisJoin['condition'] : "" ));
              break;
            case 'habtm':
              $this->{$include} = [];
              $this->app->dbConn->join($thisJoin['join_table']." ON ".static::$TABLE.".".$thisJoin['own_col']."=".$thisJoin['join_table'].".".$thisJoin['join_table_own_col'].( isset($thisJoin['condition']) ? " AND ".$thisJoin['condition'] : "" ));
              $this->app->dbConn->join($thisJoin['table']." ON ".$thisJoin['join_table'].".".$thisJoin['join_table_join_col']."=".$thisJoin['table'].".".$thisJoin['join_col'].( isset($thisJoin['join_table_condition']) ? " AND ".$thisJoin['join_table_condition'] : "" ));
              break;
            default:
              $this->app->dbConn->reset();
              throw new ModelException($this->app, "Invalid join type: ".$thisJoin['type']);
              break;
          }
        }
      }
    } else {
      // attempt to retrieve this object from the cache.
      $cas = "";
      $info = $this->app->cache->get($this->cacheKey(), $foo, $cas);
      if ($this->app->cache->resultCode() !== Memcached::RES_NOTFOUND) {
        $this->set($info);
        $this->app->dbConn->reset();
        return $this;
      }
    }

    $this->app->dbConn->table(static::$TABLE);
    $this->app->dbConn->where([
                      static::$TABLE.".".static::$FIELDS['id']['db'] => $this->id
                     ]);
    if ($includes) {
      $rows = $this->app->dbConn->query();
      $infoSet = False;
      while ($row = $rows->fetch()) {
        if (!$infoSet) {
          $this->set($row);
          $infoSet = True;
        }
        foreach ($includes as $include) {
          if (isset(static::$JOINS[$include])) {
            // filter out just the columns belonging to this joined model so we can set them properly.
            $thisJoin = static::$JOINS[$include];
            $className = $thisJoin['obj'];
            $joinRow = [];
            $joinFields = $className::JOIN_FIELDS();
            foreach (array_keys($row) as $key) {
              if (isset($joinFields[$key])) {
                $joinRow[$joinFields[$key]] = $row[$key];
              }
            }
            $newObj = new $className($this->app, $joinRow[$className::$FIELDS['id']['db']]);
            switch ($thisJoin['type']) {
              case 'habtm':
              case 'many':
                $this->{$include}[$newObj->id] = $newObj->set($joinRow);
                break;
              case 'one':
              default:
                $this->{$include} = $newObj->set($joinRow);
                break;
            }
          }
        }
      }
      return $this;
    } else {
      $row = $this->app->dbConn->limit(1)
                      ->firstRow();
      $this->app->cache->set($this->cacheKey(), $row);
      return $this->set($row);
    }
  }
  public function getFields() {
    $fields = [];
    foreach (array_keys(static::$FIELDS) as $field) {
      if (isset($this->{$field})) {
        $fields[$field] = $this->{$field};
      }
    }
    return $fields;
  }

  public function prev() {
    // gets the model with the next-lowest id.
    $findRow = $this->app->dbConn->table(static::$TABLE)
                        ->where([static::$FIELDS['id']['db']."<".$this->id])
                        ->order(static::$FIELDS['id']['db']." DESC")
                        ->limit(1)
                        ->firstRow();
    $objClass = static::MODEL_NAME();
    $obj = new $objClass($this->app, $findRow[static::$FIELDS['id']['db']]);
    return $obj->set($findRow);
  }
  public function next() {
    // gets the model with the next-highest id.
    $findRow = $this->app->dbConn->table(static::$TABLE)
                        ->where([static::$FIELDS['id']['db'].">".$this->id])
                        ->order(static::$FIELDS['id']['db']." ASC")
                        ->limit(1)
                        ->firstRow();
    $objClass = static::MODEL_NAME();
    $obj = new $objClass($this->app, $findRow[$fields['id']['db']]);
    return $obj->set($findRow);
  }

  public function __get($property) {
    if (method_exists($this, $property)) {
      // A property accessor exists
      return $this->$property();
    } else {
      if (isset(static::$FIELDS[$property])) {
        // this is a property in the model's table.
        $this->load();
        if (!isset($this->{$property})) {
          throw new ModelException($this->app, "Requested attribute does not exist: ".$property." on: ".get_called_class());          
        }
        return $this->{$property};
      } elseif (isset(static::$JOINS[$property])) {
        // this is a join on the model.
        $this->load($property);
        if (!isset($this->{$property})) {
          throw new ModelException($this->app, "Requested attribute does not exist: ".$property." on: ".get_called_class());
        }
        return $this->{$property};
      } else {
        throw new ModelException($this->app, "Requested attribute does not exist: ".$property." on: ".get_called_class());
      }
    }
  }

  // all classes must implement allow(), which defines user permissions.
  abstract public function allow(User $authingUser, $action, array $params=Null);
  // also should implement validate(), which takes an array of parameters and ensures that they are valid. returns a bool.
  public function validate(array $object) {
    $validationErrors = [];
    if (!$object) {
      $validationErrors[] = "Object must have some attributes set";
    }
    if (isset($object['id']) && ( !is_integral($object['id']) || intval($object['id']) < 0) ) {
      $validationErrors[] = "Object ID must be an integer greater than 0";
    }
    if (isset($object['created_at']) && !strtotime($object['created_at'])) {
      $validationErrors[] = "Malformed created-at time";
    }
    if (isset($object['updated_at']) && !strtotime($object['updated_at'])) {
      $validationErrors[] = "Malformed updated-at time";
    }
    if ($validationErrors) {
      throw new ValidationException($this->app, $object, $validationErrors);
    } else {
      return True;
    }
  }

  public function serialize() {
    // returns an associative array with keys the fields of this object contained in $FIELDS, and values the values on this object.
    $serialized = [];
    foreach (static::$FIELDS as $attr => $info) {
      if (!isset($info['serialize']) || $info['serialize']) {
        if ($this->{$attr} instanceof Model) {
          // recursively-serialize attributes if necessary.
          $serialized[$attr] = $this->{$attr}->serialize();
        } else {
          $serialized[$attr] = $this->{$attr};
        }
      }
    }
    return $serialized;
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
    $this->app->dbConn->table(static::$TABLE);
    if (!isset($object['updated_at'])) {
      $this->app->dbConn->set(['updated_at=NOW()']);
    }

    if ($this->id != 0) {
      $whereConditions['id'] = $this->id;
      //update this object.
      $this->beforeUpdate($object);
      $this->app->dbConn->set($object)->where($whereConditions === Null ? [] : $whereConditions)->limit(1);
      if (!$this->app->dbConn->update()) {
        throw new DatabaseException("Could not update ".static::$TABLE.": ".$this->app->dbConn->queryString());
      }
      $this->set($object);
      $modelName = static::MODEL_NAME();
      $newObject = new $modelName($this->app, $this->id);
      $newObject->afterUpdate($object);
    } else {
      // add this object.
      $this->app->dbConn->set(['created_at=NOW()']);
      $this->beforeCreate([$object]);
      if (!$this->app->dbConn->set($object)->insert()) {
        throw new DatabaseException("Could not insert into ".static::$TABLE.": ".$this->app->dbConn->queryString());
      } else {
        $this->id = intval($this->app->dbConn->lastInsertId);
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
    if (!is_array($entries) && !is_integral($entries)) {
      throw new ValidationException($this->app, $entries, "Invalid ".static::MODEL_NAME()." ID to delete");
    }
    if (is_integral($entries)) {
      $entries = [$entries];
    }
    $entryIDs = [];
    foreach ($entries as $entry) {
      if (is_integral($entry)) {
        $entryIDs[] = intval($entry);
      }
    }
    $this->beforeDelete();
    if ($entryIDs) {
      if (!$this->app->dbConn->table(static::$TABLE)->where(['id' => $entryIDs])->limit(count($entryIDs))->delete()) {
        throw new DatabaseException("Could not delete from ".static::$TABLE.": ".$deleteQuery);
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

    $url = "/".rawurlencode(self::MODEL_URL());
    switch ($action) {
      case 'index':
        break;
      case 'show':
        $url .= "/".rawurlencode($id);
        break;
      default:
        $url .= "/".rawurlencode($id)."/".rawurlencode($action);
        break;
    }
    $url .= ($format !== Null ? ".".rawurlencode($format) : "").($params !== Null ? "?".$urlParams : "");
    return $url;
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
    } catch (DatabaseException $e) {
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