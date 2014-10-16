<?php
class Anime extends Model {
  use Aliasable, Feedable, Commentable;

  public static $TABLE = "anime";
  public static $PLURAL = "anime";
  public static $FIELDS = [
    'id' => [
      'type' => 'int',
      'db' => 'id'
    ],
    'title' => [
      'type' => 'str',
      'db' => 'title'
    ],
    'description' => [
      'type' => 'str',
      'db' => 'description'
    ],
    'episodeCount' => [
      'type' => 'int',
      'db' => 'episode_count'
    ],
    'episodeLength' => [
      'type' => 'int',
      'db' => 'episode_length'
    ],
    'startedOn' => [
      'type' => 'date',
      'db' => 'started_on'
    ],
    'endedOn' => [
      'type' => 'date',
      'db' => 'ended_on'
    ],
    'createdAt' => [
      'type' => 'date',
      'db' => 'created_at'
    ],
    'updatedAt' => [
      'type' => 'date',
      'db' => 'updated_at'
    ],
    'imagePath' => [
      'type' => 'str',
      'db' => 'image_path'
    ],
    'approvedOn' => [
      'type' => 'date',
      'db' => 'approved_on'
    ],
    'approvedUserId' => [
      'type' => 'int',
      'db' => 'approved_user_id'
    ]
  ];
  public static $JOINS = [
    'tags' => [
      'obj' => 'Tag',
      'table' => 'tags',
      'own_col' => 'id',
      'join_col' => 'id',
      'type' => 'habtm',
      'join_table' => 'anime_tags',
      'join_table_own_col' => 'anime_id',
      'join_table_join_col' => 'tag_id'
    ],
    'approvedUser' => [
      'obj' => 'User',
      'table' => 'users',
      'own_col' => 'approved_user_id',
      'join_col' => 'id',
      'type' => 'one'
    ],
    'comments' => [
      'obj' => 'CommentEntry',
      'table' => 'comments',
      'own_col' => 'id',
      'join_col' => 'parent_id',
      'condition' => "comments.type = 'Anime'",
      'type' => 'many'
    ],
    'entries' => [
      'obj' => 'AnimeEntry',
      'table' => 'anime_lists',
      'own_col' => 'id',
      'join_col'  => 'anime_id',
      'type' => 'many'
    ]
  ];

  public $entries;
  public $latestEntries;
  public $ratings;

  public $ratingCount;
  public $ratingAvg;
  public $regularizedAvg;

