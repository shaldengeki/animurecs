<?php
include_once("global/includes.php");

if (isset($_POST['tag_type']) && is_array($_POST['tag_type'])) {
  // check to ensure that the user has perms to create or update an tag type.
  try {
    $targetTagType = new TagType($database, intval($_POST['tag_type']['id']));
  } catch (Exception $e) {
    // this non-zero tag_typeID does not exist.
    redirect_to("/tag_types/", array('status' => 'This tag type does not exist.', 'class' => 'error'));
  }
  if ($targetTagType->id === 0) {
    $authStatus = $targetTagType->allow($user, 'new');
  } else {
    $authStatus = $targetTagType->allow($user, 'edit');
  }
  if (!$authStatus) {
    redirect_to(($targetTagType->id === 0 ? $targetTagType->url("index") : $targetTagType->url("show")), array('status' => "You're not allowed to do this.", 'class' => 'error'));
  }
  $updateTagType = $targetTagType->create_or_update($_POST['tag_type']);
  if ($updateTagType) {
    redirect_to($targetTagType->url("show"), array('status' => "Successfully updated.", 'class' => 'success'));
  } else {
    redirect_to(($targetTagType->id === 0 ? $targetTagType->url("new") : $targetTagType->url("edit")), array('status' => "An error occurred while creating or updating this tag type.", 'class' => 'error'));
  }
}

try {
  $targetTagType = new TagType($database, intval($_REQUEST['id']));
} catch (Exception $e) {
  $targetTagType = new TagType($database, 0);
}

if (!$targetTagType->allow($user, $_REQUEST['action'])) {
  $title = "Error: Insufficient privileges";
  $output = display_error("Error: Insufficient privileges", "You're not allowed to do this.");
} else {
  switch($_REQUEST['action']) {
    case 'new':
      $title = "Create a Tag Type";
      $output = "<h1>Add a tag type</h1>\n";
      $output .= $targetTagType->form($user);
      break;
    case 'edit':
      if ($targetTagType->id == 0) {
        $output = display_error("Error: Invalid tag type", "The given tag type doesn't exist.");
        break;
      }
      $title = "Editing ".escape_output($targetTagType->name);
      $output = "<h1>".escape_output($targetTagType->name)."</h1>\n";
      $output .= $targetTagType->form($user);
      break;
    case 'show':
      if ($targetTagType->id == 0) {
        $output = display_error("Error: Invalid tag type", "The given tag type doesn't exist.");
        break;
      }
      $title = escape_output($targetTagType->name);
      $output = "<h1>".escape_output($targetTagType->name).($targetTagType->allow($user, "edit") ? " <small>(".$targetTagType->link("edit", "edit").")</small>" : "")."</h1>\n".$targetTagType->profile();
      break;
    case 'delete':
      if ($targetTagType->id == 0) {
        $output = display_error("Error: Invalid tag type", "The given tag type doesn't exist.");
        break;
      }
      $deleteTagType = $targetTagType->delete();
      if ($deleteTagType === True) {
        redirect_to("/tag_types/", array('status' => 'Successfully deleted '.urlencode($targetTagType->name).'.', 'class' => 'success'));
      } else {
        redirect_to("/tag_types/".intval($targetTagType->id)."/show/", array('status' => 'An error occurred while deleting '.urlencode($targetTagType->name).'.', 'class' => 'error'));
      }
      break;
    default:
    case 'index':
      $title = "All Tag Types";
      $output = "<h1>All Tag Types</h1>\n".display_tag_types($database, $user);
      break;
  }
}
start_html($database, $user, "Animurecs", $title, $_REQUEST['status'], $_REQUEST['class']);
echo $output;
display_footer();
?>