<?php

trait Commentable {
  // allows an object to have comments on its profile.
  // you'll also need to provide a 'comment' case in the class's allow() method.
  
  protected $comments;

  public function getComments() {
    // returns a list of commentEntry objects sent by this user.
    $profileComments = $this->app->dbConn->table(Comment::$MODEL_TABLE)->fields('id')->where(['type' => static::MODEL_NAME(), 'parent_id' => $this->id])->order('created_at ASC')->query();
    $comments = [];
    while ($comment = $profileComments->fetch()) {
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