<?php

class Alias extends BaseObject {
  protected $name;
  protected $type;
  protected $parentId;
  protected $parent;

  public function __construct(DbConn $database, $id=Null, BaseObject $parent=Null) {
    parent::__construct($database, $id);
    $this->modelTable = "aliases";
    $this->modelPlural = "aliases";
    if ($id === 0) {
      $this->name = "";
      $this->parent = $parent;
      $this->parentId = $parent->id;
      $this->type = get_class($this->parent);
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
      $this->parent = new $type($this->dbConn, $this->parentId());
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
        $parent = new $alias['type']($this->dbConn, intval($alias['parent_id']));
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
    $search = $this->dbConn->stdQuery("SELECT `type`, `parent_id` FROM `".$this->modelTable."` WHERE MATCH(`name`) AGAINST(".$this->dbConn->quoteSmart($text)." IN BOOLEAN MODE) ORDER BY `name` ASC;");
    $objects = [];
    if ($this->id === 0) {
      $objType = $this->parent()->modelName();
    } else {
      $objType = $this->type();
    }
    while ($result = $search->fetch_assoc()) {
      try {
        $tempObject = new $objType($this->dbConn, intval($result['parent_id']));
        $tempObject->getInfo();
        $objects[intval($result['parent_id'])] = $tempObject;
      } catch (Exception $e) {
        // ignore dangling aliases.
      }
    }
    return $objects;
  }
  public function form(User $currentUser, BaseObject $currentObject) {
    $output = "    <form action='".(($this->id === 0) ? $this->url("new") : $this->url("edit"))."' method='POST' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='alias[id]' value='".intval($this->id)."' />")."
      <input type='hidden' name='alias[type]' value='".escape_output(($this->id === 0) ? get_class($currentObject) : $this->type())."' />
      <input type='hidden' name='alias[parent_id]' value='".(($this->id === 0) ? intval($currentObject->id) : $this->parent()->id)."' />
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='alias[name]'>Name</label>
          <div class='controls'>
            <input type='text' name='alias[name]' id='alias[name]' value='".(($this->id === 0) ? "" : escape_output($this->name()))."' />
          </div>
        </div>

        <div class='form-actions'>
          <button type='submit' class='btn btn-primary'>".(($this->id === 0) ? "Add Alias" : "Save changes")."</button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>".(($this->id === 0) ? "Go back" : "Discard changes")."</a>
        </div>
      </fieldset>\n</form>\n";
    return $output;
  }
  public function inlineForm(User $currentUser, BaseObject $currentObject) {
    $output = "    <form class='form-inline' action='".(($this->id === 0) ? $this->url("new") : $this->url("edit"))."' method='POST'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='alias[id]' value='".intval($this->id)."' />")."
      <input type='hidden' name='alias[type]' value='".escape_output(($this->id === 0) ? get_class($currentObject) : $this->type())."' />
      <input type='hidden' name='alias[parent_id]' value='".(($this->id === 0) ? intval($currentObject->id) : $this->parent()->id)."' />
      <input type='text' name='alias[name]'".(($this->id === 0) ? "placeholder='Add an alias'" : "value='".escape_output($this->name())."'")." />
      <button type='submit' class='btn btn-primary'>".(($this->id === 0) ? "Add" : "Update")."</button>
    </form>\n";
    return $output;
  }
  public function url($action="show", array $params=Null, $id=Null) {
    // returns the url that maps to this comment and the given action.
    // if we're showing this comment, show its parent instead.
    if ($action == "show") {
      return $this->parent()->url($action, $params);
    }
    if ($id === Null) {
      $id = intval($this->id);
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output($this->modelTable)."/".($action !== "index" ? intval($id)."/".escape_output($action)."/" : "").($params !== Null ? "?".$urlParams : "");
  }
}

?>