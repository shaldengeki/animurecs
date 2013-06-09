<?php
class AnimeList extends BaseList {
  // anime list.
  public static $modelTable = "anime_lists";
  public static $modelPlural = "animeLists";


  public function __construct(Application $app, $user_id=Null) {
    parent::__construct($app, $user_id);
    $this->partName = "episode";
    $this->listType = "Anime";
    $this->typeVerb = "watching";
    $this->feedType = "Anime";
    $this->listTypeLower = strtolower($this->listType);
    $this->typeID = $this->listTypeLower.'_id';
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'new':
      case 'edit':
      case 'delete':
        if ($authingUser->id == $this->user_id || $authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'index':
        if ($authingUser->isAdmin()) {
          return True;
        }
        return False;
        break;
      case 'show':
      case 'feed':
        return True;
        break;
      default:
        return False;
        break;
    }
  }
  public function render() {
    if ($this->user_id !== Null) {
      $targetUser = $this->user();
    } else {
      try {
        $targetUser = isset($_POST['anime_lists']['user_id']) ? new User($this->app, intval($_POST['anime_lists']['user_id'])) : $this->app->user;
        if ($targetUser->id !== 0) {
          $targetUser->getInfo();
        } else {
          // This user does not exist.
          $this->app->redirect($this->app->user->url(), ['status' => "This user ID doesn't exist.", 'class' => 'error']);
        }
      } catch (Exception $e) {
        // this non-zero userID does not exist.
        $this->app->redirect($this->app->user->url(), ['status' => "This user doesn't exist.", 'class' => 'error']);
      }
    }
    $location = $this->user()->url();
    $status = "";
    $class = "";
    switch($this->app->action) {
      case 'feed':
        $maxTime = isset($_REQUEST['maxTime']) ? new DateTime('@'.intval($_REQUEST['maxTime'])) : Null;
        $minTime = isset($_REQUEST['minTime']) ? new DateTime('@'.intval($_REQUEST['minTime'])) : Null;
        $entries = $this->entries($minTime, $maxTime, 50);
        echo $this->feed($entries, 50, "");
        exit;
        break;
      case 'new':
      case 'edit':
        if (isset($_REQUEST['anime_lists']) && is_array($_REQUEST['anime_lists'])) {
          $_POST['anime_lists'] = $_REQUEST['anime_lists'];
        }
        if (isset($_POST['anime_lists']) && is_array($_POST['anime_lists'])) {
          // filter out any blank values to fill them with the previous entry's values.
          foreach ($_POST['anime_lists'] as $key=>$value) {
            if ($_POST['anime_lists'][$key] === '') {
              unset($_POST['anime_lists'][$key]);
            }
          }
          if (!isset($_POST['anime_lists']['id'])) {
            // fill default values from the last entry for this anime.
            $lastEntry = $this->uniqueList()[intval($_POST['anime_lists']['anime_id'])];
            if (!$lastEntry) {
              $lastEntry = [];
            } else {
              unset($lastEntry['id'], $lastEntry['time'], $lastEntry['anime']);
            }
            $_POST['anime_lists'] = array_merge($lastEntry, $_POST['anime_lists']);
          }
          $updateList = $this->create_or_update($_POST['anime_lists']);
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
        if (!$this->app->checkCSRF()) {
          $this->app->display_error(403);
        }
        if (!isset($_REQUEST['id'])) {
          $entryList = Null;
        } else {
          $entryList = [intval($_REQUEST['id'])];
        }
        $deleteList = $this->app->user->animeList->delete($entryList);
        if ($deleteList) {
          $status = "Successfully deleted entries from your anime list.";
          $class = "success";
          break;
        } else {
          $status = "An error occurred while deleting entries from your anime list.";
          $class = "error";
          break;
        }
        break;
      default:
        break;
    }
    $this->app->redirect($location, ['status' => $status, 'class' => $class]);
  }
}
?>