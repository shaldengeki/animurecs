<?php

class AnimeGroup extends BaseGroup {
  // class to provide mass-querying functions for groups of animeIDs or anime objects.
  protected $_groupTable = "anime";
  protected $_groupTableSingular = "anime";
  protected $_groupObject = "Anime";
  protected $_nameField = "title";
  
  public function anime() {
    return $this->objects();
  }
}
?>