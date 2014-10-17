<?php

class CommentsController extends Controller {
  public static $URL_BASE = "comments";
  public static $MODEL = "Comment";

  public function _isAuthorized($action) {
    // takes a user object and an action and returns a bool.
    if ($this->_target->id !== 0) {
      $parentClass = get_class($this->_target->parent());
      $parentController = $this->_app->modelControllers($parentClass)[0];
    }
    switch($action) {
      case 'edit':
        // if this user is the owner of the comment, allow them to edit this comment.
        if (($this->_app->user->id === $this->_target->user->id && $this->_app->user->id === intval($_POST['comments']['user_id'])) || $this->_app->user->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'delete':
        // if this user is the owner, or if they're the owner of the thing that the comment is posted on, allow them to delete this comment.
        if ($this->_app->user->id === $this->_target->user->id || $parentController->_isAuthorized($action) || $this->_app->user->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'index':
        if (isset($_POST['comments']) && is_array($_POST['comments'])) {
          // if user is logged in and this user has perms to comment on the parent, allow them to post a new comment.
          if ($this->_app->user->loggedIn() && $this->_app->user->id === intval($_POST['comments']['user_id']) && $parentController->_isAuthorized('comment')) {
            return True;
          }
          return False;
        }
        if ($this->_app->user->isStaff()) {
          return True;
        }
        return False;
        break;

      case 'show':
        // if this user can view the parent object, allow them to view comments belonging to this object.
        if ($parentController->_isAuthorized('show')) {
          return True;
        }
        return False;
        break;
      default:
        return False;
        break;
    }
  }

  public function delete() {
    if ($this->_target->id === 0) {
      $this->_app->display_error(403, "You're not allowed to comment on this.");
    }
    $deleteComment = $this->_target->delete();
    if ($deleteComment) {
      $this->_app->display_success(200, "Comment successfully deleted.");
    } else {
      $this->_app->display_error(500, "An error occurred while deleting this comment.");
    }
  }
  public function edit() {
    if ($this->_target->id === 0) {
      $this->_app->display_error(404, "No such comment found.");
    }
    if (isset($_POST['comments']) && is_array($_POST['comments'])) {
      // ensure that the thing to which this comment belongs exists.
      $commentType = !isset($_POST['comments']['type']) ? $this->_target->type : $_POST['comments']['type'];
      $commentParentID = !isset($_POST['comments']['parent_id']) ? $this->_target->parent->id : $_POST['comments']['parent_id'];
      try {
        $targetParent = $commentType::FindById($this->_app, intval($commentParentID));
      } catch (NoDatabaseRowsRetrievedException $e) {
        $this->_app->display_error(404, "The thing you're commenting on no longer exists.");
      }
      if ($targetParent->id === 0) {
        $this->_app->display_error(400, "Please provide something to comment on.");
      }

      // ensure that the user to which this comment is going to belong exists.
      try {
        $targetUser = User::FindById($this->_app, intval($_POST['comments']['user_id']));
      } catch (NoDatabaseRowsRetrievedException $e) {
        $this->_app->display_error(404, "The user provided doesn't exist.");
      }

      // ensure that the user has perms to update a comment.
      try {
        $this->_target = new Comment($this->_app, $this->_app->id);
      } catch (NoDatabaseRowsRetrievedException $e) {
        // this non-zero commentID does not exist.
        $this->_app->display_error(404, "No such comment found.");
      }
      $updateComment = $this->_target->create_or_update($_POST['comments']);
      if ($updateComment) {
        $this->_app->display_success(200, "Comment successfully updated.");
      } else {
        $this->_app->display_error(500, "An error occurred while updating this comment.");
      }
    }
    $this->_app->display_error(400, "You must submit a comment to edit.");
  }
  public function index() {
    if (isset($_POST['comments']) && is_array($_POST['comments'])) {
      if (isset($_POST['comments']['type']) && isset($_POST['comments']['parent_id']) && is_numeric($_POST['comments']['parent_id'])) {
        // ensure that the thing to which this comment is going to belong exists.
        try {
          $targetParent = $_POST['comments']['type']::FindById($this->_app, intval($_POST['comments']['parent_id']));
        } catch (NoDatabaseRowsRetrievedException $e) {
          $this->_app->display_error(404, "The thing you're commenting on no longer exists.");
        }

        // ensure that the user to which this comment is going to belong exists.
        try {
          $targetUser = User::FindById($this->_app, intval($_POST['comments']['user_id']));
        } catch (NoDatabaseRowsRetrievedException $e) {
          $this->_app->display_error(404, "The user provided doesn't exist.");
        }

        $createComment = $this->_target->create_or_update($_POST['comments']);
        if ($createComment) {
          $this->_app->display_success(200, "Succesfully commented.");
        } else {
          $this->_app->display_error(500, "An error occurred while commenting on this.");
        }
      }
      $this->_app->display_error(400, "You must submit a comment to post.");
    }
    $this->_app->display_response(200, array_map(function ($c) {
      return $c->serialize();
    }, Comment::GetList($this->_app)));
  }
  public function show() {
    if ($this->_target->id == 0) {
      $this->_app->display_error(404, "No such comment found.");
    }
    $this->_app->display_response(200, $this->_target->serialize());
  }

}
?>