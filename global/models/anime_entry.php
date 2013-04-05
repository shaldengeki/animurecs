<?php

class AnimeEntry extends BaseEntry {
  public static $modelTable = "anime_lists";
  public static $modelUrl = "anime_entries";
  public static $modelPlural = "animeLists";

  protected $anime, $animeId;
  protected $episode;
  protected $list;
  protected $comments;

  public function __construct(Application $app, $id=Null, $params=Null) {
    parent::__construct($app, $id, $params);
    $this->entryType = "Anime";
    if ($id === 0) {
      $this->anime = new Anime($this->app, 0);
      $this->animeId = $this->userId = 0;
      $this->episode = 0;
      $this->list = new AnimeList($this->app, $this->userId);
    } else {
      if (!is_numeric($this->animeId)) {
        $this->animeId = Null;
      }
      $this->anime = $this->episode = Null;
      if (!isset($this->list)) {
        $this->list = $this->user()->animeList;
      }
    }
  }
  public function animeId() {
    return $this->returnInfo('animeId');
  }
  public function anime() {
    if ($this->anime === Null) {
      $this->anime = new Anime($this->app, $this->animeId());
    }
    return $this->anime;
  }
  public function episode() {
    return $this->returnInfo('episode');
  }
  private function _getComments() {
    $comments = $this->dbConn->queryAssoc("SELECT `id` FROM `comments` WHERE `type` = 'AnimeEntry` && `parent_id` = ".intval($this->id)." ORDER BY `created_at` ASC", 'id', 'id');
    return new CommentGroup($this->app, array_keys($comments));
  }
  public function comments() {
    if ($this->comments === Null) {
      $this->comments = $this->_getComments();
    }
    return $this->comments;
  }
  public function formatFeedEntry() {
    // fetch the previous feed entry and compare values against current entry.

    $nowTime = new DateTime("now", $this->app->outputTimeZone);

    $diffInterval = $nowTime->diff($this->time());
    $prevEntry = $this->list->prevEntry($this->anime()->id, $this->time());

    $statusChanged = (bool) ($this->status() != $prevEntry->status());
    $scoreChanged = (bool) ($this->score() != $prevEntry->score());
    $partChanged = (bool) ($this->{$this->list->partName}() != $prevEntry->{$this->list->partName}());
    
    // concatenate appropriate parts of this status text.
    $statusTexts = [];
    if ($statusChanged) {
      $statusTexts[] = $this->list->statusStrings[intval((bool)$prevEntry)][intval($this->status())];
    }
    if ($scoreChanged) {
      $statusTexts[] = $this->list->scoreStrings[intval($this->score() == 0)][intval($statusChanged)];
    }
    if ($partChanged && ($this->{$this->list->partName} != $this->anime()->{$this->list->partName."Count"} || $this->status() != 2)) {
      $statusTexts[] = $this->list->partStrings[intval($statusChanged || $scoreChanged)];
    }
    $statusText = implode(" ", $statusTexts);

    // replace placeholders.
    $statusText = str_replace("[TYPE_VERB]", $this->list->typeVerb, $statusText);
    $statusText = str_replace("[PART_NAME]", $this->list->partName, $statusText);
    $statusText = str_replace("[TITLE]", $this->anime()->link("show", $this->anime()->title), $statusText);
    $statusText = str_replace("[SCORE]", $this->score(), $statusText);
    $statusText = str_replace("[PART]", $this->{$this->list->partName}, $statusText);
    $statusText = str_replace("[TOTAL_PARTS]", $this->anime()->{$this->list->partName."Count"}, $statusText);
    $statusText = ucfirst($statusText).".";

    return array('title' => $this->user()->link("show", $this->user()->username), 'text' => $statusText);
  }
  public function render() {
    $location = $this->app->user->url();
    $status = "";
    $class = "";
    switch($this->app->action) {
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
            $targetUser = new User($this->app, intval($_POST['anime_entry']['user_id']));
            $targetUser->getInfo();
          } catch (DbException $e) {
            // this non-zero userID does not exist.
            $status = "This user doesn't exist.";
            $class = "error";
            break;
          }
          $targetEntry = new AnimeEntry($this->app, intval($this->app->id), array('user' => $targetUser));
          if (!$targetEntry->allow($this->app->user, $this->app->action)) {
            $location = $targetUser->url();
            $status = "You can't update someone else's anime list.";
            $class = "error";
            break;
          }
          try {
            $targetAnime = new Anime($this->app, intval($_POST['anime_entry']['anime_id']));
            $targetAnime->getInfo();
          } catch (DbException $e) {
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
        if (!$this->app->checkCSRF()) {
          $this->app->display_error(403);
        }
        $deleteList = $this->delete();
        if ($deleteList) {
          $status = "Successfully removed an entry from your anime list.";
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
    $this->app->redirect($location, array('status' => $status, 'class' => $class));
  }
}

?>