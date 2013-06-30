<?php

class AnimeGroup extends BaseGroup {
  // class to provide mass-querying functions for groups of animeIDs or anime objects.
  public static $modelUrl = "anime_groups";

  protected $_groupTable = "anime";
  protected $_groupTableSingular = "anime";
  protected $_groupObject = "Anime";
  protected $_nameField = "title";
  private $_tags, $_predictions=Null;

  public function anime() {
    return $this->objects();
  }

  private function _getTags() {
    $animeIDs = array_map(function($anime) {
      return intval($anime->id);
    }, $this->anime());

    if ($animeIDs) {
      $cacheKeys = array_map(function($animeID) {
        return "Anime-".$animeID."-tagIDs";
      }, $animeIDs);
      $casTokens = [];

      $tags = [];
      // fetch as many tags as we can from the cache.
      $cacheValues = $this->app->cache->get($cacheKeys, $casTokens);
      if ($cacheValues) {
        $animeFound = [];
        foreach ($cacheValues as $cacheKey=>$tagIDs) {
          foreach ($tagIDs as $tagID) {
            $tags[$tagID] = $tagID;
          }
          // split the ID off from the cacheKey.
          $animeFound[] = intval(explode("-", $cacheKey)[1]);
        }
        $animeIDs = array_diff($animeIDs, $animeFound);

      }
      if ($animeIDs) {
        // now fetch the non-cached results from the db, building a record so we can cache it after.
        $animeTags = [];
        $fetchTaggings = $this->dbConn->query("SELECT `anime_id`, `tag_id` FROM `anime_tags` WHERE `anime_id` IN (".implode(",", $animeIDs).")");
        while ($tagging = $fetchTaggings->fetch_assoc()) {
          $animeID = intval($tagging['anime_id']);
          $tagID = intval($tagging['tag_id']);
          $tags[$tagID] = $tagID;
          if (isset($animeTags[$animeID])) {
            $animeTags[$animeID][] = $tagID;
          } else {
            $animeTags[$animeID] = [$tagID];
          }
        }
        // finally, store these new records in the cache.
        foreach ($animeTags as $animeID => $tagIDs) {
          $cacheKey = "Anime-".$animeID."-tagIDs";
          $this->app->cache->set($cacheKey, $tagIDs);
        }
      }
    }
    return new TagGroup($this->app, $tags);
  }
  public function tags() {
    if ($this->_tags === Null) {
      $this->_tags = $this->_getTags();
    }
    return $this->_tags;
  }

  public function _getPredictions() {
    return False;
    /* TODO */
  }

  public function predictions() {
    return False;
    /* TODO
    if ($this->_predictions === Null) {
      $this->_predictions = $this->_getPredictions();
    }
    return $this->_predictions;
    */
  }
}
?>