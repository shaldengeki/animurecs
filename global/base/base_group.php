<?php

class BaseGroup implements Iterator, ArrayAccess {
  // class to provide mass-querying functions for groups of object IDs or objects.
  // you can treat this as if it were an array of objects
  // e.g. foreach($group->load('info') as $object) or $group->load('info')[1]

  protected $_objects,$_objectGroups,$_objectKeys = [];
  private $position = 0;
  protected $_tagCounts=Null;
  protected $_pulledInfo=False;
  public $intKeys=True;
  public $dbConn,$app=Null;
  protected $_groupTable,$_groupTableSingular,$_groupObject,$_nameField = Null;

  public function __construct(Application $app, array $objects) {
    // preserves keys of input array.
    $this->position = 0;
    $this->app = $app;
    $this->dbConn = $app->dbConn;
    $this->_objects = [];
    if (count($objects) > 0) {
      foreach ($objects as $key=>$object) {
        $this->intKeys = $this->intKeys && is_int($key);
      }
      if (current($objects) instanceof $this->_groupObject) {
        $this->_objects = $objects;
      } elseif (is_numeric(current($objects))) {
        foreach ($objects as $key=>$objectID) {
          $this->_objects[$key] = new $this->_groupObject($this->app, intval($objectID));
        }
      }
    }
    $this->_objectKeys = array_keys($this->_objects);
    $this->_setObjectGroups();
  }
  // iterator functions.
  public function rewind() {
    $this->position = 0;
  }
  public function current() {
    return $this->objects()[$this->_objectKeys[$this->position]];
  }
  public function key() {
    return $this->position;
  }
  public function next() {
    ++$this->position;
  }
  public function valid() {
    return isset($this->objects()[$this->_objectKeys[$this->position]]);
  }

  // array access functions.
  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      $this->objects()[] = $value;
    } else {
      $this->objects()[$offset] = $value;
    }
  }
  public function offsetExists($offset) {
    return isset($this->objects()[$offset]);
  }
  public function offsetUnset($offset) {
    unset($this->objects()[$offset]);
  }
  public function offsetGet($offset) {
    return isset($this->objects()[$offset]) ? $this->objects()[$offset] : null;
  }

  // we need to set object groups by object table, so we can query the proper tables for information when eager-loading is triggered.
  protected function _setObjectGroups() {
    $this->_objectGroups = [];
    foreach ($this->_objects as $key=>$object) {
      if (!isset($this->_objectGroups[$object->modelTable])) {
        $this->_objectGroups[$object->modelTable] = array($key=>$object);
      } else {
        $this->_objectGroups[$object->modelTable][$key] = $object;
      }
    }
  }

  // returns the string name of the database table for the first object in this group.
  public function groupTable() {
    if ($this->_groupTable === Null) {
      $this->_groupTable = count($objects) > 0 ? current($objects)->modelTable : Null;
    }
    return $this->_groupTable;
  }

  // returns the string name of the first object in this group.
  public function groupObject() {
    if ($this->_groupObject === Null) {
      $this->_groupObject = count($objects) > 0 ? current($objects)->modelName(): Null;
    }
    return $this->_groupObject;
  }
  public function objects() {
    return $this->_objects;
  }
  public function load($attrs) {
    // eager-loads properties or methods, returning the current object.
    // input can be a single string (name of property/method of element)
    // e.g. 'info' or 'tags'

    // or array('attr' => 'attr') (name of property/methods of object group belonging to element)
    // e.g. array('tags' => 'info')

    if (!is_array($attrs)) {
      if (method_exists($this, $attrs)) {
        $this->$attrs();
      } elseif (property_exists($this, $attrs)) {
        $this->attrs;
      }
    } else {
      $key = key($attrs);
      if ($key !== Null) {
        $value = $attrs[$key];
        if (method_exists($this, $key) && is_object($this->$key())) {
          if (method_exists($this->$key(), $value)) {
            $this->$key()->$value();
          } elseif (property_exists($this->$key(), $value)) {
            $this->$key()->$value;
          }
        } elseif (property_exists($this, $key) && is_object($this->$key)) {
          if (method_exists($this->$key, $value)) {
            $this->$key->$value();
          } elseif (property_exists($this->$key, $value)) {
            $this->$key->$value;
          }
        }
      }
    }
    return $this;
  }
  protected function _getInfo() {
    foreach ($this->_objectGroups as $groupTable=>$objectList) {
      $inclusion = [];
      foreach ($objectList as $object) {
        $inclusion[] = $object->id;
      }
      if ($inclusion) {
        $objectInfo = $this->dbConn->queryAssoc("SELECT * FROM `".$groupTable."` WHERE `id` IN (".implode(", ", $inclusion).")");
        foreach ($objectInfo as $info) {
          $object = current(array_filter_by_property($objectList, "id", intval($info['id'])));
          $object->set($info);
        }
      }
    }
  }
  public function info() {
    if (!$this->_pulledInfo) {
      $this->_pulledInfo = True;
      $this->_getInfo();
    }
    return $this->_objects;
  }
  protected function _getTagCounts() {
    $inclusion = [];
    foreach ($this->_objects as $object) {
      $inclusion[] = $object->id;
    }
    return $inclusion ? $this->dbConn->queryAssoc("SELECT `tag_id`, COUNT(*) FROM `".$this->_groupTable."_tags` INNER JOIN `tags` ON  `tags`.`id` =  `tag_id` WHERE  `".$this->_groupTableSingular."_id` IN (".implode(", ", $inclusion).") GROUP BY  `tag_id` ORDER BY COUNT(*) DESC", 'tag_id', 'COUNT(*)') : [];
  }
  public function tagCounts() {
    if ($this->_tagCounts === Null) {
      $this->_tagCounts = $this->_getTagCounts();
    }
    return $this->_tagCounts;
  }
  public function append(BaseGroup $group, $override=False) {
    // appends another basegroup's objects to this one.
    // overrides keys if any non-numeric.
    foreach ($group->objects() as $key=>$object) {
      if (!$override && $this->intKeys && $group->intKeys) {
        array_push($this->_objects, $object);
      } else {
        $this->_objects[$key] = $object;
      }
    }
    $this->_objectKeys = array_keys($this->_objects);
    $this->_setObjectGroups();
    return $this->objects();
  }
}

?>