<?php

class CommentGroup extends BaseGroup {
  // class to provide mass-querying functions for groups of commentIDs or comment objects.
  protected $_groupTable = "comments";
  protected $_groupTableSingular = "comment";
  protected $_groupObject = "Comment";

  public function __construct(DbConn $dbConn, array $comments) {
    parent::__construct($dbConn, $comments);
  }
  public function comments() {
    return $this->objects();
  }
}
?>