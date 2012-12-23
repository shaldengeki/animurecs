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

  public function __construct(Application $app, $id=Null, User $user=Null, BaseObject $parent=Null) {
    parent::__construct($app, $id);
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
      $this->user = new User($this->app, $this->userId());
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
  public function render() {
    if ($this->app->id != 0) {
      try {
        $this->getInfo();
      } catch (Exception $e) {
        redirect_to('/feed.php', array('status' => 'This comment does not exist.', 'class' => 'error'));
      }
      $targetParent = $this->parent();
      $targetUser = $this->user();
    } else {
      $type = isset($_POST['comment']['type']) ? $_POST['comment']['type'] : (isset($_REQUEST['type']) ? $_REQUEST['type'] : Null);
      try {
        $targetParent = $type !== Null && (isset($_POST['comment']['parent_id']) || isset($_REQUEST['parent_id'])) ? new $type($this->app, intval(isset($_POST['comment']['parent_id']) ? $_POST['comment']['parent_id'] : $_REQUEST['parent_id'])) : Null;
        if ($targetParent !== Null) {
          $targetParent->getInfo();
        }
      } catch (Exception $e) {
        redirect_to($this->app->user->url(), array('status' => "The thing you're commenting on no longer exists.", 'class' => 'error'));
      }

      if (intval($_REQUEST['user_id']) === $this->app->user->id || intval($_POST['user_id']) === $this->app->user->id) {
        $targetUser = $this->app->user;
      } else {
        try {
          $targetUser = new User($this->app, isset($_POST['comment']['user_id']) ? intval($_POST['comment']['user_id']) : intval($_REQUEST['user_id']));
          $targetUser->getInfo();
        } catch (Exception $e) {
          redirect_to($this->app->user->url(), array('status' => "This user ID doesn't exist.", 'class' => 'error'));
        }
      }
    }
    try {
      $targetComment = new Comment($this->app, intval($this->app->id), $targetUser, $targetParent);
      if ($targetComment->id !== 0) {
        $targetComment->getInfo();
      }
    } catch (Exception $e) {
      $targetComment = new Comment($this->app, 0, $targetUser, $targetParent);
    }
    switch($this->app->action) {
      case 'new':
        if (isset($_POST['comment']) && is_array($_POST['comment']) && isset($_POST['comment']['type']) && isset($_POST['comment']['parent_id']) && is_numeric($_POST['comment']['parent_id'])) {
          // ensure that the thing to which this comment is going to belong exists.
          if ($targetParent === Null) {
            redirect_to($this->app->user->url(), array('status' => "The thing you're commenting on no longer exists.", 'class' => 'error'));
          }

          // ensure that the user has perms to create a comment for this user under this object.
          if (($targetUser->id != $this->app->user->id && !$this->app->user->isModerator() && !$this->app->user->isAdmin()) || !$targetComment->allow($this->app->user, 'new')) {
            redirect_to($targetParent->url(), array('status' => "You're not allowed to comment on this.", 'class' => 'error'));
          }
          $createComment = $targetComment->create_or_update($_POST['comment']);
          if ($createComment) {
            redirect_to($targetParent->url(), array('status' => "Succesfully commented.", 'class' => 'success'));
          } else {
            redirect_to($targetParent->url(), array('status' => "An error occurred while commenting on this.", 'class' => 'error'));
          }
        }
        $title = "Add a comment";
        $output .= $targetComment->view('new', array('currentObject' => $targetParent));
        break;
      case 'edit':
        if ($targetComment->id == 0) {
          $this->app->display_error(404);
        }
        if (isset($_POST['comment']) && is_array($_POST['comment'])) {
          // ensure that the thing to which this comment belongs exists.
          $commentType = !isset($_POST['comment']['type']) ? $targetComment->type : $_POST['comment']['type'];
          $commentParentID = !isset($_POST['comment']['parent_id']) ? $targetComment->parent->id : $_POST['comment']['parent_id'];
          try {
            $targetParent = new $commentType($this->app, intval($commentParentID));
          } catch (Exception $e) {
            redirect_to('/feed.php', array('status' => "The thing you're trying to comment on doesn't exist anymore.", 'class' => 'error'));
          }
          if ($targetParent->id === 0) {
            redirect_to($this->app->user->url(), array('status' => "Please provide something to comment on.", 'class' => 'error'));
          }

          // ensure that the user has perms to update a comment.
          try {
            $targetComment = new Comment($this->app, $this->app->id);
          } catch (Exception $e) {
            // this non-zero commentID does not exist.
            redirect_to($targetParent->url(), array('status' => 'This comment does not exist.', 'class' => 'error'));
          }
          if (($targetUser->id != $this->app->user->id && !$this->app->user->isModerator() && !$this->app->user->isAdmin()) || !$targetComment->allow($this->app->user, 'edit')) {
            redirect_to($targetParent->url(), array('status' => "You're not allowed to comment on this.", 'class' => 'error'));
          }
          $updateComment = $targetComment->create_or_update($_POST['comment']);
          if ($updateComment) {
            redirect_to($targetParent->url(), array('status' => "Comment successfully updated.", 'class' => 'success'));
          } else {
            redirect_to($targetParent->url(), array('status' => "An error occurred while creating or updating this comment.", 'class' => 'error'));
          }
        }
        $title = "Editing comment";
        $output = $targetComment->view('edit', array('currentObject' => $targetParent));
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
          redirect_to($targetParent->url(), array('status' => 'Successfully deleted a comment.', 'class' => 'success'));
        } else {
          redirect_to($targetParent->url(), array('status' => 'An error occurred while deleting a comment.', 'class' => 'error'));
        }
        break;
      default:
      case 'index':
        $title = "All Comments";
        $output = $this->view('index');
        break;
    }
    $this->app->render($output, array('title' => $title));
  }
}
?>