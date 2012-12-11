<?php
require_once("global/includes.php");
if (intval($_REQUEST['id']) === $user->id) {
  $targetUser = $user;
} else {
  try {
    $targetUser = new User($database, intval($_REQUEST['id']));
  } catch (Exception $e) {
    $targetUser = new User($database, 0);
  }
}
if (!$targetUser->allow($user, $_REQUEST['action'])) {
  $title = "Error: Insufficient privileges";
  $output = display_error("Error: Insufficient privileges", "You're not allowed to do this.");
} else {
  switch($_REQUEST['action']) {
    case 'request_friend':
      if ($targetUser->id === $user->id) {
        redirect_to($user->url("show"), array('status' => "You can't befriend yourself, silly!"));
      }
      $requestFriend = $user->requestFriend($targetUser, $_POST['friend_request']);
      if ($requestFriend) {
        redirect_to($targetUser->url("show"), array('status' => "Your friend request has been sent to ".urlencode($targetUser->username).".", 'class' => 'success'));
      } else {
        redirect_to($targetUser->url("show"), array('status' => 'An error occurred while requesting this friend. Please try again.', 'class' => 'error'));
      }
      break;
    case 'confirm_friend':
      $confirmFriend = $user->confirmFriend($targetUser);
      if ($confirmFriend) {
        redirect_to($targetUser->url("show"), array('status' => "Hooray! You're now friends with ".urlencode($targetUser->username).".", 'class' => 'success'));
      } else {
        redirect_to($targetUser->url("show"), array('status' => 'An error occurred while confirming this friend. Please try again.', 'class' => 'error'));
      }
      break;
    case 'mal_import':
      // import a MAL list for this user.
      if (!isset($_POST['user']) || !is_array($_POST['user']) || !isset($_POST['user']['mal_username'])) {
        redirect_to($targetUser->url("edit"), array('status' => 'Please enter a MAL username.'));
      }
      $importMAL = $targetUser->importMAL($_POST['user']['mal_username']);
      if ($importMAL) {
        redirect_to($targetUser->url("show"), array('status' => 'Hooray! Your MAL was successfully imported.', 'class' => 'success'));
      } else {
        redirect_to($targetUser->url("edit"), array('status' => 'An error occurred while importing your MAL. Please try again.', 'class' => 'error'));
      }
      break;
    case 'switch_back':
      $switchUser = $user->switchUser($_SESSION['switched_user'], False);
      redirect_to($switchUser);
      break;
    case 'switch_user':
      if (isset($_POST['switch_username'])) {
        $switchUser = $user->switchUser($_POST['switch_username']);
        redirect_to($switchUser['location'], array('status' => $switchUser['status'], 'class' => $switchUser['class']));
      }
      $title = "Switch Users";
      $output = "<h1>Switch Users</h1>\n".$user->view("switchForm");
      break;
    case 'new':
      $title = "Sign Up";
      $output = "<h1>Sign Up</h1>\n";
      $output .= $targetUser->view("form", $user);
      break;
    case 'edit':
      if (isset($_POST['user']) && is_array($_POST['user'])) {
        // check to ensure that the user has perms to create or update a user.
        try {
          $targetUser = new User($database, intval($_POST['user']['id']));
        } catch (Exception $e) {
          // this non-zero userID does not exist.
          redirect_to('/users/', array('status' => 'This user ID does not exist.', 'class' => 'error'));
        }
        if ($targetUser->id === 0) {
          // check to ensure this username hasn't already been taken.
          $checkUsername = $database->queryCount("SELECT COUNT(*) FROM `users` WHERE `username` = ".$database->quoteSmart($_POST['user']['username'])." LIMIT 1");
          if ($checkUsername > 0) {
            redirect_to($targetUser->url("new"), array('status' => "This username has already been taken.", 'class' => 'error'));
          }
          // check to ensure this email hasn't already been taken.
          $checkEmail = $database->queryCount("SELECT COUNT(*) FROM `users` WHERE `email` = ".$database->quoteSmart($_POST['user']['email'])." LIMIT 1");
          if ($checkEmail > 0) {
            redirect_to($targetUser->url("new"), array('status' => "This email has already been taken.", 'class' => 'error'));
          }
          $authStatus = $targetUser->allow($user, 'new');
        } else {
          $authStatus = $targetUser->allow($user, 'edit');
        }
        if (!$authStatus) {
          redirect_to($targetUser->url("new"), array('status' => "You're not allowed to do this.", 'class' => 'error'));
        }
        // check to ensure userlevels aren't being elevated beyond this user's abilities.
        if (isset($_POST['user']['usermask']) && intval($_POST['user']['usermask']) > 1 && intval($_POST['user']['usermask']) >= $user->usermask) {
          redirect_to($targetUser->url("new"), array('status' => "You can't set permissions beyond your own userlevel.", 'class' => 'error'));
        }

        $updateUser = $targetUser->create_or_update($_POST['user']);
        if ($updateUser) {
          redirect_to($targetUser->url("show"), array('status' => (isset($_POST['user']['id']) ? "Your user settings have been saved." : "Congratulations, you're now signed in!"), 'class' => 'success'));
        } else {
          redirect_to(($targetUser->id === 0 ? $targetUser->url("new") : $targetUser->url("edit")), array('status' => "An error occurred while creating or updating this user.", 'class' => 'error'));
        }
      }
      if ($targetUser->id === 0) {
        $output = display_error("Error: Invalid user", "The given user doesn't exist.");
        break;
      }
      $title = "Editing ".escape_output($targetUser->username);
      $output = "<h1>".escape_output($targetUser->username)."</h1>
            <div class='editUserTabs'>
              <ul class='nav nav-tabs'>
                <li class='active'><a href='#generalSettings' data-toggle='tab'>General</a></li>
                <li><a href='#malImport' data-toggle='tab'>MAL Import</a></li>
                <li><a href='#privacySettings' data-toggle='tab'>Privacy</a></li>
              </ul>
              <div class='tab-content'>
                <div class='tab-pane active' id='generalSettings'>
                  ".$targetUser->view("form", $user)."
                </div>
                <div class='tab-pane' id='malImport'>
                  <p>To import your list, we'll need your MAL username:</p>
                  <form class='form form-inline' action='".$targetUser->url("mal_import")."' method='post'>
                    <input type='text' name='user[mal_username]' placeholder='MAL username' />
                    <input type='submit' class='btn btn-primary' value='Import' />
                  </form>
                </div>
                <div class='tab-pane' id='privacySettings'>
                  Coming soon!
                </div>
              </div>
            </div>\n";
      break;
    case 'show':
      if ($targetUser->id === 0) {
        $output = display_error("Error: Invalid user", "The given user doesn't exist.");
        break;
      }
      $title = escape_output($targetUser->username)."'s Profile";
      $output = $targetUser->view("show", $user);
      break;
    case 'feed':
      if ($targetUser->animeList()->allow($user, 'edit')) {
        $output .= $targetUser->view('addEntryInlineForm');
      }
      if ($targetUser->allow($user, 'comment')) {
        $blankComment = new Comment($targetUser->dbConn, 0, $user, $targetUser);
        $output .= "                <div class='addListEntryForm'>
                    ".$blankComment->inlineForm($user, $targetUser)."
                  </div>\n";

      }
      $output .= $targetUser->profileFeed($user);
      echo $output;
      exit;
    case 'anime_list':
      echo $targetUser->animeList->view("show", $user);
      exit;
    case 'stats':
      echo $targetUser->view('stats', $user);
      exit;
    case 'achievements':
      echo $targetUser->view('achievements', $user);
      exit;
    case 'delete':
      if ($targetUser->id == 0) {
        $output = display_error("Error: Invalid user", "The given user doesn't exist.");
        break;
      }
      $username = $targetUser->username;
      $deleteUser = $targetUser->delete();
      if ($deleteUser === True) {
        redirect_to('/users/', array('status' => 'Successfully deleted '.urlencode($username).'.', 'class' => 'success'));
      } else {
        redirect_to($targetUser->url("show"), array('status' => 'An error occurred while deleting '.urlencode($username).'.', 'class' => 'error'));
      }
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