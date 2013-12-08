<?php

trait Commentable {
  // allows an object to have comments on its profile.
  // you'll also need to provide a 'comment' case in the class's allow() method.
  
  protected $comments;

  public function getComments() {
    // returns a list of commentEntry objects sent by this user.
    $ownedComments = $this->app->dbConn->table(Comment::$TABLE)
                        ->fields('id')
                        ->where([
                          Comment::DB_FIELD('type') => static::MODEL_NAME(),
                          Comment::DB_FIELD('parentId') => $this->id
                        ])
                        ->order(Comment::DB_FIELD('createdAt').' ASC')
                        ->query();
    $comments = [];
    while ($comment = $ownedComments->fetch()) {
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
  public function lastCommentTime() {
    return max(array_map(function($c) {
      return $c->createdAt();
    }, $this->comments()));
  }
}

?>