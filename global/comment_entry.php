<?php

class CommentEntry extends BaseEntry {
  protected $comment, $commentId, $user, $userId;
  protected $time;
  protected $status, $score, $episode;
  protected $parent;

  public function __construct(DbConn $database, $id=Null, $params=Null) {
    parent::__construct($database, $id, $params);
    if ($id === 0) {
      $this->comment = new Comment($this->dbConn, 0);
      $this->commentId = $this->userId = 0;
    } else {
      $this->comment = $this->commentId = $this->episode = Null;
    }
    $this->modelTable = "comments";
    $this->modelUrl = "comment_entries";
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
  public function parent() {
    return $this->comment()->parent;
  }
  public function depth() {
    return $this->comment()->depth;
  }
  public function ancestor() {
    return $this->comment()->ancestor;
  }
  public function type() {
    return $this->comment()->type;
  }
  public function time() {
    return $this->comment()->createdAt();
  }
  public function formatFeedEntry(User $currentUser) {
    /* TODO: make this work for comments posted on anime etc */
    if ($currentUser->id != $this->comment()->user()->id) {
      $feedTitle = $this->comment()->user()->link("show", $this->comment()->user()->username);
    } else {
      $feedTitle = "You";
    }
    if ($this->depth() < 2) {
      if ($currentUser->id != $this->comment()->parent()->id) {
        $receivingUser = $this->comment()->parent()->link("show", $this->comment()->parent()->username);
      } else {
        $receivingUser = "you";
      }
      $feedTitle .= " to ".$receivingUser.":";
    }
    return array('title' => $feedTitle, 'text' => escape_output($this->comment()->message()));
  }
  public function url($action="show", array $params=Null, $id=Null) {
    // returns the url that maps to this comment and the given action.
    // if we're showing this comment, show its parent instead.
    if ($action == "show") {
      return $this->parent()->url($action, $params);
    }
    if ($id === Null) {
      $id = intval($this->id);
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output($this->modelTable)."/".($action !== "index" ? intval($id)."/".escape_output($action)."/" : "").($params !== Null ? "?".$urlParams : "");
  }
}

?>