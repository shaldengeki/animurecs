<?php

class AnimeGroup extends BaseGroup {
  // class to provide mass-querying functions for groups of animeIDs or anime objects.
  protected $_groupTable = "anime";
  protected $_groupTableSingular = "anime";
  protected $_groupObject = "Anime";
  protected $_nameField = "title";
  private $_tags=Null;
  
  public function anime() {
    return $this->objects();
  }

  private function _getTags() {
    $animeIDs = array_map(function($anime) {
      return intval($anime->id);
    }, $this->anime());

    $tags = $this->dbConn->queryAssoc("SELECT `tag_id` FROM `anime_tags` WHERE `anime_id` IN (".implode(",", $animeIDs).")", 'tag_id', 'tag_id');
    return new TagGroup($this->app, $tags);
  }
  public function tags() {
    if ($this->_tags === Null) {
      $this->_tags = $this->_getTags();
    }
    return $this->_tags;
  }

  public function tag_list($n=50) {
    // displays a list of tags for this group of anime, sorted by frequency of tag.
    $tagCounts = $this->tagCounts();
    $output = "<ul class='tagList'>\n";
    $i = 1;
    $this->tags()->load('info');
    foreach ($tagCounts as $id=>$count) {
      $output .= "<li>".$this->tags()[$id]->link("show", $this->tags()[$id]->name)." ".intval($count)."</li>\n";
      if ($i >= $n) {
        break;
      }
      $i++;
    }
    $output .= "</ul>";
    return $output;
  }
}
?>