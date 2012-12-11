<?php
require_once("global/includes.php");

try {
  $targetEntry = new AnimeEntry($database, intval($_REQUEST['id']));
} catch (Exception $e) {
  redirect_to($user->url(), array('status' => "This entry ID doesn't exist.", 'class' => 'error'));
}
if ($targetEntry->id !== 0) {
  $targetUser = $targetEntry->user;
} else {
  try {
    $targetUser = isset($_POST['anime_list']['user_id']) ? new User($database, intval($_POST['anime_list']['user_id'])) : $user;
  } catch (Exception $e) {
    // this non-zero userID does not exist.
    redirect_to($user->url(), array('status' => "This user ID doesn't exist.", 'class' => 'error'));
  }
}
if ($targetUser->id === 0) {
  // This user does not exist.
  redirect_to($user->url(), array('status' => "This user ID doesn't exist.", 'class' => 'error'));
}

$location = $targetUser->url();
$status = "";
$class = "";
if (!$targetUser->animeList->allow($user, $_REQUEST['action'])) {
  $status = "You don't have permissions to do this.";
  $class = "error";
} else {
  switch($_REQUEST['action']) {
    case 'feed':
      $maxTime = new DateTime('@'.intval($_REQUEST['maxTime']));
      $entries = $targetUser->animeList->entries($maxTime, 50);
      echo $targetUser->animeList->feed($entries, $user, 50, "");
      exit;
      break;
    case 'new':
    case 'edit':
      if (isset($_REQUEST['anime_list']) && is_array($_REQUEST['anime_list'])) {
        $_POST['anime_list'] = $_REQUEST['anime_list'];
      }
      if (isset($_POST['anime_list']) && is_array($_POST['anime_list'])) {
        // filter out any blank values to fill them with the previous entry's values.
        foreach ($_POST['anime_list'] as $key=>$value) {
          if ($_POST['anime_list'][$key] === '') {
            unset($_POST['anime_list'][$key]);
          }
        }
        if (!isset($_POST['anime_list']['id'])) {
          // fill default values from the last entry for this anime.
          $lastEntry = $targetUser->animeList->uniqueList[intval($_POST['anime_list']['anime_id'])];
          if (!$lastEntry) {
            $lastEntry = [];
          } else {
            unset($lastEntry['id'], $lastEntry['time'], $lastEntry['anime']);
          }
          $_POST['anime_list'] = array_merge($lastEntry, $_POST['anime_list']);
        }
        $updateList = $targetUser->animeList->create_or_update($_POST['anime_list']);
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
      $deleteList = $targetUser->animeList->delete([intval($_REQUEST['id'])]);
      if ($deleteList) {
        $status = "Successfully updated your anime list.";
        $class = "success";
        break;
      } else {
        $status = "An error occurred while changing your anime list.";
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