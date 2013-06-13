<?php
class Comment extends BaseObject {
  use Feedable;

  public static $modelTable = "comments";
  public static $modelPlural = "comments";

  protected $userId;
  protected $user;
  protected $type;
  protected $parentId;
  protected $parent;
  protected $ancestor;
  protected $depth;
  protected $message;

  protected $entries;

  public function __construct(Application $app, $id=Null, User $user=Null, BaseObject $parent=Null) {
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->message = "";
      $this->parent = $parent;
      $this->parentId = ($parent !== Null) ? $parent->id : Null;
      $this->type = ($parent !== Null) ? get_class($this->parent) : Null;
      $this->user = $user;
      $this->userId = ($user !== Null) ? $user->id : Null;
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
      $this->user = new User($this->app, $this->userId());
    }
    return $this->user;
  }
  public function depth() {
    if ($this->depth === Null) {
      $parentClass = get_class($this->parent());
      $this->depth = method_exists($this->parent(), 'depth') ? $this->parent()->depth() + 1 : ($parentClass::modelName() == "User" ? 0 : 1);
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
      $this->parent = new $type($this->app, $this->parentId());
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
        $createdUser = new User($this->app, intval($comment['user_id']));
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
        $parent = new $comment['type']($this->app, intval($comment['parent_id']));
      } catch (Exception $e) {
        return False;
      }
    }
    if (isset($comment['message']) && strlen($comment['message']) < 1 || strlen($comment['message']) > 300) {
      return False;
    }
    return True;
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
    return "/".escape_output(static::$modelTable)."/".($action !== "index" ? intval($id)."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
  public function render() {
    if ($this->app->id != 0) {
      try {
        $this->getInfo();
      } catch (DbException $e) {
        $this->app->logger->err($e->__toString());
        $this->app->delayedMessage('This comment does not exist.', 'error');
        $this->app->redirect($this->app->user->url());
      }
      $targetParent = $this->parent();
      $targetUser = $this->user();
    } else {
      $type = isset($_POST['comments']['type']) ? $_POST['comments']['type'] : (isset($_REQUEST['type']) ? $_REQUEST['type'] : Null);
      try {
        $targetParent = $type !== Null && (isset($_POST['comments']['parent_id']) || isset($_REQUEST['parent_id'])) ? new $type($this->app, intval(isset($_POST['comments']['parent_id']) ? $_POST['comments']['parent_id'] : $_REQUEST['parent_id'])) : Null;
        if ($targetParent !== Null) {
          $targetParent->getInfo();
        }
      } catch (DbException $e) {
        $this->app->logger->err($e->__toString());
        $this->app->delayedMessage("The thing you're commenting on no longer exists.", 'error');
        $this->app->redirect($this->app->user->url());
      }

      if (intval($_REQUEST['user_id']) === $this->app->user->id || intval($_POST['user_id']) === $this->app->user->id) {
        $targetUser = $this->app->user;
      } else {
        try {
          $targetUser = new User($this->app, isset($_POST['comments']['user_id']) ? intval($_POST['comments']['user_id']) : intval($_REQUEST['user_id']));
          $targetUser->getInfo();
        } catch (DbException $e) {
          $this->app->logger->err($e->__toString());
          $this->app->delayedMessage("This user ID doesn't exist.", 'error');
          $this->app->redirect($this->app->user->url());
        }
      }
    }
    try {
      $targetComment = new Comment($this->app, intval($this->app->id), $targetUser, $targetParent);
      if ($targetComment->id !== 0) {
        $targetComment->getInfo();
      }
    } catch (DbException $e) {
      $targetComment = new Comment($this->app, 0, $targetUser, $targetParent);
    }
    switch($this->app->action) {
      case 'new':
        if (isset($_POST['comments']) && is_array($_POST['comments']) && isset($_POST['comments']['type']) && isset($_POST['comments']['parent_id']) && is_numeric($_POST['comments']['parent_id'])) {
          // ensure that the thing to which this comment is going to belong exists.
          if ($targetParent === Null) {
            $this->app->delayedMessage("The thing you're commenting on no longer exists.", 'error');
            $this->app->redirect($this->app->user->url());
          }

          // ensure that the user has perms to create a comment for this user under this object.
          if (($targetUser->id != $this->app->user->id && !$this->app->user->isModerator() && !$this->app->user->isAdmin()) || !$targetComment->allow($this->app->user, 'new')) {
            $this->app->delayedMessage("You're not allowed to comment on this.", 'error');
            $this->app->redirect($targetParent->url());
          }
          $createComment = $targetComment->create_or_update($_POST['comments']);
          if ($createComment) {
            $this->app->delayedMessage("Succesfully commented.", 'success');
            $this->app->redirect($targetParent->url());
          } else {
            $this->app->delayedMessage("An error occurred while commenting on this.", 'error');
            $this->app->redirect($targetParent->url());
          }
        }
        $title = "Add a comment";
        $output .= $targetComment->view('new', ['currentObject' => $targetParent]);
        break;
      case 'edit':
        if ($targetComment->id == 0) {
          $this->app->display_error(404);
        }
        if (isset($_POST['comments']) && is_array($_POST['comments'])) {
          // ensure that the thing to which this comment belongs exists.
          $commentType = !isset($_POST['comments']['type']) ? $targetComment->type : $_POST['comments']['type'];
          $commentParentID = !isset($_POST['comments']['parent_id']) ? $targetComment->parent->id : $_POST['comments']['parent_id'];
          try {
            $targetParent = new $commentType($this->app, intval($commentParentID));
          } catch (Exception $e) {
            $this->app->delayedMessage("The thing you're trying to comment on doesn't exist anymore.", 'error');
            $this->app->redirect($this->app->user->url());
          }
          if ($targetParent->id === 0) {
            $this->app->delayedMessage("Please provide something to comment on.", 'error');
            $this->app->redirect($this->app->user->url());
          }

          // ensure that the user has perms to update a comment.
          try {
            $targetComment = new Comment($this->app, $this->app->id);
          } catch (Exception $e) {
            // this non-zero commentID does not exist.
            $this->app->delayedMessage('This comment does not exist.', 'error');
            $this->app->redirect($targetParent->url());
          }
          if (($targetUser->id != $this->app->user->id && !$this->app->user->isModerator() && !$this->app->user->isAdmin()) || !$targetComment->allow($this->app->user, 'edit')) {
            $this->app->delayedMessage("You're not allowed to comment on this.", 'error');
            $this->app->redirect($targetParent->url());
          }
          $updateComment = $targetComment->create_or_update($_POST['comments']);
          if ($updateComment) {
            $this->app->delayedMessage("Comment successfully updated.", 'success');
            $this->app->redirect($targetParent->url());
          } else {
            $this->app->delayedMessage("An error occurred while creating or updating this comment.", 'error');
            $this->app->redirect($targetParent->url());
          }
        }
        $title = "Editing comment";
        $output = $targetComment->view('edit', ['currentObject' => $targetParent]);
        break;
      case 'show':
        if ($targetComment->id == 0) {
          $this->app->display_error(404);
        }
        $title = "Showing comment";
        $output = $targetComment->view('show');
        break;
      case 'delete':
        if ($targetComment->id == 0) {
          $this->app->display_error(404);
        }
        $deleteComment = $targetComment->delete();
        if ($deleteComment) {
          $this->app->delayedMessage('Successfully deleted a comment.', 'success');
          $this->app->redirect($targetParent->url());
        } else {
          $this->app->delayedMessage("An error occurred while creating or updating this comment.", 'error');
          $this->app->redirect($targetParent->url());
        }
        break;
      default:
      case 'index':
        $title = "All Comments";
        $output = $this->view('index');
        break;
    }
    return $this->app->render($output, ['subtitle' => $title]);
  }
}
?>