<?php
include_once("global/includes.php");
if (!$user->loggedIn()) {
  header("Location: index.php");
}

if (isset($_POST['switch_username']) && $user->isAdmin()) {
  $switchUser = $user->switchUser($_POST['switch_username']);
  redirect_to($switchUser);
} elseif ($_REQUEST['action'] == 'switch_back') {
  $switchUser = $user->switchUser($_SESSION['switched_user']['username'], False);
  redirect_to($switchUser);
}

$targetUser = False;
if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
  try {
    $targetUser = new User($database, intval($_REQUEST['id']));
  } catch (Exception $e) {
    $targetUser = False;
  }
}

if ($targetUser !== False && !$targetUser->allow($user, $_REQUEST['action'])) {
  $title = "Error: Insufficient privileges";
  $output = display_error("Error: Insufficient privileges", "You're not allowed to do this.");
} else {
  switch($_REQUEST['action']) {
    case 'switch_user':
      if (!$user->isAdmin()) {
        $output = display_error("Error: Insufficient privileges", "Only admins can switch users.");
        break;      
      }
      $title = "Switch Users";
      $output = "<h1>Switch Users</h1>\n".$user->switchForm();
      break;
    case 'edit':
      if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
        $output = display_error("Error: Invalid user ID", "Please check your ID and try again.");
        break;
      }
      //ensure that user has sufficient privileges to modify this user.
      if ($user->id != intval($_REQUEST['id']) && !$user->isAdmin()) {
        $output = display_error("Error: Insufficient privileges", "You can't edit this user.");
        break;
      }
      if ($targetUser === False) {
        $output = display_error("Error: Invalid user", "The given user doesn't exist.");
        break;
      }
      $title = "Editing ".escape_output($targetUser->username);
      $output = "<h1>".escape_output($targetUser->username)."</h1>\n";
      $output .= $targetUser->editForm($user);
      break;
    case 'show':
      if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
        $output = display_error("Error: Invalid user ID", "Please check your ID and try again.");
        break;
      }
      //ensure that user has sufficient privileges to view this user.
      if ($user->id != intval($_REQUEST['id']) && !$user->isAdmin()) {
        $output = display_error("Error: Insufficient privileges", "You can't view this user.");
        break;
      }
      if ($targetUser === False) {
        $output = display_error("Error: Invalid user", "The given user doesn't exist.");
        break;
      }
      $title = escape_output($targetUser->username)."'s Profile";
      $output = "<h1>".escape_output($targetUser->username).($targetUser->allow($user, "edit") ? " <small>(".$targetUser->link("edit", "edit").")</small>" : "")."</h1>\n".$targetUser->profile();
      break;
    default:
    case 'index':
      $title = "Users";
      $output = "<h1>Users</h1>\n".display_users($database, $user);
      break;
  }
}
start_html($database, $user, "Animurecs", $title, $_REQUEST['status'], $_REQUEST['class']);
echo $output;
display_footer();
?>