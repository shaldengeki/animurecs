<?php

class TagGroup extends BaseGroup {
  // class to provide mass-querying functions for groups of tagIDs or tag objects.
  protected $_groupTable = "tags";
  protected $_groupTableSingular = "tag";
  protected $_groupObject = "Tag";
  protected $_nameField = "name";

  public function __construct(DbConn $dbConn, array $tag) {
    parent::__construct($dbConn, $tag);
  }
  protected function _getTypes() {
    $tagTypeDict = [];
    foreach ($this->tags() as $tag) {
      $tagTypeDict[$tag->tagTypeId] = 1;
    }
    $getTagTypes = $this->dbConn->queryAssoc("SELECT * FROM `tag_types` WHERE `id` IN (".implode(",", array_keys($tagTypeDict)).")");
    foreach ($getTagTypes as $tagType) {
      $tagTypes[$tagType['id']] = new TagType($this->dbConn, intval($tagType['id']));
      $tagTypes[$tagType['id']]->set($tagType);
    }
    foreach ($this->tags() as $tag) {
      $tag->set(array('type' => $tagTypes[$tag->tagTypeId]));
    }
    return $tagTypes;
  }
  protected function _getInfo() {
    parent::_getInfo();
    $this->_getTypes();
  }
  protected function _getTagCounts() {
    $inclusion = [];
    foreach ($this->_objects as $object) {
      $inclusion[] = $object->id;
    }
    return $this->dbConn->queryAssoc("SELECT `tag_id`, COUNT(*) FROM `anime_tags` WHERE  `tag_id` IN (".implode(", ", $inclusion).") GROUP BY  `tag_id` ORDER BY COUNT(*) DESC", 'tag_id', 'COUNT(*)');
  }
  public function tags() {
    return $this->objects();
  }
}
?>