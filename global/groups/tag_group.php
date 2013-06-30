<?php

class TagGroup extends BaseGroup {
  // class to provide mass-querying functions for groups of tagIDs or tag objects.
  protected $_groupTable = "tags";
  protected $_groupTableSingular = "tag";
  protected $_groupObject = "Tag";
  protected $_nameField = "name";

  protected function _getTypes() {
    $tagTypeDict = [];
    foreach ($this->tags() as $tag) {
      $tagTypeDict[intval($tag->type->id)] = 1;
    }
    $tagTypes = [];
    if ($tagTypeDict) {
      $cacheKeys = array_map(function($tagTypeID) {
        return "TagType-".$tagTypeID;
      }, array_keys($tagTypeDict));
      $casTokens = [];

      $tagTypes = [];
      // fetch as many tag types as we can from the cache.
      $cacheValues = $this->app->cache->get($cacheKeys, $casTokens);
      if ($cacheValues) {
        $tagTypesFound = [];
        foreach ($cacheValues as $cacheKey=>$cacheValue) {
          // split the ID off from the cacheKey.
          $tagTypeID = intval(explode("-", $cacheKey)[1]);
          $tagTypes[$tagTypeID] = new TagType($this->app, $tagTypeID);
          $tagTypes[$tagTypeID]->set($cacheValue);
          $tagTypesFound[$tagTypeID] = 1;
        }
        $tagTypeDict = array_diff_key($tagTypeDict, $tagTypesFound);

      }
      if ($tagTypeDict) {
        // now fetch the non-cached results from the db, building a record so we can cache it after.
        $tagTypesToCache = [];
        $getTagTypes = $this->dbConn->assoc("SELECT * FROM `tag_types` WHERE `id` IN (".implode(",", array_keys($tagTypeDict)).")");
        foreach ($getTagTypes as $tagType) {
          $tagTypesToCache["TagType-".$tagType['id']] = $tagType;
          $tagTypes[$tagType['id']] = new TagType($this->app, intval($tagType['id']));
          $tagTypes[$tagType['id']]->set($tagType);
        }
        // finally, store these new records in the cache.
        foreach ($tagTypesToCache as $cacheKey => $cacheValue) {
          $this->app->cache->set($cacheKey, $cacheValue);
        }
      }
      foreach ($this->tags() as $tag) {
        $tag->set(['type' => $tagTypes[$tag->type->id]]);
      }
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
    $tagCountList = $inclusion ? $this->dbConn->assoc("SELECT `tag_id`, COUNT(*) FROM `anime_tags` WHERE `tag_id` IN (".implode(", ", $inclusion).") GROUP BY `tag_id` ORDER BY COUNT(*) DESC", 'tag_id', 'COUNT(*)') : [];
    foreach ($tagCountList as $id=>$count) {
      $tagCountList[$id] = ['tag' => $this->tags()[$id], 'count' => intval($count)];
    }
    return $tagCountList;
  }
  public function tags() {
    return $this->objects();
  }
}
?>