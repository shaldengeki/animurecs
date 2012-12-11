<?php
require_once("global/includes.php");

try {
  $targetEntry = new AnimeEntry($database, intval($_REQUEST['id']));
} catch (Exception $e) {
  redirect_to($user->url(), array('status' => "This anime entry ID doesn't exist.", 'class' => 'error'));
}
$location = $user->url();
$status = "";
$class = "";
if (!$targetEntry->allow($user, $_REQUEST['action'])) {
  $status = "You don't have permissions to do this.";
  $class = "error";
} else {
  switch($_REQUEST['action']) {
    case 'new':
    case 'edit':
      if (isset($_POST['anime_entry']) && is_array($_POST['anime_entry'])) {
        // filter out any blank values to fill them with the previous entry's values.
        foreach ($_POST['anime_entry'] as $key=>$value) {
          if ($_POST['anime_entry'][$key] === '') {
            unset($_POST['anime_entry'][$key]);
          }
        }
        // check to ensure that the user has perms to create or update an entry.
        try {
          $targetUser = new User($database, intval($_POST['anime_entry']['user_id']));
        } catch (Exception $e) {
          // this non-zero userID does not exist.
          $status = "This user ID doesn't exist.";
          $class = "error";
          break;
        }
        if ($targetUser->id === 0) {
          // This user does not exist.
          $location = $user->url();
          $status = "This user ID doesn't exist.";
          $class = "error";
          break;
        }
        $targetEntry = new AnimeEntry($database, intval($_POST['anime_entry']['id']), array('user' => $targetUser));
        if (!$targetEntry->allow($user, $_REQUEST['action'])) {
          $location = $targetUser->url();
          $status = "You can't update someone else's anime list.";
          $class = "error";
          break;
        }
        try {
          $targetAnime = new Anime($database, intval($_POST['anime_entry']['anime_id'])));
        } catch (Exception $e) {
          $location = $targetUser->url();
          $status = "This anime ID doesn't exist.";
          $class = "error";
          break;
        }
        if ($targetAnime->id === 0) {
          $location = $targetUser->url();
          $status = "This anime ID doesn't exist.";
          $class = "error";
          break;
        }
        if (!isset($_POST['anime_entry']['id'])) {
          // fill default values from the last entry for this anime.
          $lastEntry = $targetUser->animeList->uniqueList[intval($_POST['anime_entry']['anime_id'])];
          if (!$lastEntry) {
            $lastEntry = [];
          } else {
            unset($lastEntry['id'], $lastEntry['time'], $lastEntry['anime']);
          }
          $_POST['anime_entry'] = array_merge($lastEntry, $_POST['anime_entry']);
        }
        $updateList = $targetEntry->create_or_update($_POST['anime_entry']);
        if ($updateList) {
          $status = "Successfully updated your anime list.";
          $class = "success";
          break;
        } else {
          $status = "An error occurred while changing your anime list.";
          $class = "error";
          break;
        }
      }
      break;
    case 'show':
      break;
    case 'delete':
      if (!isset($_REQUEST['id'])) {
        $_REQUEST['id'] = False;
      }
      $deleteList = $targetEntry->delete(intval($_REQUEST['id']));
      if ($deleteList) {
        $status = "Successfully removed an entry your anime list.";
        $class = "success";
        break;
      } else {
        $status = "An error occurred while removing an entry from your anime list.";
        $class = "error";
        break;
      }
      break;
    default:
      break;
  }
}
redirect_to($location, array('status' => $status, 'class' => $class));
?>