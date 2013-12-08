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
      $animes = Anime::findByIds($this->app, array_keys($animeDict));
      foreach ($this->entries() as $entry) {
        if (method_exists($entry, 'anime') && $entry->animeId !== Null) {
          $entry->set(['anime' => $animes[$entry->animeId]]);
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
      $users = User::findByIds($this->app, array_keys($userDict));
      foreach ($this->entries() as $entry) {
        $setArray = ['user' => $users[$entry->userId]];
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
      $commentDict["(`type` = '".$entryClass::MODEL_NAME()."' && `parent_id` = ".$entry->id.")"] = 1;
    }
    if ($commentDict) {
      $getComments = $this->app->dbConn->table(Comment::$TABLE)->where([implode(" || ", array_keys($commentDict))])->assoc();
      foreach ($getComments as $comment) {
        $newComment = new CommentEntry($this->app, intval($comment['id']));
        $newComment->comment()->set($comment);
        if (!isset($comments[$comment['type']])) {
          $comments[$comment['type']] = [];
        }
        if (!isset($comments[$comment['type']][$comment['parent_id']])) {
          $comments[$comment['type']][$comment['parent_id']] = [$newComment->id => $newComment];
        } else {
          $comments[$comment['type']][$comment['parent_id']][$newComment->id] = $newComment;
        }
        $comments[$comment['id']] = $newComment;
        $commentFlatList[$comment['id']] = $newComment;
      }
      foreach ($this->entries() as $entry) {
        $entryClass = get_class($entry);
        if (method_exists($entry, 'comment')) {
          $entry->set(['comment' => $comments[$entry->commentId]->comment()]);
        }
        if (isset($comments[$entryClass::MODEL_NAME()][$entry->id])) {
          $entry->set(['comments' => $comments[$entryClass::MODEL_NAME()][$entry->id]]);
        } else {
          $entry->set(['comments' => []]);
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
  public function lastCommentTime() {
    return max(array_map(function($c) {
      return $c->createdAt;
    }, $this->comments()));
  }
  public function entries() {
    return $this->objects();
  }
}
?>