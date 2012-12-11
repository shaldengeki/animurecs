<?php
class Comment extends BaseObject {
  use Feedable;

  protected $userId;
  protected $user;
  protected $type;
  protected $parentId;
  protected $parent;
  protected $ancestor;
  protected $depth;
  protected $message;

  protected $entries;

  public function __construct(DbConn $database, $id=Null, User $user=Null, BaseObject $parent=Null) {
    parent::__construct($database, $id);
    $this->modelTable = "comments";
    $this->modelPlural = "comments";
    if ($id === 0) {
      $this->message = "";
      $this->parent = $parent;
      $this->parentId = $parent->id;
      $this->type = get_class($this->parent);
      $this->user = $user;
      $this->userId = $user->id;
    } else {
      $this->message = $this->userId = $this->parent = $this->parentId = $this->type = Null;
    }
    $this->depth = $this->ancestor = Null;
  }
  public function userId() {
    return $this->returnInfo('userId');
  }
  public function user() {
    if ($this->user === Null) {
      $this->user = new User($this->dbConn, $this->userId());
    }
    return $this->user;
  }
  public function depth() {
    if ($this->depth === Null) {
      $this->depth = method_exists($this->parent(), 'depth') ? $this->parent()->depth() + 1 : ($this->parent->modelName() == "User" ? 0 : 1);
    }
    return $this->depth;
  }
  public function ancestor() {
    if ($this->ancestor === Null) {
      $this->ancestor = method_exists($this->parent(), 'parent') && $this->parent()->type() != "User" && $this->parent()->type() != "Anime" ? $this->parent()->ancestor() : $this->parent();
    }
    return $this->ancestor;
  }
  public function type() {
    return $this->returnInfo('type');
  }
  public function parentId() {
    return $this->returnInfo('parentId');
  }
  public function parent() {
    if ($this->parent === Null) {
      $type = $this->type();
      $this->parent = new $type($this->dbConn, $this->parentId());
    }
    return $this->parent;
  }
  public function message() {
    return $this->returnInfo('message');
  }
  public function getEntries() {
    // retrieves a list of id arrays corresponding to the comments belonging to this comment.
    $returnList = [];
    $commentEntries = $this->dbConn->stdQuery("SELECT * FROM `comments` WHERE `type` = 'Comment' && `parent_id` = ".intval($this->id)." ORDER BY `time` ASC");
    while ($entry = $commentEntries->fetch_assoc()) {
      $newEntry = new CommentEntry($this->dbConn, intval($entry['id']), $entry);
      $returnList[intval($entry['id'])] = $newEntry;
    }
    return $returnList;
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'edit':
        // if this user is the owner of the comment, allow them to edit this comment.
        if ($authingUser->id === $this->user()->id || $authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'delete':
        // if this user is the owner, or if they're the owner of the thing that the comment is posted on, allow them to delete this comment.
        if ($authingUser->id === $this->user()->id || $this->parent()->allow($authingUser, $action) || $authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'new':
        // if user is logged in and this user has perms to comment on the parent, allow them to post a new comment.
        if ($authingUser->loggedIn() && $this->parent()->allow($authingUser, 'comment')) {
          return True;
        }
        return False;
        break;
      case 'show':
      case 'index':
        // if this user can view the parent object, allow them to view comments belonging to this object.
        if ($this->parent()->allow($authingUser, 'show')) {
          return True;
        }
        return False;
        break;
      default:
        return False;
        break;
    }
  }
  public function validate(array $comment) {
    if (!parent::validate($comment)) {
      return False;
    }
    if (!isset($comment['user_id'])) {
      return False;
    }
    if (!is_numeric($comment['user_id']) || intval($comment['user_id']) != $comment['user_id'] || intval($comment['user_id']) <= 0) {
      return False;
    } else {
      try {
        $createdUser = new User($this->dbConn, intval($comment['user_id']));
      } catch (Exception $e) {
        return False;
      }
    }
    if (!isset($comment['type']) || !isset($comment['parent_id'])) {
      return False;
    }
    if (!is_numeric($comment['parent_id']) || intval($comment['parent_id']) != $comment['parent_id'] || intval($comment['parent_id']) <= 0) {
      return False;
    } else {
      try {
        $parent = new $comment['type']($this->dbConn, intval($comment['parent_id']));
      } catch (Exception $e) {
        return False;
      }
    }
    if (isset($comment['message']) && strlen($comment['message']) < 1 || strlen($comment['message']) > 300) {
      return False;
    }
    return True;
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