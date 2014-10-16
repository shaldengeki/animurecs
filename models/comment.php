<?php
class Comment extends BaseObject {
  use Feedable;

  public static $TABLE = "comments";
  public static $PLURAL = "comments";
  public static $FIELDS = [
    'id' => [
      'type' => 'int',
      'db' => 'id'
    ],
    'userId' => [
      'type' => 'int',
      'db' => 'user_id'
    ],
    'type' => [
      'type' => 'str',
      'db' => 'type'
    ],
    'parentId' => [
      'type' => 'int',
      'db' => 'parent_id'
    ],
    'message' => [
      'type' => 'str',
      'db' => 'message'
    ],
    'createdAt' => [
      'type' => 'date',
      'db' => 'created_at'
    ],
    'updatedAt' => [
      'type' => 'date',
      'db' => 'updated_at'
    ]
  ];
  public static $JOINS = [
    'user' => [
      'obj' => 'User',
      'table' => 'users',
      'own_col' => 'user_id',
      'join_col' => 'id',
      'type' => 'one'
    ],
    'entries' => [
      'obj' => 'CommentEntry',
      'table' => 'comments',
      'own_col' => 'id',
      'join_col' => 'parent_id',
      'condition' => "comments.type = 'CommentEntry'",
      'type' => 'many'
    ]
  ];
  public $parent;
  public $ancestor;
  public $depth;

  public $entries;

