<?php
include_once("global/includes.php");

if (intval($_REQUEST['user_id']) === $user->id) {
  $targetUser = $user;
} else {
  try {
    $targetUser = new User($database, intval($_REQUEST['user_id']));
  } catch (Exception $e) {
    redirect_to(array('location' => 'user.php', 'status' => "This user ID doesn't exist.", 'class' => 'error'));
  }
}
$location = "user.php?action=show&id=".intval($targetUser->id);
$status = "";
$class = "";
if (!$targetUser->animeList->allow($user, $_REQUEST['action'])) {
  $location = "user.php?action=show&id=".intval($targetUser->id);
  $status = "You don't have permissions to do this.";
  $class = "error";
} else {
  switch($_REQUEST['action']) {
    case 'new':
    case 'edit':
      if (isset($_REQUEST['anime_list']) && is_array($_REQUEST['anime_list'])) {
        $_POST['anime_list'] = $_REQUEST['anime_list'];
      }
      if (isset($_POST['anime_list']) && is_array($_POST['anime_list'])) {
        // check to ensure that the user has perms to create or update a user.
        try {
          $targetUser = new User($database, intval($_POST['anime_list']['user_id']));
        } catch (Exception $e) {
          // this non-zero userID does not exist.
          $location = "user.php";
          $status = "This user ID doesn't exist.";
          $class = "error";
          break;
        }
        if ($targetUser->id === 0) {
          // This user does not exist.
          $location = "user.php";
          $status = "This user ID doesn't exist.";
          $class = "error";
          break;
        }
        if (isset($_POST['anime_list']['id'])) {
          if (!$targetUser->animeList->allow($user, 'edit')) {
            $location = 'user.php?action=show&id='.intval($targetUser->id);
            $status = "You don't have permissions to update this user's anime list.";
            $class = "error";
            break;
          }
        } else {
          if (!$targetUser->animeList->allow($user, 'new')) {
            $location = 'user.php?action=show&id='.intval($targetUser->id);
            $status = "You don't have permissions to add to this user's anime list.";
            $class = "error";
            break;
          }
        }
        $updateList = $targetUser->animeList->create_or_update($_POST['anime_list']);
        if ($updateList) {
          $location = 'user.php?action=show&id='.intval($targetUser->id);
          $status = "Successfully updated your anime list.";
          $class = "success";
          break;
        } else {
          $location = 'user.php?action=show&id='.intval($targetUser->id);
          $status = "An error occurred while changing your anime list";
          $class = "error";
          break;
        }
      }
      break;
    case 'show':
      $location = 'user.php?action=show&id='.intval($targetUser->id);
      break;
    case 'delete':
      if (!isset($_REQUEST['id'])) {
        if (!$targetUser->animeList->allow($user, 'd')) {
          $location = 'user.php?action=show&id='.intval($targetUser->id);
          $status = "You don't have permissions to update this user's anime list.";
          $class = "error";
          break;
        }
      }
      $deleteList = $targetUser->animeList->delete($_REQUEST['id']);
      if ($updateList) {
        $location = 'user.php?action=show&id='.intval($targetUser->id);
        $status = "Successfully updated your anime list.";
        $class = "success";
        break;
      } else {
        $location = 'user.php?action=show&id='.intval($targetUser->id);
        $status = "An error occurred while changing your anime list.";
        $class = "error";
        break;
      }
      break;
    default:
      $location = 'user.php?action=show&id='.intval($targetUser->id);
      break;
  }
}
redirect_to(array('location' => $location, 'status' => $status, 'class' => $class));
?>