<?php

trait Commentable {
  // allows an object to have comments on its profile.
  // you'll also need to provide a 'comment' case in the class's allow() method.
  
  protected $profileComments;

  public function getProfileComments() {
    // returns a list of comment objects sent by this user.
    $profileComments = $this->dbConn->stdQuery("SELECT `id` FROM `comments` WHERE `type` = '".$this->modelName()."' && `parent_id` = ".intval($this->id)." ORDER BY `created_at` DESC");
    $comments = [];
    while ($comment = $profileComments->fetch_assoc()) {
      $comments[intval($comment['id'])] = new Comment($this->dbConn, intval($comment['id']));
    }
    return $comments;
  }
  public function profileComments() {
    if ($this->profileComments === Null) {
      $this->profileComments = $this->getProfileComments();
    }
    return $this->profileComments;
  }
}

?>