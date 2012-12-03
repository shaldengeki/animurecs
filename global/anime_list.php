<?php
class AnimeList extends BaseList {
  // anime list.
  public function __construct(DbConn $database, $user_id=Null) {
    parent::__construct($database, $user_id);
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
}
?>