  public function __construct(Application $app, $id=Null, User $user=Null, BaseObject $parent=Null) {
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->message = "";
      $this->parent = $parent;
      $this->parentId = ($parent !== Null) ? $parent->id : Null;
      $this->type = ($parent !== Null) ? get_class($this->parent) : Null;
      $this->user = $user;
      $this->userId = ($user !== Null) ? $user->id : Null;
    }
  }
  public function depth() {
    if ($this->depth === Null) {
      $parentClass = get_class($this->parent());
      $this->depth = method_exists($this->parent(), 'depth') ? $this->parent()->depth() + 1 : ($parentClass::MODEL_NAME() == "User" ? 0 : 1);
    }
    return $this->depth;
  }
  public function ancestor() {
    if ($this->ancestor === Null) {
      $this->ancestor = method_exists($this->parent(), 'parent') && $this->parent()->type != "User" && $this->parent()->type != "Anime" ? $this->parent()->ancestor() : $this->parent();
    }
    return $this->ancestor;
  }
  public function parent() {
    if ($this->parent === Null) {
      $type = $this->type;
      $this->parent = new $type($this->app, $this->parentId);
    }
    return $this->parent;
  }
  public function getEntries() {
    // retrieves a list of id arrays corresponding to the comments belonging to this comment.
    $returnList = [];
    $commentEntries = $this->app->dbConn->table(static::$TABLE)
                        ->where(["type='Comment'", 'parent_id' => $this->id])
                        ->order('time ASC')
                        ->query();
    while ($entry = $commentEntries->fetch()) {
      $newEntry = new CommentEntry($this->app, intval($entry['id']), $entry);
      $returnList[intval($entry['id'])] = $newEntry;
    }
    return $returnList;
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'edit':
        // if this user is the owner of the comment, allow them to edit this comment.
        if ($authingUser->id === $this->user->id || $authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'delete':
        // if this user is the owner, or if they're the owner of the thing that the comment is posted on, allow them to delete this comment.
        if ($authingUser->id === $this->user->id || $this->parent()->allow($authingUser, $action) || $authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'new':
        // if user is logged in and this user has perms to comment on the parent, allow them to post a new comment.
        if ($authingUser->loggedIn() && ($this->parent === Null || $this->parent()->allow($authingUser, 'comment'))) {
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
    $validationErrors = [];
    try {
      parent::validate($comment);
    } catch (ValidationException $e) {
      $validationErrors = array_merge($validationErrors, $e->messages);
    }
    if (!isset($comment['user_id']) || !is_integral($comment['user_id']) || intval($comment['user_id']) <= 0) {
      $validationErrors[] = "User ID must be valid";
    }else {
      try {
        $createdUser = new User($this->app, intval($comment['user_id']));
      } catch (DbException $e) {
        $validationErrors[] = "User must exist";
      }
    }
    if (!isset($comment['parent_id']) || !is_integral($comment['parent_id']) || intval($comment['parent_id']) <= 0) {
      $validationErrors[] = "Parent ID must be valid";
    } else {
      try {
        $parent = new $comment['type']($this->app, intval($comment['parent_id']));
        $parent->load();
      } catch (DbException $e) {
        $validationErrors[] = "Parent must be valid";
      }
    }
    if (!isset($comment['message']) || mb_strlen($comment['message']) < 1 || mb_strlen($comment['message']) > 300) {
      $validationErrors[] = "Message must be between 1 and 300 characters long";
    }
    if ($validationErrors) {
      throw new ValidationException($this->app, $comment, $validationErrors);
    } else {
      return True;
    }
  }
  public function url($action="show", $format=Null, array $params=Null, $id=Null) {
    // returns the url that maps to this comment and the given action.
    // if we're showing this comment, show its parent instead.
    if ($action == "show") {
      return $this->parent()->url($action, $format, $params);
    }
    if ($id === Null) {
      $id = intval($this->id);
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output(static::$TABLE)."/".($action !== "index" ? intval($id)."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
  public function render() {
    if ($this->app->id != 0) {
      try {
        $this->load();
      } catch (DbException $e) {
        $this->app->log_exception($e);
        $this->app->display_error(404, "No such comment found.");
      }
      $targetParent = $this->parent();
      $targetUser = $this->user;
    } elseif ($this->app->action === 'new') {
      $type = isset($_POST['comments']['type']) ? $_POST['comments']['type'] : (isset($_REQUEST['type']) ? $_REQUEST['type'] : Null);
      try {
        $targetParent = $type !== Null && (isset($_POST['comments']['parent_id']) || isset($_REQUEST['parent_id'])) ? new $type($this->app, intval(isset($_POST['comments']['parent_id']) ? $_POST['comments']['parent_id'] : $_REQUEST['parent_id'])) : Null;
        if ($targetParent !== Null) {
          $targetParent->load();
        }
      } catch (DbException $e) {
        $this->app->log_exception($e);
        $this->app->display_error(404, "The thing you're commenting on no longer exists.");
      }

      if (intval($_REQUEST['user_id']) === $this->app->user->id || intval($_POST['user_id']) === $this->app->user->id) {
        $targetUser = $this->app->user;
      } else {
        try {
          $targetUser = new User($this->app, isset($_POST['comments']['user_id']) ? intval($_POST['comments']['user_id']) : intval($_REQUEST['user_id']));
          $targetUser->load();
        } catch (DbException $e) {
          $this->app->log_exception($e);
          $this->app->display_error(404, "This user ID doesn't exist.");
        }
      }
    }
    try {
      $targetComment = new Comment($this->app, intval($this->app->id), $targetUser, $targetParent);
      if ($targetComment->id !== 0) {
        $targetComment->load();
      }
    } catch (DbException $e) {
      $targetComment = new Comment($this->app, 0, $targetUser, $targetParent);
    }
    switch($this->app->action) {
      case 'new':
        if (isset($_POST['comments']) && is_array($_POST['comments']) && isset($_POST['comments']['type']) && isset($_POST['comments']['parent_id']) && is_numeric($_POST['comments']['parent_id'])) {
          // ensure that the thing to which this comment is going to belong exists.
          if ($targetParent === Null) {
            $this->app->display_error(404, "The thing you're commenting on no longer exists.");
          }

          // ensure that the user has perms to create a comment for this user under this object.
          if (($targetUser->id != $this->app->user->id && !$this->app->user->isModerator() && !$this->app->user->isAdmin()) || !$targetComment->allow($this->app->user, 'new')) {
            $this->app->display_error(403, "You're not allowed to comment on this.");
          }
          $createComment = $targetComment->create_or_update($_POST['comments']);
          if ($createComment) {
            $this->app->display_success(200, "Succesfully commented.");
          } else {
            $this->app->display_error(500, "An error occurred while commenting on this.");
          }
        }
        $this->app->display_error(400, "You must submit a comment to post.");
        break;
      case 'edit':
        if ($targetComment->id == 0) {
          $this->app->display_error(404, "No such comment found.");
        }
        if (isset($_POST['comments']) && is_array($_POST['comments'])) {
          // ensure that the thing to which this comment belongs exists.
          $commentType = !isset($_POST['comments']['type']) ? $targetComment->type : $_POST['comments']['type'];
          $commentParentID = !isset($_POST['comments']['parent_id']) ? $targetComment->parent->id : $_POST['comments']['parent_id'];
          try {
            $targetParent = new $commentType($this->app, intval($commentParentID));
          } catch (Exception $e) {
            $this->app->display_error(404, "The thing you're commenting on no longer exists.");
          }
          if ($targetParent->id === 0) {
            $this->app->display_error(400, "Please provide something to comment on.");
          }

          // ensure that the user has perms to update a comment.
          try {
            $targetComment = new Comment($this->app, $this->app->id);
          } catch (Exception $e) {
            // this non-zero commentID does not exist.
            $this->app->display_error(404, "No such comment found.");
          }
          if (($targetUser->id != $this->app->user->id && !$this->app->user->isModerator() && !$this->app->user->isAdmin()) || !$targetComment->allow($this->app->user, 'edit')) {
            $this->app->display_error(403, "You're not allowed to comment on this.");
          }
          $updateComment = $targetComment->create_or_update($_POST['comments']);
          if ($updateComment) {
            $this->app->display_success(200, "Comment successfully updated.");
          } else {
            $this->app->display_error(500, "An error occurred while updating this comment.");
          }
        }
        $this->app->display_error(400, "You must submit a comment to edit.");
        break;
      case 'show':
        if ($targetComment->id == 0) {
          $this->app->display_error(404, "No such comment found.");
        }
        $this->app->display_response(200, $targetComment->serialize());
        break;
      case 'delete':
        if ($targetComment->id == 0) {
          $this->app->display_error(403, "You're not allowed to comment on this.");
        }
        $deleteComment = $targetComment->delete();
        if ($deleteComment) {
          $this->app->display_success(200, "Comment successfully deleted.");
        } else {
          $this->app->display_error(500, "An error occurred while deleting this comment.");
        }
        break;
      default:
      case 'index':
        $this->app->display_response(200, array_map(function ($c) {
          return $c->serialize();
        }, Comment::GetList($this->app)));
        break;
    }
    return;
  }
}
?>