<?php

class AnimeEntry extends BaseEntry {
  public static $TABLE = "anime_lists";
  public static $PLURAL = "animeLists";
  public static $FIELDS = [
    'id' => [
      'type' => 'int',
      'db' => 'id'
    ],
    'userId' => [
      'type' => 'int',
      'db' => 'user_id'
    ],
    'animeId' => [
      'type' => 'int',
      'db' => 'anime_id'
    ],
    'time' => [
      'type' => 'date',
      'db' => 'time'
    ],
    'status' => [
      'type' => 'int',
      'db' => 'status'
    ],
    'score' => [
      'type' => 'float',
      'db' => 'score'
    ],
    'episode' => [
      'type' => 'int',
      'db' => 'episode'
    ]
  ];
  public static $JOINS = [
    'user' => [
      'obj' => 'User',
      'table' => 'users',
      'own_col' => 'user_id',
      'join_col' => 'id',
      'type' => 'one'
    ],
    'anime' => [
      'obj' => 'Anime',
      'table' => 'anime',
      'own_col' => 'anime_id',
      'join_col' => 'id',
      'type' => 'one'
    ],
    'comments' => [
      'obj' => 'CommentEntry',
      'table' => 'comments',
      'own_col' => 'id',
      'join_col' => 'parent_id',
      'condition' => "comments.type = 'AnimeEntry'",
      'type' => 'many'
    ]
  ];

  public static $URL = "anime_entries";
  public static $ENTRY_TYPE = "Anime";
  public static $TYPE_ID = "anime_id";
  public static $PART_NAME = "episode";

  public function __construct(Application $app, $id=Null, $params=Null) {
    parent::__construct($app, $id, $params);
    if ($id === 0) {
      $this->anime = new Anime($this->app, 0);
      $this->animeId = $this->userId = 0;
      $this->episode = 0;
    }
  }
  public function animeList() {
    return $this->user->animeList();
  }
  public function time() {
    return $this->time;
  }

  public function formatFeedEntry() {
    // fetch the previous feed entry and compare values against current entry.

    $nowTime = new DateTime("now", $this->app->outputTimeZone);
    $diffInterval = $nowTime->diff($this->time);

    $feedEntry = ['title' => $this->user->link("show", $this->user->username), 'text' => 'Empty feed entry'];

    try {
      $prevEntry = $this->animeList()->prevEntry($this->anime->id, $this->time);
    } catch (ModelException $e) {
      // this anime doesn't exist in the database.
      $this->app->logger->err($e->__toString());
      $feedEntry['text'] = "This entry couldn't be retrieved, since its anime doesn't exist.";
      return $feedEntry;
    }
      
    $statusChanged = (bool) ($this->status != $prevEntry->status);
    $scoreChanged = (bool) ($this->score != $prevEntry->score);
    $partChanged = (bool) ($this->{AnimeList::$PART_NAME} != $prevEntry->{AnimeList::$PART_NAME});

    
    // concatenate appropriate parts of this status text.
    $statusTexts = [];
    if ($statusChanged) {
      $statusTexts[] = $this->animeList()->statusStrings[intval((bool)$prevEntry)][intval($this->status)];
    }
    if ($scoreChanged) {
      $statusTexts[] = $this->animeList()->scoreStrings[intval($this->score == 0)][intval($statusChanged)];
    }
    if ($partChanged && ($this->{AnimeList::$PART_NAME} != $this->anime->{AnimeList::$PART_NAME."Count"} || $this->status != 2)) {
      $statusTexts[] = $this->animeList()->partStrings[intval($statusChanged || $scoreChanged)];
    }
    $statusText = "";
    if ($statusTexts) {
      $statusText = implode(" ", $statusTexts);

      // replace placeholders.

      $statusText = str_replace("[TYPE_VERB]", AnimeList::$TYPE_VERB, $statusText);
      $statusText = str_replace("[PART_NAME]", AnimeList::$PART_NAME, $statusText);
      $statusText = str_replace("[TITLE]", $this->anime->link("show", $this->anime->title), $statusText);
      $statusText = str_replace("[SCORE]", $this->score, $statusText);
      $statusText = str_replace("[PART]", $this->{AnimeList::$PART_NAME}, $statusText);
      $statusText = str_replace("/[TOTAL_PARTS]", $this->anime->{AnimeList::$PART_NAME."Count"} ? "/".$this->anime->{AnimeList::$PART_NAME."Count"} : "", $statusText);
      $statusText = ucfirst($statusText).".";

      $feedEntry['text'] = $statusText;
    }
    return $feedEntry;
  }
  public function render() {
    $status = "";
    $class = "";
    switch($this->app->action) {
      case 'new':
      case 'edit':
        $verbProgressive = $this->id === 0 ? "creating" : "updating";
        $verbPast = $this->id === 0 ? "created" : "updated";
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
            $targetUser->load();
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
            $targetAnime->load();
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
          try {
            $updateList = $targetEntry->create_or_update($_POST['anime_entries']);
          } catch (ValidationException $e) {
            $this->app->delayedMessage("Some problems were found with your input while ".$verbProgressive." an anime list entry:\n".$e->listMessages());
            $this->app->redirect();
          }
          if ($updateList) {
            $status = "Successfully updated your anime list.";
            $class = "success";
            break;
          } else {
            $status = "An error occurred while ".$verbProgressive." your anime list.";
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
        $deleteEntry = $this->delete();
        if ($deleteEntry) {
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