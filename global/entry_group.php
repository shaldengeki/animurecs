<?php

class EntryGroup extends BaseGroup {
  // class to provide mass-querying functions for groups of entry objects.
  protected $_groupTable = "users";
  protected $_groupTableSingular = "user";
  protected $_groupObject = "User";
  protected $_nameField = "username";

  protected $_anime, $_users, $_comments = Null;

  public function __construct(DbConn $dbConn, array $entries) {
    // preserves keys of input array.
    $this->dbConn = $dbConn;
    $this->_objects = [];
    if (count($entries) > 0) {
      foreach ($entries as $key=>$object) {
        $this->intKeys = $this->intKeys && is_int($key);
      }
      $this->_objects = $entries;
    }
    $this->_setObjectGroups();
  }
  private function _getAnime() {
    $animeDict = [];
    foreach ($this->entries() as $entry) {
      if (method_exists($entry, 'anime') && $entry->animeId !== Null) {
        $animeDict[$entry->animeId] = 1;
      }
    }
    $getAnime = $this->dbConn->queryAssoc("SELECT * FROM `anime` WHERE `id` IN (".implode(",", array_keys($animeDict)).")");
    foreach ($getAnime as $anime) {
      $animes[$anime['id']] = new Anime($this->dbConn, intval($anime['id']));
      $animes[$anime['id']]->set($anime);
    }
    foreach ($this->entries() as $entry) {
      if (method_exists($entry, 'anime') && $entry->animeId !== Null) {
        $entry->set(array('anime' => $animes[$entry->animeId]));
      }
    }
    return $animes;
  }
  public function anime() {
    if ($this->_anime === Null) {
      $this->_anime = $this->_getAnime();
    }
    return $this->_anime;
  }
  private function _getUsers() {
    $userDict = [];
    foreach ($this->entries() as $entry) {
      if ($entry->parentId !== Null && method_exists($entry, 'parent')) {
        $userDict[$entry->parentId] = 1;
      }
      $userDict[$entry->userId] = 1;
    }
    $getUsers = $this->dbConn->queryAssoc("SELECT * FROM `users` WHERE `id` IN (".implode(",", array_keys($userDict)).")");
    foreach ($getUsers as $user) {
      $users[$user['id']] = new User($this->dbConn, intval($user['id']));
      $users[$user['id']]->set($user);
    }
    foreach ($this->entries() as $entry) {
      $setArray = array('user' => $users[$entry->userId]);
      if ($entry->parentId !== Null && method_exists($entry, 'parent')) {
        $setArray['parent'] = $users[$entry->parentId];
      }
      $entry->set($setArray);
    }
    return $users;
  }
  public function users() {
    if ($this->_users === Null) {
      $this->_users = $this->_getUsers();
    }
    return $this->_users;
  }
  private function _getComments() {
    $commentDict = [];
    foreach ($this->entries() as $entry) {
      if (method_exists($entry, 'comment')) {
        $commentDict["(`id` = ".$entry->commentId.")"] = 1;
      }
      $commentDict["(`type` = '".$entry->modelName()."' && `parent_id` = ".$entry->id.")"] = 1;
    }
    $getComments = $this->dbConn->queryAssoc("SELECT * FROM `comments` WHERE ".implode(" || ", array_keys($commentDict)));
    foreach ($getComments as $comment) {
      $newComment = new Comment($this->dbConn, intval($comment['id']));
      $newComment->set($comment);
      if (!isset($comments[$comment['type']."||".$comment['parent_id']])) {
        $comments[$comment['type']."||".$comment['parent_id']] = array($newComment->id => $newComment);
      } else {
        $comments[$comment['type']."||".$comment['parent_id']][$newComment->id] = $newComment;
      }
      $comments[$comment['id']] = $newComment;
    }
    foreach ($this->entries() as $entry) {
      if (method_exists($entry, 'comment')) {
        $entry->set(array('comment' => $comments[$entry->commentId]));
      }
      if (isset($comments[$entry->modelName()."||".$entry->id])) {
        $entry->set(array('comments' => $comments[$entry->modelName()."||".$entry->id]));
      } else {
        $entry->set(array('comments' => []));
      }
    }
  }
  public function comments() {
    if ($this->_comments === Null) {
      $this->_getComments();
      $this->_comments = True;
    }
    return;
  }

  public function entries() {
    return $this->objects();
  }
}
?>