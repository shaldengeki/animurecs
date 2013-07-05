<?php

class Alias extends BaseObject {
  protected $name;
  protected $type;
  protected $parentId;
  protected $parent;
  public static $MODEL_TABLE = "aliases";
  public static $MODEL_PLURAL = "aliases";

  public function __construct(Application $app, $id=Null, BaseObject $parent=Null) {
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
    } else {
      $this->name = $this->parent = $this->parentId = $this->type = Null;
    }
  }
  public function name() {
    return $this->returnInfo('name');
  }
  public function type() {
    return $this->returnInfo('type');
  }
  public function parentId() {
    return $this->returnInfo('parentId');
  }
  public function parent() {
    if ($this->parent === Null) {
      $type = $this->type();
      $this->parent = new $type($this->app, $this->parentId());
    }
    return $this->parent;
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
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
    if (!parent::validate($alias)) {
      return False;
    }
    if (!isset($alias['type']) || !isset($alias['parent_id'])) {
      return False;
    }
    if (!is_numeric($alias['parent_id']) || intval($alias['parent_id']) != $alias['parent_id'] || intval($alias['parent_id']) <= 0) {
      return False;
    } else {
      try {
        $parent = new $alias['type']($this->app, intval($alias['parent_id']));
        $parent->getInfo();
      } catch (Exception $e) {
        return False;
      }
    }
    if (isset($alias['name']) && strlen($alias['name']) < 1) {
      return False;
    }
    return True;
  }
  public function search($text) {
    // searches for aliases that match the given string.
    $terms = explode(" ", $text);
    foreach ($terms as $key=>$term) {
      $terms[$key] = "+".$term;
    }
    $text = implode(" ", $terms);
    $search = $this->app->dbConn->table(static::$MODEL_TABLE)->fields('type', 'parent_id')->match('name', $text)->order('name ASC')->query();
    $objects = [];
    if ($this->id === 0) {
      $parentClass = get_class($this->parent());
      $objType = $parentClass::MODEL_NAME();
    } else {
      $objType = $this->type();
    }
    while ($result = $search->fetch()) {
      try {
        $tempObject = new $objType($this->app, intval($result['parent_id']));
        $tempObject->getInfo();
        $objects[intval($result['parent_id'])] = $tempObject;
      } catch (Exception $e) {
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
    return "/".escape_output(static::$MODEL_TABLE)."/".($action !== "index" ? intval($id)."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
}

?>