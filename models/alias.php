<?php

class Alias extends Model {
  public static $TABLE = "aliases";
  public static $PLURAL = "aliases";
  public static $FIELDS = [
    'id' => [
      'type' => 'int',
      'db' => 'id'
    ],
    'name' => [
      'type' => 'str',
      'db' => 'name'
    ],
    'type' => [
      'type' => 'str',
      'db' => 'type'
    ],
    'parentId' => [
      'type' => 'int',
      'db' => 'parent_id'
    ],
    'createdAt' => [
      'type' => 'date',
      'db' => 'created_at'
    ],
    'updatedAt' => [
      'type' => 'date',
      'db' => 'updated_at'
    ]
  ];
  public static $JOINS = [
  ];
  public function __construct(Application $app, $id=Null, Model $parent=Null) {
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->name = "";
      $this->parent = $parent;
      if ($parent !== Null) {
        $this->parentId = $parent->id;
        $this->type = get_class($this->parent);
      } else {
        $this->parentId = Null;
        $this->type = Null;
      }
    }
  }
  public function parent() {
    if ($this->parent === Null) {
      $type = $this->type;
      $this->parent = new $type($this->app, $this->parentId);
    }
    return $this->parent;
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'index':
      case 'new':
      case 'edit':
      case 'delete':
        // if this user is staff, allow them to edit this alias.
        if ($authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      default:
        return False;
        break;
    }
  }
  public function validate(array $alias) {
    $validationErrors = [];
    try {
      parent::validate($alias);
    } catch (ValidationException $e) {
      $validationErrors = array_merge($validationErrors, $e->messages);
    }
    if (!isset($alias['type']) || !$alias['type'] || mb_strlen($alias['type']) < 1) {
      $validationErrors[] = "Type must be set to something not-blank";
    }
    if (!isset($alias['parent_id']) || !is_integral($alias['parent_id']) || intval($alias['parent_id']) <= 0) {
      $validationErrors[] = "Parent ID must be a valid integer greater than zero";
    } else {
      try {
        $parent = new $alias['type']($this->app, intval($alias['parent_id']));
        $parent->load();
      } catch (DatabaseException $e) {
        $validationErrors[] = "Parent must exist";
      }
    }
    if (!isset($alias['name']) || !$alias['name'] || mb_strlen($alias['name']) < 1) {
      $validationErrors[] = "Alias name must be set to something not-blank";
    }
    if ($validationErrors) {
      throw new ValidationException($this->app, $alias, $validationErrors);
    } else {
      return True;
    }
  }
  public function search($text) {
    // searches for aliases that match the given string.
    $terms = explode(" ", $text);
    foreach ($terms as $key=>$term) {
      $terms[$key] = "+".$term;
    }
    $text = implode(" ", $terms);
    $search = $this->app->dbConn->table(static::$TABLE)->fields('name', 'type', 'parent_id')->match('name', $text)->order('name ASC')->query();
    $objects = [];
    if ($this->id === 0) {
      $parentClass = get_class($this->parent());
      $objType = $parentClass::MODEL_NAME();
    } else {
      $objType = $this->type;
    }
    while ($result = $search->fetch()) {
      try {
        $tempObject = new $objType($this->app, intval($result['parent_id']));
        $tempObject->load();
        $objects[intval($result['parent_id'])] = ['anime' => $tempObject, 'alias' => $result['name']];
      } catch (DatabaseException $e) {
        // ignore dangling aliases.
      }
    }
    return $objects;
  }
  public function url($action="show", $format=Null, array $params=Null, $id=Null) {
    // returns the url that maps to this comment and the given action.
    // if we're showing this comment, show its parent instead.
    if ($action == "show") {
      return $this->parent()->url($action, $format, $params);
    }
    if ($id === Null) {
      $id = intval($this->id);
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output(static::$TABLE)."/".($action !== "index" ? intval($id)."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
}

?>