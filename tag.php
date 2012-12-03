<?php
include_once("global/includes.php");

if (isset($_POST['tag']) && is_array($_POST['tag'])) {
  // check to ensure that the user has perms to create or update an tag.
  try {
    $targetTag = new Tag($database, intval($_POST['tag']['id']));
  } catch (Exception $e) {
    // this non-zero tagID does not exist.
    redirect_to("/tags/", array('status' => 'This tag ID does not exist.', 'class' => 'error'));
  }
  if ($targetTag->id === 0) {
    $authStatus = $targetTag->allow($user, 'new');
  } else {
    $authStatus = $targetTag->allow($user, 'edit');
  }
  if (!$authStatus) {
    redirect_to(($targetTag->id === 0 ? $targetTag->url("index") : $targetTag->url("show")), array('status' => "You're not allowed to do this.", 'class' => 'error'));
  }
  $updateTag = $targetTag->create_or_update($_POST['tag']);
  if ($updateTag) {
    redirect_to($targetTag->url("show"), array('status' => "Successfully updated.", 'class' => 'success'));
  } else {
    redirect_to(($targetTag->id === 0 ? $targetTag->url("new") : $targetTag->url("show")), array('status' => "An error occurred while creating or updating this tag.", 'class' => 'error'));
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
    case 'token_search':
      $tags = [];
      if (isset($_REQUEST['term'])) {
        $tags = $database->queryAssoc("SELECT `id`, `name` FROM `tags` WHERE MATCH(`name`) AGAINST(".$database->quoteSmart($_REQUEST['term'])." IN BOOLEAN MODE) ORDER BY `name` ASC;");
      }
      echo json_encode($tags);
      exit;
      break;
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
      $title = escape_output($targetTag->name);
      $output = $targetTag->profile($recsEngine, $user);
      break;
    case 'delete':
      if ($targetTag->id == 0) {
        $output = display_error("Error: Invalid tag", "The given tag doesn't exist.");
        break;
      }
      $tagName = $targetTag->name;
      $deleteTag = $targetTag->delete();
      if ($deleteTag) {
        redirect_to('/tags/', array('status' => 'Successfully deleted '.urlencode($tagName).'.', 'class' => 'success'));
      } else {
        redirect_to($targetTag->url("show"), array('status' => 'An error occurred while deleting '.urlencode($tagName).'.', 'class' => 'error'));
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