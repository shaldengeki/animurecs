<?php
include_once("global/includes.php");

if (isset($_POST['switch_username']) && $user->isAdmin()) {
  $switchUser = $user->switchUser($_POST['switch_username']);
  redirect_to($switchUser);
}

try {
  $targetUser = new User($database, intval($_REQUEST['id']));
} catch (Exception $e) {
  $targetUser = new User($database, 0);
}

if (!$targetUser->allow($user, $_REQUEST['action'])) {
  $title = "Error: Insufficient privileges";
  $output = display_error("Error: Insufficient privileges", "You're not allowed to do this.");
} else {
  switch($_REQUEST['action']) {
    case 'switch_back':
      $switchUser = $user->switchUser($_SESSION['switched_user']['username'], False);
      redirect_to($switchUser);
      break;
    case 'switch_user':
      $title = "Switch Users";
      $output = "<h1>Switch Users</h1>\n".$user->switchForm();
      break;
    case 'edit':
      if ($targetUser->id === 0) {
        $output = display_error("Error: Invalid user", "The given user doesn't exist.");
        break;
      }
      $title = "Editing ".escape_output($targetUser->username);
      $output = "<h1>".escape_output($targetUser->username)."</h1>\n";
      $output .= $targetUser->form($user);
      break;
    case 'show':
      if ($targetUser->id === 0) {
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