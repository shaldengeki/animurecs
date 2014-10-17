<?php

class AnimeEntriesController extends Controller {
  public static $URL_BASE = "anime_entries";
  public static $MODEL = "AnimeEntry";

  public function _isAuthorized($action) {
    switch ($action) {
      /* Require the authing user to be logged in. */
      case 'comment':
        if ($this->_app->user->loggedIn()) {
          return True;
        }
        return False;
        break;

      /* Require the current user to be the requested user, or be staff. */
      case 'edit':
      case 'delete':
        if (($this->_app->user->id === $this->_target->user->id && $this->_app->user->id === intval($_POST['anime_entries']['user_id'])) || $this->_app->user->isStaff()) {
          return True;
        }
        return False;
        break;

      case 'index':
        if (isset($_POST['anime_entries']) && is_array($_POST['anime_entries'])) {
          // if the user isn't submitting an entry for themselves, they must be staff.
          if ($this->_app->user->loggedIn() && ($this->_app->user->id === intval($_POST['anime_entries']['user_id']) || $this->_app->user->isStaff())) {
            return True;
          }
          return False;
        }
        if ($this->_app->user->isAdmin()) {
          return True;
        }
        return False;
        break;

      /* Public views. */
      case 'show':
        return True;
        break;
      default:
        return False;
        break;
    }
  }

  public function delete() {
    if (!$this->_app->checkCSRF()) {
      $this->_app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
    }
    $deleteEntry = $this->_target->delete();
    if ($deleteEntry) {
      $this->_app->display_success(200, "Successfully removed an entry from your anime list.");
    } else {
      $this->_app->display_error(500, "An error occurred while removing an entry from your anime list.");
    }
  }

  public function edit() {
    if (!isset($_POST['anime_entries']) || !is_array($_POST['anime_entries'])) {
      $this->_app->display_error(400, "Please provide parameters to update an anime entry.");
    }

    // check to ensure that the user has perms to update an entry.
    // we have to set the target properly.
    try {
      $targetUser = new User($this->_app, intval($_POST['anime_entries']['user_id']));
      $targetUser->load();
    } catch (DatabaseException $e) {
      // this non-zero userID does not exist.
      $this->_app->display_error(404, "No such user found.");
    }
    $this->_target = new AnimeEntry($this->_app, intval($this->_app->id), ['user' => $targetUser]);
    if (!$this->_isAuthorized($this->_app->action)) {
      $this->_app->display_error(403, "You can't update someone else's anime list.");
    }
    try {
      $targetAnime = new Anime($this->_app, intval($_POST['anime_entries']['anime_id']));
      $targetAnime->load();
    } catch (DatabaseException $e) {
      $this->_app->display_error(404, "No such anime found.");
    }
    if (!isset($_POST['anime_entries']['id'])) {
      // fill default values from the last entry for this anime.
      $lastEntry = $targetUser->animeList->uniqueList[intval($_POST['anime_entries']['anime_id'])];
      if (!$lastEntry) {
        $lastEntry = [];
      } else {
        unset($lastEntry['id'], $lastEntry['time'], $lastEntry['anime']);
      }
      $_POST['anime_entries'] = array_merge($lastEntry, $_POST['anime_entries']);
    }

    $updateList = $this->_target->create_or_update($_POST['anime_entries']);
    if ($updateList) {
      $this->_app->display_success(200, $this->_target->serialize());
    } else {
      $this->_app->display_error(500, "An error occurred while updating your anime list. Please try again!");
    }
  }

  public function index() {
    if (isset($_POST['anime_entries']) && is_array($_POST['anime_entries'])) {
      // filter out any blank values to fill them with the previous entry's values.
      foreach ($_POST['anime_entries'] as $key=>$value) {
        if ($_POST['anime_entries'][$key] === '') {
          unset($_POST['anime_entries'][$key]);
        }
      }
      // check to ensure that the user has perms to create an entry.
      // we have to set the target properly.
      if (!isset($_POST['anime_entries']['user_id'])) {
        $_POST['anime_entries']['user_id'] = $this->_app->user->id;
        $targetUser = $this->_app->user;
      } else {
        $targetUser = new User($this->_app, intval($_POST['anime_entries']['user_id']));
      }

      try {
        $targetUser->load();
      } catch (DatabaseException $e) {
        // this non-zero userID does not exist.
        $this->_app->display_error(404, "No such user found.");
      }
      $this->_target = new AnimeEntry($this->_app, intval($this->_app->id), ['user' => $targetUser]);
      if (!$this->_isAuthorized($this->_app->action)) {
        $this->_app->display_error(403, "You can't update someone else's anime list.");
      }
      try {
        $targetAnime = new Anime($this->_app, intval($_POST['anime_entries']['anime_id']));
        $targetAnime->load();
      } catch (DatabaseException $e) {
        $this->_app->display_error(404, "No such anime found.");
      }
      // fill default values from the last entry for this anime.
      $lastEntry = $targetUser->animeList->uniqueList[intval($_POST['anime_entries']['anime_id'])];
      if (!$lastEntry) {
        $lastEntry = [];
      } else {
        unset($lastEntry['id'], $lastEntry['time'], $lastEntry['anime']);
      }
      $_POST['anime_entries'] = array_merge($lastEntry, $_POST['anime_entries']);
      $updateList = $this->_target->create_or_update($_POST['anime_entries']);
      if ($updateList) {
        $this->_app->display_success(200, $this->_target->serialize());
      } else {
        $this->_app->display_error(500, "An error occurred while updating your anime list. Please try again!");
      }
    }
    $this->_app->display_error(400, "Please provide parameters to create an anime entry.");
  }

  public function show() {
    $this->_app->display_response(200, $this->_target->serialize());
  }
}
?>