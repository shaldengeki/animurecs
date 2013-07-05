<?php

class AnimeEntry extends BaseEntry {
  public static $MODEL_TABLE = "anime_lists";
  public static $MODEL_URL = "anime_entries";
  public static $MODEL_PLURAL = "animeLists";
  public static $ENTRY_TYPE = "Anime";
  public static $TYPE_ID = "anime_id";

  protected $anime, $animeId;
  protected $episode;
  protected $list;
  protected $comments;

  public function __construct(Application $app, $id=Null, $params=Null) {
    parent::__construct($app, $id, $params);
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
    $comments = $this->dbConn->table('comments')->fields('id')->where(['type' => 'AnimeEntry', 'parent_id' => $this->id])->order('created_at ASC')->assoc('id', 'id');
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
    $partChanged = (bool) ($this->{AnimeList::$PART_NAME}() != $prevEntry->{AnimeList::$PART_NAME}());
    
    // concatenate appropriate parts of this status text.
    $statusTexts = [];
    if ($statusChanged) {
      $statusTexts[] = $this->list->statusStrings[intval((bool)$prevEntry)][intval($this->status())];
    }
    if ($scoreChanged) {
      $statusTexts[] = $this->list->scoreStrings[intval($this->score() == 0)][intval($statusChanged)];
    }
    if ($partChanged && ($this->{AnimeList::$PART_NAME} != $this->anime()->{AnimeList::$PART_NAME."Count"} || $this->status() != 2)) {
      $statusTexts[] = $this->list->partStrings[intval($statusChanged || $scoreChanged)];
    }
    $statusText = "";
    if ($statusTexts) {
      $statusText = implode(" ", $statusTexts);

      // replace placeholders.
      $statusText = str_replace("[TYPE_VERB]", AnimeList::$TYPE_VERB, $statusText);
      $statusText = str_replace("[PART_NAME]", AnimeList::$PART_NAME, $statusText);
      $statusText = str_replace("[TITLE]", $this->anime()->link("show", $this->anime()->title), $statusText);
      $statusText = str_replace("[SCORE]", $this->score(), $statusText);
      $statusText = str_replace("[PART]", $this->{AnimeList::$PART_NAME}, $statusText);
      $statusText = str_replace("/[TOTAL_PARTS]", $this->anime()->{AnimeList::$PART_NAME."Count"} ? "/".$this->anime()->{AnimeList::$PART_NAME."Count"} : "", $statusText);
      $statusText = ucfirst($statusText).".";
    }
    return ['title' => $this->user()->link("show", $this->user()->username), 'text' => $statusText];
  }
  public function render() {
    $status = "";
    $class = "";
    switch($this->app->action) {
      case 'new':
      case 'edit':
        if (isset($_REQUEST['anime_entries']) && is_array($_REQUEST['anime_entries'])) {
          $_POST['anime_entries'] = $_REQUEST['anime_entries'];
        }
        if (isset($_POST['anime_entries']) && is_array($_POST['anime_entries'])) {
          // filter out any blank values to fill them with the previous entry's values.
          foreach ($_POST['anime_entries'] as $key=>$value) {
            if ($_POST['anime_entries'][$key] === '') {
              unset($_POST['anime_entries'][$key]);
            }
          }
          // check to ensure that the user has perms to create or update an entry.
          try {
            $targetUser = new User($this->app, intval($_POST['anime_entries']['user_id']));
            $targetUser->getInfo();
          } catch (DbException $e) {
            // this non-zero userID does not exist.
            $status = "This user doesn't exist.";
            $class = "error";
            break;
          }
          $targetEntry = new AnimeEntry($this->app, intval($this->app->id), ['user' => $targetUser]);
          if (!$targetEntry->allow($this->app->user, $this->app->action)) {
            $status = "You can't update someone else's anime list.";
            $class = "error";
            break;
          }
          try {
            $targetAnime = new Anime($this->app, intval($_POST['anime_entries']['anime_id']));
            $targetAnime->getInfo();
          } catch (DbException $e) {
            $status = "This anime ID doesn't exist.";
            $class = "error";
            break;
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
          $updateList = $targetEntry->create_or_update($_POST['anime_entries']);
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
    if ($status) {
      $this->app->delayedMessage($status, $class);
    }
    $this->app->redirect();
  }
}

?>