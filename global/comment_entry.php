<?php

class CommentEntry extends BaseEntry {
  protected $comment, $commentId, $user, $userId;
  protected $time;
  protected $status, $score, $episode;

  public function __construct(DbConn $database, $id=Null, $user=Null) {
    parent::__construct($database, $id, $user);
    if ($id === 0) {
      $this->comment = new Comment($this->dbConn, 0);
      $this->commentId = $this->userId = 0;
    } else {
      $this->comment = $this->commentId = $this->episode = Null;
    }
    $this->modelTable = "comments";
    $this->modelPlural = "comments";
    $this->entryType = "Comment";
    $this->typeVerb = "watching";
    $this->feedType = "Comment";
    $this->entryTypeLower = strtolower($this->entryType);
    $this->typeID = $this->entryTypeLower.'_id';
  }
  public function commentId() {
    return $this->returnInfo('id');
  }
  public function comment() {
    if ($this->comment === Null) {
      $this->comment = new Comment($this->dbConn, $this->commentId());
    }
    return $this->comment;
  }
  public function time() {
    return $this->comment()->createdAt;
  }
  public function formatFeedEntry(User $currentUser) {
    /* TODO: make this work for comments posted on anime etc */
    if ($currentUser->id != $this->comment()->user()->id) {
      $sendingUser = $this->comment()->user()->link("show", $this->comment()->user()->username);
    } else {
      $sendingUser = "You";
    }
    if ($currentUser->id != $this->comment()->parent()->id) {
      $receivingUser = $this->comment()->parent()->link("show", $this->comment()->parent()->username);
    } else {
      $receivingUser = "you";
    }

    $feedTitle = $sendingUser." to ".$receivingUser.":";
    return array('title' => $feedTitle, 'text' => escape_output($this->comment()->message()));
  }
}

?>