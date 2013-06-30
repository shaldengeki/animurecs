<?php

class ThreadGroup extends BaseGroup {
  // class to provide mass-querying functions for groups of threadIDs or thread objects.
  protected $_groupTable = "threads";
  protected $_groupTableSingular = "thread";
  protected $_groupObject = "Thread";
  protected $_nameField = "title";
  private $_tags, $_predictions=Null;
  
  public function threads() {
    return $this->objects();
  }

  private function _getTags() {
    $threadIDs = array_map(function($thread) {
      return intval($thread->id);
    }, $this->threads());

    if ($threadIDs) {
      $cacheKeys = array_map(function($threadID) {
        return "Thread-".$threadID."-tagIDs";
      }, $threadIDs);
      $casTokens = [];

      $tags = [];
      // fetch as many tags as we can from the cache.
      $cacheValues = $this->app->cache->get($cacheKeys, $casTokens);
      if ($cacheValues) {
        $threadFound = [];
        foreach ($cacheValues as $cacheKey=>$tagIDs) {
          foreach ($tagIDs as $tagID) {
            $tags[$tagID] = $tagID;
          }
          // split the ID off from the cacheKey.
          $threadFound[] = intval(explode("-", $cacheKey)[1]);
        }
        $threadIDs = array_diff($threadIDs, $threadFound);

      }
      if ($threadIDs) {
        // now fetch the non-cached results from the db, building a record so we can cache it after.
        $threadTags = [];
        $fetchTaggings = $this->dbConn->query("SELECT `thread_id`, `tag_id` FROM `anime_tags` WHERE `thread_id` IN (".implode(",", $threadIDs).")");
        while ($tagging = $fetchTaggings->fetch_assoc()) {
          $threadID = intval($tagging['thread_id']);
          $tagID = intval($tagging['tag_id']);
          $tags[$tagID] = $tagID;
          if (isset($threadTags[$threadID])) {
            $threadTags[$threadID][] = $tagID;
          } else {
            $threadTags[$threadID] = [$tagID];
          }
        }
        // finally, store these new records in the cache.
        foreach ($threadTags as $threadID => $tagIDs) {
          $cacheKey = "Thread-".$threadID."-tagIDs";
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

  // public function tagList($n=50) {
  //   // displays a list of tags for this group of anime, sorted by frequency of tag.
  //   $tagCounts = $this->tagCounts();
  //   $output = "<ul class='tagList'>\n";
  //   $i = 1;
  //   $this->tags()->load('info');
  //   foreach ($tagCounts as $id=>$count) {
  //     $output .= "<li>".$this->tags()[$id]->link("show", $this->tags()[$id]->name)." ".intval($count)."</li>\n";
  //     if ($i >= $n) {
  //       break;
  //     }
  //     $i++;
  //   }
  //   $output .= "</ul>";
  //   return $output;
  // }

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