<?php

trait Commentable {
  // allows an object to have comments on its profile.
  // you'll also need to provide a 'comment' case in the class's allow() method.
  
  protected $comments;

  public function getComments() {
    // returns a list of commentEntry objects sent by this user.
    $profileComments = $this->dbConn->stdQuery("SELECT `id` FROM `comments` WHERE `type` = '".$this->modelName()."' && `parent_id` = ".intval($this->id)." ORDER BY `created_at` ASC");
    $comments = [];
    while ($comment = $profileComments->fetch_assoc()) {
      $comments[intval($comment['id'])] = new CommentEntry($this->app, intval($comment['id']));
    }
    return new EntryGroup($this->app, $comments);
  }
  public function comments() {
    if ($this->comments === Null) {
      $this->comments = $this->getComments();
    }
    return $this->comments;
  }
}

?>