  public function __construct(Application $app, $id=Null) {
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->title = "New Anime";
      $this->description = $this->imagePath = $this->approvedOn = "";
      $this->episodeCount = $this->episodeLength = $this->ratingAvg = $this->regularizedAvg = 0;
      $this->tags = $this->comments = $this->entries = $this->approvedUser = $this->ratings = [];
    }
  }
  public function description($short=False) {
    return $short ? shortenText($this->description) : $this->description;
  }
  public function imagePath() {
    return $this->imagePath ? $this->imagePath : "img/blank.png";
  }
  public function imageTag(array $params=Null) {
    return $this->image($this->imagePath(), $params);
  }
  public function approvedUser() {
    if ($this->approvedUser === Null) {
      $this->approvedUser = new User($this->app, intval($this->approvedUserId));
    }
    return $this->approvedUser;
  }
  public function isApproved() {
    // Returns a bool reflecting whether or not the current anime is approved.
    return (bool) $this->approvedOn;
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      /* cases where user must be staff. */
      case 'remove_tag':
      case 'approve':
      case 'edit':
      case 'delete':
        if ($authingUser->isStaff()) {
          return True;
        }
        return False;
        break;

      /* cases where user must be logged in. */
      case 'new':
        if ($authingUser->loggedIn()) {
          return True;
        }
        return False;
        break;

      /* cases where this tag must be approved or user must be staff. */
      case 'feed':
      case 'show':
      case 'related':
      case 'stats':
      case 'tags':
        if ($this->isApproved() || $authingUser->isStaff()) {
          return True;
        }
        return False;
        break;

      /* public views. */
      case 'token_search':
      case 'index':
        return True;
        break;
      default:
        return False;
        break;
    }
  }
  public function create_or_update_tagging($tag_id, User $currentUser) {
    /*
      Creates or updates an existing tagging for the current anime.
      Takes a tag ID.
      Returns a boolean.
    */
    // check to see if this is an update.
    if (isset($this->tags[intval($tag_id)])) {
      return True;
    }
    try {
      $tag = new Tag($this->app, intval($tag_id));
    } catch (DatabaseException $e) {
      return False;
    }
    $datetime = new DateTime('now', $this->app->serverTimeZone);

    $this->app->dbConn->table('anime_tags')->fields('tag_id', 'anime_id', 'created_user_id', 'created_at')
      ->values([$tag->id, $this->id, $currentUser->id, $datetime->format("Y-m-d H:i:s")])
      ->insert();

    $this->fire('tag');
    $tag->fire('tag');
    $this->tags[intval($tag->id)] = ['id' => intval($tag->id), 'name' => $tag->name];
    return True;
  }
  public function drop_taggings(array $tags=Null) {
    /*
      Deletes tagging relations.
      Takes an array of tag ids as input, defaulting to all tags.
      Returns a boolean.
    */
    if ($tags === Null) {
      $tags = array_keys($this->tags);
    }
    $tagIDs = [];
    foreach ($tags as $tag) {
      if (is_numeric($tag)) {
        $tagIDs[] = intval($tag);
      }
    }
    if ($tagIDs) {
      $this->app->dbConn->table('anime_tags')
        ->where(['anime_id' => $this->id, 'tag_id' => $tagIDs])
        ->limit(count($tagIDs))
        ->delete();
    }
    foreach ($tagIDs as $tagID) {
      $this->tags[intval($tagID)]->fire('untag');
      unset($this->tags[intval($tagID)]);
    }
    $this->fire('untag');
    return True;
  }
  public function validate(array $anime) {
    $validationErrors = [];
    try {
      parent::validate($anime);
    } catch (ValidationException $e) {
      $validationErrors = array_merge($validationErrors, $e->messages);
    }
    if (!isset($anime['title']) || mb_strlen($anime['title']) < 1) {
      $validationErrors[] = "Anime must have a non-blank title";
    }
    if (isset($anime['description']) && (mb_strlen($anime['description']) < 1 || mb_strlen($anime['description']) > 1000)) {
      $validationErrors[] = "Anime description must be between 1 and 1000 characters in length";
    }
    if ($anime['episode_count'] && ( !is_integral($anime['episode_count']) || intval($anime['episode_count']) < 0) ) {
      $validationErrors[] = "Anime episode count must be an integer greater than or equal to 0";
    }
    if ($anime['episode_length'] && ( !is_integral($anime['episode_length']) || intval($anime['episode_length']) < 0) ) {
      $validationErrors[] = "Anime episode length must be an integer greater than 0";
    }
    if (isset($anime['approved_on']) && $anime['approved_on'] && !strtotime($anime['approved_on'])) {
      $validationErrors[] = "Anime approved time must be a parseable date-time stamp";
    }
    if (isset($anime['approved_user_id']) && intval($anime['approved_user_id'])) {
      if (!is_integral($anime['approved_user_id']) || intval($anime['approved_user_id']) <= 0) {
        $validationErrors[] = "User who approved this anime must have a valid userID";
      } else {
        try {
          $approvedUser = new User($this->app, intval($anime['approved_user_id']));
          $approvedUser->load();
        } catch (Exception $e) {
          $validationErrors[] = "User who approved this anime must exist";
        }
      }
    }
    if ($validationErrors) {
      throw new ValidationException($this->app, $anime, $validationErrors);
    } else {
      return True;
    }
  }
  public function create_or_update(array $anime, array $whereConditions=Null) {
    // creates or updates a anime based on the parameters passed in $anime and this object's attributes.
    // returns False if failure, or the ID of the anime if success.
    
    // filter some parameters out first and replace them with their corresponding db fields.
    if (isset($anime['anime_tags']) && !is_array($anime['anime_tags'])) {
      $animeTags = explode(",", $anime['anime_tags']);
      unset($anime['anime_tags']);
    }
    if ((isset($anime['approved']) && intval($anime['approved']) == 1 && !$this->isApproved())) {
      $anime['approved_on'] = unixToMySQLDateTime();
    } elseif ((!isset($anime['approved']) || intval($anime['approved']) == 0)) {
      $anime['approved_on'] = Null;
      $anime['approved_user_id'] = 0;
    }
    unset($anime['approved']);

    if (isset($anime['episode_minutes'])) {
      $anime['episode_length'] = intval($anime['episode_minutes']) * 60;
    }
    unset($anime['episode_minutes']);

    // process uploaded image.
    $file_array = $_FILES['anime_image'];
    $imagePath = "";
    if ($file_array['tmp_name'] && is_uploaded_file($file_array['tmp_name'])) {
      if ($file_array['error'] != UPLOAD_ERR_OK) {
        throw new ValidationException($this->app, $file_array, 'An error occurred while uploading anime cover image');
      }
      $file_contents = file_get_contents($file_array['tmp_name']);
      if (!$file_contents) {
        throw new ValidationException($this->app, $file_array, 'An error occurred while reading anime cover image');
      }
      $newIm = @imagecreatefromstring($file_contents);
      if (!$newIm) {
        throw new ValidationException($this->app, $file_array, 'An error occurred while creating anime cover image');
      }
      $imageSize = getimagesize($file_array['tmp_name']);
      if ($imageSize[0] > 300 || $imageSize[1] > 300) {
        throw new ValidationException($this->app, $imageSize, 'Anime cover image dimensions must be <= 300px');
      }
      // move file to destination and save path in db.
      if (!is_dir(joinPaths(Config::APP_ROOT, "img", "anime", intval($this->id)))) {
        mkdir(joinPaths(Config::APP_ROOT, "img", "anime", intval($this->id)));
      }
      $imagePathInfo = pathinfo($file_array['tmp_name']);
      $imagePath = joinPaths("img", "anime", intval($this->id), $this->id.image_type_to_extension($imageSize[2]));
      if (!move_uploaded_file($file_array['tmp_name'], $imagePath)) {
        throw new ValidationException($this->app, $file_array, 'An error occurred while moving anime cover image');
      }
    } else {
      if ($this->id != 0) {
        $imagePath = $this->imagePath();
      } else {
        $imagePath = "";  
      }
    }
    $anime['image_path'] = $imagePath;
    $this->id = parent::create_or_update($anime, $whereConditions);

    // now process any taggings.
    if (isset($animeTags)) {
      // drop any unneeded tags.
      $tagsToDrop = [];
      foreach ($this->tags as $tag) {
        if (!in_array($tag->id, $animeTags)) {
          $tagsToDrop[] = intval($tag->id);
        }
      }
      $this->drop_taggings($tagsToDrop);
      foreach ($animeTags as $tagToAdd) {
        // add any needed tags.
        if (!$this->tags || !array_filter_by_property($this->tags, 'id', $tagToAdd)) {
          // find this tagID.
          try {
            $thisTag = Tag::findById($this->app, $tagToAdd);
            $this->create_or_update_tagging($thisTag->id, $this->app->user);
          } catch (DatabaseException $e) {
            // don't create a tagging for a non-existent tag.
          }
        }
      }
    }

    return $this->id;
  }
  public function delete($entries=Null) {
    // first, drop all taggings.
    $this->drop_taggings();
    return parent::delete($entries);
  }
  public function getEntries() {
    // retrieves a list of id arrays corresponding to the list entries belonging to this anime.
    $returnList = [];
    $animeEntries = $this->app->dbConn->table(AnimeList::$TABLE)
                      ->fields('id', 'user_id', 'anime_id', 'time', 'status', 'score', 'episode')
                      ->where(['anime_id' => $this->id])
                      ->order('time DESC')
                      ->query();
    while ($entry = $animeEntries->fetch()) {
      $newEntry = new AnimeEntry($this->app, intval($entry['id']));
      $returnList[intval($entry['id'])] = $newEntry->set($entry);
    }
    return $returnList;
  }
  public function getLatestEntries() {
    // retrieves the latest entries for each user for this anime.
    // retrieves a list of typeID, time, status, score, part_name arrays corresponding to the latest list entry for each thing the user has consumed.
    $entryQuery = $this->app->dbConn->raw("SELECT anime_lists.id, anime_lists.user_id, anime_lists.time, anime_lists.score, anime_lists.status, anime_lists.episode FROM anime_lists
      LEFT OUTER JOIN anime_lists anime_lists2 ON anime_lists.user_id = anime_lists2.user_id
        AND anime_lists.anime_id = anime_lists2.anime_id
        AND anime_lists.time < anime_lists2.time
      WHERE anime_lists.anime_id = ".intval($this->id)."
        AND anime_lists2.time IS NULL
        AND anime_lists.status != 0
      ORDER BY status ASC, score DESC");
    $returnList = [];
    while ($row = $entryQuery->fetch()) {
      $returnList[$row['user_id']] = $row;
    }
    return $returnList;
  }
  public function latestEntries() {
    if ($this->latestEntries === Null) {
      $app = $this->app;
      $this->latestEntries = new EntryGroup($this->app, array_map(function($a) use ($app) {
        return new AnimeEntry($app, $a['id']);
      }, $this->getLatestEntries()));
    }
    return $this->latestEntries;
  }
  public function getRatings() {
    $users = [];
    $ratings = array_filter($this->entries()->entries(), function ($value) use (&$users) {
      if (!isset($users[$value->user->id]) && round(floatval($value->score), 2) != 0) {
        $users[$value->user->id] = 1;
        return True;
      }
      return False;
    });
    return $ratings;
  }
  public function ratings() {
    if ($this->ratings === Null) {
      $this->ratings = $this->getRatings();
    }
    return $this->ratings;    
  }
  public function calcRatingStats() {
    $ratingSum = $ratingCount = 0;
    foreach ($this->ratings() as $rating) {
      $ratingSum += $rating->score;
      $ratingCount++;
    }
    $this->ratingCount = $ratingCount;
    if ($ratingCount != 0) {
      $this->ratingAvg = $ratingSum / $ratingCount;
    } else {
      $this->ratingAvg = 0;
    }
  }
  public function ratingCount() {
    if ($this->ratingAvg === Null) {
      $this->calcRatingStats();
    }
    return $this->ratingCount;
  }
  public function ratingAvg() {
    if ($this->ratingAvg === Null) {
      $this->calcRatingStats();
    }
    return $this->ratingAvg; 
  }
  public function predict(User $user) {
    return $this->app->recsEngine->predict($user, $this);
  }
  public function similar($start=0, $n=20) {
    // Returns an AnimeGroup consisting of the n most-similar anime to the current anime.
    // pull from cache if possible.
    $cas = "";
    $cacheKey = $this->cacheKey(['similar', $start, $n]);
    $result = $this->app->cache->get($cacheKey, $cas);
    if ($this->app->cache->resultCode() == Memcached::RES_NOTFOUND) {
      $result = $this->app->recsEngine->similarAnime($this, $start, $n);
      $this->app->cache->set($cacheKey, $result);
    }
    return new AnimeGroup($this->app, $result === Null ? [] : array_map(function($a) {
      return $a['id'];
    }, $result));
  }
  public function render() {
    if ($this->app->action == 'new' || $this->app->action == 'edit') {
      if (isset($_POST['anime']) && is_array($_POST['anime'])) {
        $verbProgressive = $this->id === 0 ? "creating" : "updating";
        $verbPast = $this->id === 0 ? "created" : "updated";
        $updateAnime = $this->create_or_update($_POST['anime']);
        if ($updateAnime) {
          // fetch the new ID.
          $newAnime = new Anime($this->app, $updateAnime);
          $this->app->display_success(200, "Successfully ".$verbPast." ".$newAnime->title.".", "success");
        } else {
          $this->app->display_error(500, "An error occurred while ".$verbProgressive." ".$this->title.".");
        }
      }
    }
    switch($this->app->action) {
      case 'feed':
        $maxTime = isset($_REQUEST['maxTime']) ? new DateTime('@'.intval($_REQUEST['maxTime'])) : Null;
        $minTime = isset($_REQUEST['minTime']) ? new DateTime('@'.intval($_REQUEST['minTime'])) : Null;
        foreach (array_sort_by_method($this->entries($minTime, $maxTime, 50)->load('comments')->entries(), 'time', [], 'desc') as $entry) {
          $entries[] = $entry->serialize();
        }
        $this->app->display_response(200, $entries);
        break;
      case 'token_search':
        $blankAlias = new Alias($this->app, 0, $this);
        $searchResults = $blankAlias->search($_REQUEST['term']);
        $animus = [];
        foreach ($searchResults as $result) {
          $animus[] = ['id' => $result['anime']->id, 'title' => $result['alias']];
        }
        $this->app->display_response(200, $animus);
        break;
      case 'related':
        $perPage = 10;
        try {
          $anime = $this->similar(($this->app->page - 1) * $perPage, $perPage);
        } catch (CurlException $e) {
          $this->app->display_error(503, "We couldn't fetch related anime for this show. Please try again later!");
        }
        $anime = array_map(function ($a) {
          return $a->serialize();
        }, $anime->anime());
        $this->app->display_response(200, $anime);
        break;
      case 'average_rating_timeline':
        
        break;
      case 'stats':
        /* TODO: api */
        $times = $this->app->dbConn->table(AnimeList::$TABLE)
          ->fields("UNIX_TIMESTAMP(MIN(time)) AS start", "UNIX_TIMESTAMP(MAX(time)) AS end")
          ->where([
            'anime_id' => $this->id,
            'score != 0',
            'status != 0'
          ])
          ->firstRow();
        echo $this->view('stats', [
          'start' => $times['start'],
          'end' => $times['end']
        ]);
        exit;
        break;
      case 'tags':
        // order the tags in this animeGroup's tags by tagType id
        $tagTypes = TagType::GetList($this->app);
        $tagsByType = [];
        foreach ($tagTypes as $tagType) {
          $tagsByType[$tagType->name] = [];
        }

        foreach ($this->tags as $tag) {
          $tagsByType[$tagTypes[$tag->type->id]->name][] = [
            'tag' => $tag->serialize(),
            'count' => $tag->numAnime()
          ];
        }
        $this->app->display_response(200, $tagsByType);
        break;
      case 'show':
        if ($this->id == 0) {
          $this->app->display_error(404, "No such anime found.");
        }
        $this->app->display_response(200, $this->serialize());
        break;
      case 'delete':
        if ($this->id == 0) {
          $this->app->display_error(404, "No such anime found.");
        }
        if (!$this->app->checkCSRF()) {
          $this->app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
        }
        $cachedTitle = $this->title;
        $deleteAnime = $this->delete();
        if ($deleteAnime) {
          $this->app->display_success(200, 'Successfully deleted '.$cachedTitle.'.');
        } else {
          $this->app->display_error(500, 'An error occurred while deleting '.$cachedTitle.'.');
        }
        break;
      default:
      case 'index':
        $perPage = 25;
        if (!isset($_REQUEST['search'])) {
          // top anime listing.
          $numPages = ceil(Anime::Count($this->app)/$perPage);
          $this->app->dbConn->table('( SELECT user_id, anime_id, MAX(time) AS time FROM anime_lists GROUP BY user_id, anime_id) p')
            ->fields('anime_lists.anime_id', 'AVG(score) AS avg', 'STDDEV(score) AS stddev', 'COUNT(*) AS count', '((((AVG(score)-1)/9) + ( POW(STDDEV(score), 2) / (2.0 * COUNT(*)) ) - STDDEV(score) * SQRT( ((AVG(score)-1)/9) * (1.0 - ((AVG(score)-1)/9)) / COUNT(*) + ( POW(STDDEV(score), 2) / ( 4.0 * POW(COUNT(*), 2) ) ) )) / (1.0 + (POW(STDDEV(score), 2) / COUNT(*))) * 9) + 1 AS wilson')
            ->join('anime_lists ON anime_lists.user_id=p.user_id && anime_lists.anime_id=p.anime_id && anime_lists.time=p.time');
          if (!$this->app->user->isAdmin()) {
            $this->app->dbConn->join('anime ON anime.id=anime_lists.anime_id')
              ->where(['anime.approved_on IS NOT NULL']);
          }
          $animeQuery = $this->app->dbConn->where(['anime_lists.score != 0'])
              ->group('p.anime_id')
              ->having('COUNT(*) > 9')
              ->order('wilson DESC')
              ->offset(($this->app->page-1)*$perPage)
              ->limit($perPage)
              ->query();
          $anime = [];
          $wilsons = [];
          while ($animeRow = $animeQuery->fetch()) {
            $anime[] = new Anime($this->app, intval($animeRow['anime_id']));
            if (isset($animeRow['wilson'])) {
              $wilsons[$animeRow['anime_id']] = $animeRow['wilson'];
            }
          }
        } else {
          // user is searching for an anime.
          $blankAlias = new Alias($this->app, 0, $this);
          $searchResults = $blankAlias->search($_REQUEST['search']);
          $anime = array_slice($searchResults, (intval($this->app->page)-1)*$perPage, intval($perPage));
          $aliases = [];
          foreach ($anime as $a) {
            $aliases[$a['anime']->id] = $a['alias'];
          }
          $anime = array_map(function($a) { return $a['anime']; }, $anime);
          $numPages = ceil(count($searchResults)/$perPage);
        }
        $group = new AnimeGroup($this->app, $anime);
        $resultAnime = array_map(function ($a) use ($wilsons) {
          $serial = $a->serialize();
          if (isset($wilsons[$a->id])) {
            $serial['wilson_score'] = round(floatval($wilsons[$a->id]), 2);
          }
          return $serial;
        }, $group->load('info')->anime());
        $this->app->display_response(200, [
          'page' => $this->app->page,
          'pages' => $numPages,
          'anime' => $resultAnime,
        ]);
        break;
    }
    return $this->app->render($output, ['subtitle' => $title]);
  }
  public function formatFeedEntry(BaseEntry $entry) {
    return $entry->user->animeList->formatFeedEntry($entry);
  }
  public function url($action="show", $format=Null, array $params=Null, $title=Null) {
    // returns the url that maps to this object and the given action.
    if ($title === Null) {
      $title = $this->title;
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output(self::MODEL_URL())."/".($action !== "index" ? rawurlencode($title)."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
}
?>