<?php

class BaseGroup {
  // class to provide mass-querying functions for groups of object IDs or objects.
  protected $_objects,$_objectGroups = [];
  protected $_tagCounts=Null;
  protected $_pulledInfo=False;
  public $intKeys=True;
  public $dbConn=Null;
  protected $_groupTable,$_groupTableSingular,$_groupObject,$_nameField = Null;

  public function __construct(DbConn $dbConn, array $objects) {
    // preserves keys of input array.
    $this->dbConn = $dbConn;
    if (count($objects) > 0) {
      foreach ($objects as $key=>$object) {
        $this->intKeys = $this->intKeys && is_int($key);
      }
      if (current($objects) instanceof $this->_groupObject) {
        $this->_objects = $objects;
      } elseif (is_numeric(current($objects))) {
        foreach ($objects as $key=>$objectID) {
          $this->_objects[$key] = new $this->_groupObject($this->dbConn, intval($objectID));
        }
      }
    }
    $this->_setObjectGroups();
  }
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
  public function groupTable() {
    if ($this->_groupTable === Null) {
      $this->_groupTable = count($objects) > 0 ? current($objects)->modelTable : Null;
    }
    return $this->_groupTable;
  }
  public function groupObject() {
    if ($this->_groupObject === Null) {
      $this->_groupObject = count($objects) > 0 ? current($objects)->modelName(): Null;
    }
    return $this->_groupObject;
  }
  public function objects() {
    return $this->_objects;
  }
  protected function _getInfo() {
    foreach ($this->_objectGroups as $groupTable=>$objectList) {
      $inclusion = [];
      foreach ($objectList as $object) {
        $inclusion[] = $object->id;
      }
      $objectInfo = $this->dbConn->queryAssoc("SELECT * FROM `".$groupTable."` WHERE `id` IN (".implode(", ", $inclusion).")");
      foreach ($objectInfo as $info) {
        $object = current(array_filter_by_property($objectList, "id", intval($info['id'])));
        $object->set($info);
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
    return $this->dbConn->queryAssoc("SELECT `tag_id`, COUNT(*) FROM `".$this->_groupTable."_tags` INNER JOIN `tags` ON  `tags`.`id` =  `tag_id` WHERE  `".$this->_groupTableSingular."_id` IN (".implode(", ", $inclusion).") GROUP BY  `tag_id` ORDER BY COUNT(*) DESC", 'tag_id', 'COUNT(*)');
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
    $this->_setObjectGroups();
    return $this->objects();
  }
}

?>