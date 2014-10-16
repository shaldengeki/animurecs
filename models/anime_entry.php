<?php

class AnimeEntry extends Entry {
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
    'predictedScore' => [
      'type' => 'float',
      'db' => 'predicted_score'
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
  public function validate(array $entry) {
    // validates a pending base_entry creation or update.
    $validationErrors = [];

    try {
      parent::validate($entry);
    } catch (ValidationException $e) {
      $validationErrors = array_merge($validationErrors, $e->messages);
    }

    if (!isset($entry['anime_id']) || !is_integral($entry['anime_id']) || intval($entry['anime_id']) < 1) {
      $validationErrors[] = "Anime ID must be valid";
    }

    if (isset($entry['predicted_score']) && (!is_numeric($entry['predicted_score']) || round(floatval($entry['predicted_score']), 2) < 0 || round(floatval($entry['predicted_score']), 2) > 10)) {
      $validationErrors[] = "Predicted score must be numeric and between 0 and 10";
    }

    if (!isset($entry['episode']) || !is_integral($entry['episode']) || intval($entry['episode']) < 0) {
      $validationErrors[] = "Episode must be valid";
    }

    if ($validationErrors) {
      throw new ValidationException($this->app, $entry, $validationErrors);
    }
    return True;
  }

  public function create_or_update(array $entry, array $whereConditions=Null) {
    // if this is an insertion, insert the currently-predicted score.
    if ($this->id === 0) {
      $user = User::FindById($this->app, (int) $entry['user_id']);
      $anime = Anime::FindById($this->app, (int) $entry['anime_id']);
      $entry['predicted_score'] = round($this->app->recsEngine->predict($user, $anime)[$anime->id], 2);
    }
    $this->validate($entry);
    return parent::create_or_update($entry, $whereConditions);
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
      $this->app->log_exception($e);
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
          } catch (DatabaseException $e) {
            // this non-zero userID does not exist.
            $this->app->display_error(404, "No such user found.");
          }
          $targetEntry = new AnimeEntry($this->app, intval($this->app->id), ['user' => $targetUser]);
          if (!$targetEntry->allow($this->app->user, $this->app->action)) {
            $this->app->display_error(403, "You can't update someone else's anime list.");
          }
          try {
            $targetAnime = new Anime($this->app, intval($_POST['anime_entries']['anime_id']));
            $targetAnime->load();
          } catch (DatabaseException $e) {
            $this->app->display_error(404, "No such anime found.");
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
            $this->app->display_success(200, "Successfully updated your anime list.");
          } else {
            $this->app->display_error(500, "An error occurred while updating your anime list. Please try again!");
          }
        }
        break;
      case 'show':
        $this->app->display_response(200, $this->serialize());
        break;
      case 'delete':
        if (!$this->app->checkCSRF()) {
          $this->app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
        }
        $deleteEntry = $this->delete();
        if ($deleteEntry) {
          $this->app->display_success(200, "Successfully removed an entry from your anime list.");
        } else {
          $this->app->display_error(500, "An error occurred while removing an entry from your anime list.");
        }
        break;
      default:
        break;
    }
    return;
  }
}

?>