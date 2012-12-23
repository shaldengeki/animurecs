<?php
class AnimeList extends BaseList {
  // anime list.
  public function __construct(Application $app, $user_id=Null) {
    parent::__construct($app, $user_id);
    $this->modelTable = "anime_lists";
    $this->modelPlural = "animeLists";
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
        $targetUser = isset($_POST['anime_list']['user_id']) ? new User($this->app, intval($_POST['anime_list']['user_id'])) : $this->app->user;
        if ($targetUser->id !== 0) {
          $targetUser->getInfo();
        } else {
          // This user does not exist.
          redirect_to($this->app->user->url(), array('status' => "This user ID doesn't exist.", 'class' => 'error'));
        }
      } catch (Exception $e) {
        // this non-zero userID does not exist.
        redirect_to($this->app->user->url(), array('status' => "This user doesn't exist.", 'class' => 'error'));
      }
    }
    $location = $this->user()->url();
    $status = "";
    $class = "";
    switch($this->app->action) {
      case 'feed':
        $maxTime = new DateTime('@'.intval($_REQUEST['maxTime']));
        $entries = $this->entries($maxTime, 50);
        echo $this->feed($entries, 50, "");
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
            $lastEntry = $this->uniqueList()[intval($_POST['anime_list']['anime_id'])];
            if (!$lastEntry) {
              $lastEntry = [];
            } else {
              unset($lastEntry['id'], $lastEntry['time'], $lastEntry['anime']);
            }
            $_POST['anime_list'] = array_merge($lastEntry, $_POST['anime_list']);
          }
          $updateList = $this->create_or_update($_POST['anime_list']);
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
        $deleteList = $this->app->user->animeList->delete([intval($_REQUEST['id'])]);
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
    redirect_to($location, array('status' => $status, 'class' => $class));
  }
}
?>