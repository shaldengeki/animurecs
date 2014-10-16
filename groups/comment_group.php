<?php

class CommentGroup extends ModelGroup {
  // class to provide mass-querying functions for groups of commentIDs or comment objects.
  protected $_groupTable = "comments";
  protected $_groupTableSingular = "comment";
  protected $_groupObject = "Comment";
  
  public function comments() {
    return $this->objects();
  }
}
?>