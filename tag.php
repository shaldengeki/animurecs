<?php
include_once("global/includes.php");

if (isset($_POST['tag']) && is_array($_POST['tag'])) {
  // check to ensure that the user has perms to create or update an tag.
  try {
    $targetTag = new Tag($database, intval($_POST['tag']['id']));
  } catch (Exception $e) {
    // this non-zero tagID does not exist.
    redirect_to(array('location' => 'tag.php', 'status' => 'This tag ID does not exist.', 'class' => 'error'));
  }
  if ($targetTag->id === 0) {
    $authStatus = $targetTag->allow($user, 'new');
  } else {
    $authStatus = $targetTag->allow($user, 'edit');
  }
  if (!$authStatus) {
    redirect_to(array('location' => 'tag.php'.($targetTag->id === 0 ? "" : "?action=show&id=".intval($targetTag->id)), 'status' => "You're not allowed to do this.", 'class' => 'error'));
  }
  $updateTag = $targetTag->create_or_update($_POST['tag'], $user);
  if ($updateTag) {
    redirect_to(array('location' => 'tag.php?action=show&id='.intval($targetTag->id), 'status' => "Successfully updated.", 'class' => 'success'));
  } else {
    redirect_to(array('location' => 'tag.php'.($targetTag->id === 0 ? "?action=new" : "?action=edit&id=".intval($targetTag->id)), 'status' => "An error occurred while creating or updating this tag.", 'class' => 'error'));
  }
}

try {
  $targetTag = new Tag($database, intval($_REQUEST['id']));
} catch (Exception $e) {
  $targetTag = new Tag($database, 0);
}

if (!$targetTag->allow($user, $_REQUEST['action'])) {
  $title = "Error: Insufficient privileges";
  $output = display_error("Error: Insufficient privileges", "You're not allowed to do this.");
} else {
  switch($_REQUEST['action']) {
    case 'new':
      $title = "Create a Tag";
      $output = "<h1>Add an tag</h1>\n";
      $output .= $targetTag->form($user);
      break;
    case 'edit':
      if ($targetTag->id == 0) {
        $output = display_error("Error: Invalid tag", "The given tag doesn't exist.");
        break;
      }
      $title = "Editing ".escape_output($targetTag->name);
      $output = "<h1>".escape_output($targetTag->name)."</h1>\n";
      $output .= $targetTag->form($user);
      break;
    case 'show':
      if ($targetTag->id == 0) {
        $output = display_error("Error: Invalid tag", "The given tag doesn't exist.");
        break;
      }
      $name = escape_output($targetTag->name);
      $output = "<h1>".escape_output($targetTag->name).($targetTag->allow($user, "edit") ? " <small>(".$targetTag->link("edit", "edit").")</small>" : "")."</h1>\n".$targetTag->profile();
      break;
    case 'delete':
      if ($targetTag->id == 0) {
        $output = display_error("Error: Invalid tag", "The given tag doesn't exist.");
        break;
      }
      $deleteTag = $targetTag->delete();
      if ($deleteTag === True) {
        redirect_to(array('location' => 'tag.php?action=index', 'status' => 'Successfully deleted '.urlencode($targetTag->name).'.', 'class' => 'success'));
      } else {
        redirect_to(array('location' => 'tag.php?action=show&id='.intval($targetTag->id), 'status' => 'An error occurred while deleting '.urlencode($targetTag->name).'.', 'class' => 'error'));
      }
      break;
    default:
    case 'index':
      $title = "All Tags";
      $output = "<h1>All Tags</h1>\n".display_tags($database, $user);
      break;
  }
}
start_html($database, $user, "Animurecs", $title, $_REQUEST['status'], $_REQUEST['class']);
echo $output;
display_footer();
?>