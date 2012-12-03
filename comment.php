<?php
include_once("global/includes.php");

if (isset($_REQUEST['id']) && intval($_REQUEST['id']) != 0) {
  try {
    $targetComment = new Comment($database, intval($_REQUEST['id']));
  } catch (Exception $e) {
    redirect_to('/feed.php', array('status' => 'This comment does not exist.', 'class' => 'error'));
  }
  $targetParent = $targetComment->parent;
  $targetUser = $targetComment->user;
} else {
  $type = isset($_POST['comment']['type']) ? $_POST['comment']['type'] : (isset($_REQUEST['type']) ? $_REQUEST['type'] : Null);
  try {
    $targetParent = $type !== Null && (isset($_POST['comment']['parent_id']) || isset($_REQUEST['parent_id'])) ? new $type($database, intval(isset($_POST['comment']['parent_id']) ? $_POST['comment']['parent_id'] : $_REQUEST['parent_id'])) : Null;
  } catch (Exception $e) {
    redirect_to($user->url(), array('status' => "The thing you're commenting on no longer exists.", 'class' => 'error'));
  }

  if (intval($_REQUEST['user_id']) === $user->id || intval($_POST['user_id']) === $user->id) {
    $targetUser = $user;
  } else {
    try {
      $targetUser = new User($database, isset($_POST['comment']['user_id']) ? intval($_POST['comment']['user_id']) : intval($_REQUEST['user_id']));
    } catch (Exception $e) {
      redirect_to($user->url(), array('status' => "This user ID doesn't exist.", 'class' => 'error'));
    }
  }
  try {
    $targetComment = new Comment($database, intval($_REQUEST['id']), $targetUser, $targetParent);
  } catch (Exception $e) {
    $targetComment = new Comment($database, 0, $targetUser, $targetParent);
  }
}

if (!$targetComment->allow($user, $_REQUEST['action'])) {
  // TODO: make this kawaiier
  $title = "Error: Insufficient privileges";
  $output = display_error("Error: Insufficient privileges", "You're not allowed to do this.");
} else {
  switch($_REQUEST['action']) {
    case 'new':
      if (isset($_POST['comment']) && is_array($_POST['comment']) && isset($_POST['comment']['type']) && isset($_POST['comment']['parent_id']) && is_numeric($_POST['comment']['parent_id'])) {
        // ensure that the thing to which this comment is going to belong exists.
        try {
          $targetParent = new $_POST['comment']['type']($database, intval($_POST['comment']['parent_id']));
        } catch (Exception $e) {
          redirect_to('/feed.php', array('status' => "The thing you're trying to comment on doesn't exist anymore.", 'class' => 'error'));
        }
        if ($targetParent->id === 0) {
          redirect_to($user->url(), array('status' => "Please provide something to comment on.", 'class' => 'error'));
        }

        // ensure that the user has perms to create a comment for this user under this object.
        $targetComment = new Comment($database, 0, $targetUser, $targetParent);
        if (($targetUser->id != $user->id && !$user->isModerator() && !$user->isAdmin()) || !$targetComment->allow($user, 'new')) {
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
      $output = "<h1>Add a comment</h1>\n";
      $output .= $targetComment->form($user, $targetParent);
      break;
    case 'edit':
      if ($targetComment->id == 0) {
        $output = display_error("Error: Invalid comment", "The given comment doesn't exist.");
        break;
      }
      if (isset($_POST['comment']) && is_array($_POST['comment'])) {
        // ensure that the thing to which this comment belongs exists.
        $commentType = !isset($_POST['comment']['type']) ? $targetComment->type : $_POST['comment']['type'];
        $commentParentID = !isset($_POST['comment']['parent_id']) ? $targetComment->parent->id : $_POST['comment']['parent_id'];
        try {
          $targetParent = new $commentType($database, intval($commentParentID));
        } catch (Exception $e) {
          redirect_to('/feed.php', array('status' => "The thing you're trying to comment on doesn't exist anymore.", 'class' => 'error'));
        }
        if ($targetParent->id === 0) {
          redirect_to($user->url(), array('status' => "Please provide something to comment on.", 'class' => 'error'));
        }

        // ensure that the user has perms to update a comment.
        try {
          $targetComment = new Comment($database, intval($_POST['comment']['id']));
        } catch (Exception $e) {
          // this non-zero commentID does not exist.
          redirect_to($targetParent->url(), array('status' => 'This comment does not exist.', 'class' => 'error'));
        }
        if (($targetUser->id != $user->id && !$user->isModerator() && !$user->isAdmin()) || !$targetComment->allow($user, 'edit')) {
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
      $output = "<h1>Editing comment</h1>\n";
      $output .= $targetComment->form($user, $targetParent);
      break;
    case 'show':
      if ($targetComment->id == 0) {
        $output = display_error("Error: Invalid comment", "The given comment doesn't exist.");
        break;
      }
      $title = "Showing comment";
      $output = $targetComment->profile($user);
      break;
    case 'delete':
      if ($targetComment->id == 0) {
        $output = display_error("Error: Invalid comment", "The given comment doesn't exist.");
        break;
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
      $output = "<h1>All Comments</h1>\n".display_comments($database, $user);
      break;
  }
}
start_html($database, $user, "Animurecs", $title, $_REQUEST['status'], $_REQUEST['class']);
echo $output;
display_footer();
?>