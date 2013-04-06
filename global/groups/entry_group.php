<?php

class EntryGroup extends BaseGroup {
  // class to provide mass-querying functions for groups of entry objects.
  protected $_groupTable = "users";
  protected $_groupTableSingular = "user";
  protected $_groupObject = "User";
  protected $_nameField = "username";

  protected $_anime, $_users, $_comments = Null;

  public function __construct(Application $app, array $entries) {
    // preserves keys of input array.
    $this->dbConn = $app->dbConn;
    $this->app = $app;
    $this->_objects = [];
    $this->intKeys = True;
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
    $animes = [];
    foreach ($this->entries() as $entry) {
      if (method_exists($entry, 'anime') && $entry->animeId !== Null) {
        $animeDict[$entry->animeId] = 1;
      }
    }
    if ($animeDict) {
      // TODO: pull from memcached here.
      $getAnime = $this->dbConn->queryAssoc("SELECT * FROM `anime` WHERE `id` IN (".implode(",", array_keys($animeDict)).")");
      foreach ($getAnime as $anime) {
        $animes[$anime['id']] = new Anime($this->app, intval($anime['id']));
        $animes[$anime['id']]->set($anime);
      }
      foreach ($this->entries() as $entry) {
        if (method_exists($entry, 'anime') && $entry->animeId !== Null) {
          $entry->set(array('anime' => $animes[$entry->animeId]));
        }
      }
    }
    return new AnimeGroup($this->app, $animes);
  }
  public function anime() {
    if ($this->_anime === Null) {
      $this->_anime = $this->_getAnime();
    }
    return $this->_anime;
  }
  private function _getUsers() {
    $userDict = [];
    $users = [];
    foreach ($this->entries() as $entry) {
      if (method_exists($entry, 'parentId') && $entry->parentId !== Null) {
        $userDict[$entry->parentId] = 1;
      }
      $userDict[$entry->userId] = 1;
    }
    if ($userDict) {
      $getUsers = $this->dbConn->queryAssoc("SELECT * FROM `users` WHERE `id` IN (".implode(",", array_keys($userDict)).")");
      foreach ($getUsers as $user) {
        $users[$user['id']] = new User($this->app, intval($user['id']));
        $users[$user['id']]->set($user);
      }
      foreach ($this->entries() as $entry) {
        $setArray = array('user' => $users[$entry->userId]);
        if (method_exists($entry, 'parentId') && $entry->parentId !== Null && isset($users[$entry->parentId])) {
          $setArray['parent'] = $users[$entry->parentId];
        }
        $entry->set($setArray);
      }
    }
    return new UserGroup($this->app, $users);
  }
  public function users() {
    if ($this->_users === Null) {
      $this->_users = $this->_getUsers();
    }
    return $this->_users;
  }
  private function _getComments() {
    $commentDict = $comments = $commentFlatList = [];
    foreach ($this->entries() as $entry) {
      $entryClass = get_class($entry);
      if (method_exists($entry, 'comment')) {
        $commentDict["(`id` = ".$entry->commentId.")"] = 1;
      }
      $commentDict["(`type` = '".$entryClass::modelName()."' && `parent_id` = ".$entry->id.")"] = 1;
    }
    if ($commentDict) {
      $getComments = $this->dbConn->queryAssoc("SELECT * FROM `comments` WHERE ".implode(" || ", array_keys($commentDict)));
      foreach ($getComments as $comment) {
        $newComment = new CommentEntry($this->app, intval($comment['id']));
        $newComment->comment()->set($comment);
        if (!isset($comments[$comment['type']])) {
          $comments[$comment['type']] = array();
        }
        if (!isset($comments[$comment['type']][$comment['parent_id']])) {
          $comments[$comment['type']][$comment['parent_id']] = array($newComment->id => $newComment);
        } else {
          $comments[$comment['type']][$comment['parent_id']][$newComment->id] = $newComment;
        }
        $comments[$comment['id']] = $newComment;
        $commentFlatList[$comment['id']] = $newComment;
      }
      foreach ($this->entries() as $entry) {
        $entryClass = get_class($entry);
        if (method_exists($entry, 'comment')) {
          $entry->set(array('comment' => $comments[$entry->commentId]->comment()));
        }
        if (isset($comments[$entryClass::modelName()][$entry->id])) {
          $entry->set(array('comments' => $comments[$entryClass::modelName()][$entry->id]));
        } else {
          $entry->set(array('comments' => []));
        }
      }
    }
    return $commentFlatList;
  }
  public function comments() {
    if ($this->_comments === Null) {
      $this->_comments = $this->_getComments();
    }
    return $this->_comments;
  }
  public function entries() {
    return $this->objects();
  }
}
?>