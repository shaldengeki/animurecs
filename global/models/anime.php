<?php
class Anime extends BaseObject {
  use Aliasable, Feedable, Commentable;

  public static $modelTable = "anime";
  public static $modelPlural = "anime";

  protected $title;
  protected $description;
  protected $episodeCount;
  protected $episodeLength;
  protected $approvedUser;
  protected $approvedOn;
  protected $imagePath;

  protected $entries;
  protected $latestEntries;
  protected $ratings;

  protected $ratingCount;
  protected $ratingAvg;
  protected $regularizedAvg;

  protected $tags;

  public function __construct(Application $app, $id=Null, $title=Null) {
    if ($title !== Null) {
      $id = intval($app->dbConn->queryFirstValue("SELECT `id` FROM `".static::$modelTable."` WHERE `title` = ".$app->dbConn->quoteSmart(str_replace("_", " ", $title))." LIMIT 1"));
    }
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->title = "New Anime";
      $this->description = $this->imagePath = $this->approvedOn = "";
      $this->episodeCount = $this->episodeLength = $this->ratingAvg = $this->regularizedAvg = 0;
      $this->tags = $this->comments = $this->entries = $this->approvedUser = $this->entries = $this->ratings = [];
    } else {
      $this->title = $this->description = $this->imagePath = $this->approvedOn = $this->episodeCount = $this->episodeLength = $this->tags = $this->comments = $this->entries = $this->approvedUser = $this->comments = $this->entries = $this->ratings = $this->ratingAvg = $this->regularizedAvg = Null;
    }
  }
  public function title() {
    return $this->returnInfo('title');
  }
  public function description($short=False) {
    return $short ? shortenText($this->returnInfo('description')) : $this->returnInfo('description');
  }
  public function episodeCount() {
    return $this->returnInfo('episodeCount');
  }
  public function episodeLength() {
    return $this->returnInfo('episodeLength');
  }
  public function createdAt() {
    return new DateTime($this->returnInfo('createdAt'), $this->app->serverTimeZone);
  }
  public function updatedAt() {
    return new DateTime($this->returnInfo('updatedAt'), $this->app->serverTimeZone);
  }
  public function imagePath() {
    return $this->returnInfo('imagePath') ? $this->returnInfo('imagePath') : "img/blank.png";
  }
  public function imageTag(array $params=Null) {
    return $this->image($this->imagePath(), $params);
  }
  public function approvedOn() {
    return $this->returnInfo('approvedOn');
  }
  public function approvedUser() {
    if ($this->approvedUser === Null) {
      $this->approvedUser = new User($this->app, intval($this->returnInfo('approvedUserId')));
    }
    return $this->approvedUser;
  }
  public function isApproved() {
    // Returns a bool reflecting whether or not the current anime is approved.
    if (!$this->approvedOn()) {
      return False;
    }
    return True;
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'remove_tag':
      case 'approve':
      case 'edit':
      case 'delete':
        if ($authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'token_search':
      case 'new':
        if ($authingUser->loggedIn()) {
          return True;
        }
        return False;
        break;
      case 'feed':
      case 'show':
      case 'related':
      case 'stats':
        if ($this->isApproved() || $authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
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
    $tags = $this->tags();
    if (isset($tags[intval($tag_id)])) {
      return True;
    }
    try {
      $tag = new Tag($this->app, intval($tag_id));
      $tag->getInfo();
    } catch (Exception $e) {
      return False;
    }
    $this->beforeUpdate([]);
    $tag->beforeUpdate([]);
    $insertDependency = $this->dbConn->stdQuery("INSERT INTO `anime_tags` (`tag_id`, `anime_id`, `created_user_id`, `created_at`) VALUES (".intval($tag->id).", ".intval($this->id).", ".intval($currentUser->id).", NOW())");
    if (!$insertDependency) {
      return False;
    }
    $this->afterUpdate([]);
    $tag->afterUpdate([]);
    $this->tags[intval($tag->id)] = ['id' => intval($tag->id), 'name' => $tag->name];
    return True;
  }
  public function drop_taggings(array $tags=Null) {
    /*
      Deletes tagging relations.
      Takes an array of tag ids as input, defaulting to all tags.
      Returns a boolean.
    */
    $this->tags();
    if ($tags === Null) {
      $tags = array_keys($this->tags()->tags());
    }
    $tagIDs = [];
    foreach ($tags as $tag) {
      if (is_numeric($tag)) {
        $tagIDs[] = intval($tag);
      }
    }
    if ($tagIDs) {
      $drop_taggings = $this->dbConn->stdQuery("DELETE FROM `anime_tags` WHERE `anime_id` = ".intval($this->id)." AND `tag_id` IN (".implode(",", $tagIDs).") LIMIT ".count($tagIDs));
      if (!$drop_taggings) {
        return False;
      }
    }
    foreach ($tagIDs as $tagID) {
      unset($this->tags[intval($tagID)]);
    }
    return True;
  }
  public function validate(array $anime) {
    if (!parent::validate($anime)) {
      return False;
    }
    if (!isset($anime['title']) || strlen($anime['title']) < 1) {
      return False;
    }
    if (isset($anime['description']) && (strlen($anime['description']) < 1 || strlen($anime['description']) > 1000)) {
      return False;
    }
    if (isset($anime['episode_count']) && ( !is_numeric($anime['episode_count']) || intval($anime['episode_count']) != $anime['episode_count'] || intval($anime['episode_count']) < 0) ) {
      return False;
    }
    if (isset($anime['episode_length']) && ( !is_numeric($anime['episode_length']) || intval($anime['episode_length']) != $anime['episode_length'] || intval($anime['episode_length']) < 0) ) {
      return False;
    }
    if (isset($anime['approved_on']) && $anime['approved_on'] && !strtotime($anime['approved_on'])) {
      return False;
    }
    if (isset($anime['approved_user_id']) && intval($anime['approved_user_id'])) {
      if (!is_numeric($anime['approved_user_id']) || intval($anime['approved_user_id']) != $anime['approved_user_id'] || intval($anime['approved_user_id']) <= 0) {
        return False;
      } else {
        try {
          $approvedUser = new User($this->app, intval($anime['approved_user_id']));
          $approvedUser->getInfo();
        } catch (Exception $e) {
          return False;
        }
      }
    }
    return True;
  }
  public function create_or_update(array $anime, array $whereConditions=Null) {
    // creates or updates a anime based on the parameters passed in $anime and this object's attributes.
    // returns False if failure, or the ID of the anime if success.
    
    // filter some parameters out first and replace them with their corresponding db fields.
    if (isset($anime['anime_tags']) && !is_array($anime['anime_tags'])) {
      $anime['anime_tags'] = explode(",", $anime['anime_tags']);
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
        return False;
      }
      $file_contents = file_get_contents($file_array['tmp_name']);
      if (!$file_contents) {
        return False;
      }
      $newIm = @imagecreatefromstring($file_contents);
      if (!$newIm) {
        return False;
      }
      $imageSize = getimagesize($file_array['tmp_name']);
      if ($imageSize[0] > 300 || $imageSize[1] > 300) {
        return False;
      }
      // move file to destination and save path in db.
      if (!is_dir(joinPaths(Config::APP_ROOT, "img", "anime", intval($this->id)))) {
        mkdir(joinPaths(Config::APP_ROOT, "img", "anime", intval($this->id)));
      }
      $imagePathInfo = pathinfo($file_array['tmp_name']);
      $imagePath = joinPaths("img", "anime", intval($this->id), $this->id.image_type_to_extension($imageSize[2]));
      if (!move_uploaded_file($file_array['tmp_name'], $imagePath)) {
        return False;
      }
    } else {
      if ($this->id != 0) {
        $imagePath = $this->imagePath();
      } else {
        $imagePath = "";  
      }
    }
    $anime['image_path'] = $imagePath;
    $result = parent::create_or_update($anime, $whereConditions);
    if (!$result) {
      return False;
    }

    // now process any taggings.
    if (isset($anime['anime_tags'])) {
      // drop any unneeded  tags.
      $tagsToDrop = [];
      foreach ($this->tags() as $tag) {
        if (!in_array($tag->id, $anime['anime_tags'])) {
          $tagsToDrop[] = intval($tag->id);
        }
      }
      $drop_tags = $this->drop_taggings($tagsToDrop);
      foreach ($anime['anime_tags'] as $tagToAdd) {
        // add any needed tags.
        if (!$this->tags() || !array_filter_by_property($this->tags()->tags(), 'id', $tagToAdd)) {
          // find this tagID.
          $tagID = intval($this->dbConn->queryFirstValue("SELECT `id` FROM `tags` WHERE `id` = ".intval($tagToAdd)." LIMIT 1"));
          if ($tagID) {
            $create_tagging = $this->create_or_update_tagging($tagID, $this->app->user);
          }
        }
      }
    }

    return $this->id;
  }
  public function delete($entries=Null) {
    // first, drop all taggings.
    if (!$this->drop_taggings()) {
      return False;
    }
    return parent::delete($entries);
  }
  public function getTags() {
    // retrieves a list of tag objects corresponding to tags belonging to this anime.
    $tags = [];
    $tagIDs = $this->dbConn->stdQuery("SELECT `tag_id` FROM `anime_tags` INNER JOIN `tags` ON `tags`.`id` = `tag_id` WHERE `anime_id` = ".intval($this->id)." ORDER BY `tags`.`tag_type_id` ASC, `tags`.`name` ASC");
    while ($tagID = $tagIDs->fetch_assoc()) {
      $tags[intval($tagID['tag_id'])] = new Tag($this->app, intval($tagID['tag_id']));
    }
    return $tags;
  }
  public function tags() {
    if ($this->tags === Null) {
      $this->tags = new TagGroup($this->app, $this->getTags());
    }
    return $this->tags;
  }
  public function getEntries() {
    // retrieves a list of id arrays corresponding to the list entries belonging to this anime.
    $returnList = [];
    $animeEntries = $this->dbConn->stdQuery("SELECT `id`, `user_id`, `anime_id`, `time`, `status`, `score`, `episode` FROM `anime_lists` WHERE `anime_id` = ".intval($this->id)." ORDER BY `time` DESC");
    while ($entry = $animeEntries->fetch_assoc()) {
      $newEntry = new AnimeEntry($this->app, intval($entry['id']), $entry);
      $returnList[intval($entry['id'])] = $newEntry;
    }
    return $returnList;
  }
  public function getLatestEntries() {
    // retrieves the latest entries for each user for this anime.
    // retrieves a list of $this->typeID, time, status, score, $this->partName arrays corresponding to the latest list entry for each thing the user has consumed.
    $returnList = $this->dbConn->queryAssoc("SELECT `anime_lists`.`id`, `user_id`, `time`, `score`, `status`, `episode` FROM (
                                              SELECT MAX(`id`) AS `id` FROM `anime_lists`
                                              WHERE `anime_id` = ".intval($this->id)."
                                              GROUP BY `user_id`
                                            ) `p` INNER JOIN `anime_lists` ON `anime_lists`.`id` = `p`.`id`
                                            WHERE `status` != 0
                                            ORDER BY `status` ASC, `score` DESC", 'user_id');
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
      if (!isset($users[$value->user()->id]) && intval($value->score) != 0) {
        $users[$value->user()->id] = 1;
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
    return $this->ratingAvg;
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
    $cacheKey = "Anime-".$this->id."-similar-".$start."-".$n;
    $result = $this->app->cache->get($cacheKey, $cas);
    if ($this->app->cache->resultCode() == Memcached::RES_NOTFOUND) {
      $result = $this->app->recsEngine->similarAnime($this, $start, $n);
      $this->app->cache->set($cacheKey, $result);
    }
    return new AnimeGroup($this->app, array_map(function($a) {
      return $a['id'];
    }, $result));
  }
  public function render() {
    switch($this->app->action) {
      case 'feed':
        $maxTime = isset($_REQUEST['maxTime']) ? new DateTime('@'.intval($_REQUEST['maxTime'])) : Null;
        $minTime = isset($_REQUEST['minTime']) ? new DateTime('@'.intval($_REQUEST['minTime'])) : Null;
        $entries = $this->entries($minTime, $maxTime, 50);
        echo $this->app->user->view('feed', ['entries' => $entries, 'numEntries' => 50, 'feedURL' => $this->url('feed'), 'emptyFeedText' => '']);
        exit;
        break;
      case 'token_search':
        $blankAlias = new Alias($this->app, 0, $this);
        $searchResults = $blankAlias->search($_REQUEST['term']);
        $animus = [];
        foreach ($searchResults as $anime) {
          $animus[] = ['id' => $anime->id, 'title' => $anime->title()];
        }
        echo json_encode($animus);
        exit;
        break;
      case 'related':
        $page = isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
        echo $this->view('related', ['page' => $page]);
        exit;
        break;
      case 'stats':
        echo $this->view('stats');
        exit;
        break;
      case 'new':
        $title = "Add an anime";
        $output = $this->view('new');
        break;
      case 'edit':
        if (isset($_POST['anime']) && is_array($_POST['anime'])) {
          $updateAnime = $this->create_or_update($_POST['anime']);
          if ($updateAnime) {
            // fetch the new ID.
            $newAnime = new Anime($this->app, $updateAnime);
            $this->app->delayedMessage("Successfully ".($this->id === 0 ? "created" : "updated")." ".$newAnime->title().".", "success");
            $this->app->redirect($newAnime->url("show"));
          } else {
            $this->app->delayedMessage("An error occurred while ".($this->id === 0 ? "creating" : "updating")." ".$this->title().".", "error");
            $this->app->redirect(($this->id === 0 ? $this->url("new") : $this->url("edit")));
          }
        }
        if ($this->id == 0) {
          $this->app->display_error(404);
        }
        $title = "Editing ".escape_output($this->title());
        $output .= $this->view('edit');
        break;
      case 'show':
        if ($this->id == 0) {
          $this->app->display_error(404);
        }
        $title = escape_output($this->title());
        $output = $this->view("show", ['entries' => $this->entries(Null, Null, 50), 'numEntries' => 50, 'feedURL' => $this->url('feed'), 'emptyFeedText' => "<blockquote><p>No entries yet - ".$this->app->user->link("show", "be the first!")."</p></blockquote>"]);
        break;
      case 'delete':
        if ($this->id == 0) {
          $this->app->display_error(404);
        }
        if (!$this->app->checkCSRF()) {
          $this->app->display_error(403);
        }
        $animeTitle = $this->title();
        $deleteAnime = $this->delete();
        if ($deleteAnime) {
          $this->app->delayedMessage('Successfully deleted '.$animeTitle.'.', 'success');
          $this->app->redirect();
        } else {
          $this->app->delayedMessage('An error occurred while deleting '.$animeTitle.'.', 'error');
          $this->app->redirect();
        }
        break;
      default:
      case 'index':
        $title = "Browse Anime";
        $resultsPerPage = 25;
        if (!isset($_REQUEST['search'])) {
          if ($this->app->user->isAdmin()) {
            $animeIDs = $this->dbConn->stdQuery("SELECT `".static::$modelTable."`.`id` FROM `".static::$modelTable."` ORDER BY `".static::$modelTable."`.`title` ASC LIMIT ".((intval($this->app->page)-1)*$resultsPerPage).",".intval($resultsPerPage));
            $numPages = ceil($this->dbConn->queryCount("SELECT COUNT(*) FROM `".static::$modelTable."`")/$resultsPerPage);
          } else {
            $animeIDs = $this->dbConn->stdQuery("SELECT `".static::$modelTable."`.`id` FROM `".static::$modelTable."` WHERE `approved_on` != '' ORDER BY `".static::$modelTable."`.`title` ASC LIMIT ".((intval($this->app->page)-1)*$resultsPerPage).",".intval($resultsPerPage));
            $numPages = ceil($this->dbConn->queryCount("SELECT COUNT(*) FROM `".static::$modelTable."` WHERE `approved_on` != ''")/$resultsPerPage);
          }
          $anime = [];
          while ($animeID = $animeIDs->fetch_assoc()) {
            $anime[] = new Anime($this->app, intval($animeID['id']));
          }
        } else {
          $blankAlias = new Alias($this->app, 0, $this);
          $searchResults = $blankAlias->search($_REQUEST['search']);
          $anime = array_slice($searchResults, (intval($this->app->page)-1)*$resultsPerPage, intval($resultsPerPage));
          $numPages = ceil(count($searchResults)/$resultsPerPage);
        }
        $output = $this->view("index", ['anime' => $anime, 'numPages' => $numPages, 'resultsPerPage' => $resultsPerPage]);
        break;
    }
    return $this->app->render($output, ['subtitle' => $title]);
  }
  public function scoreBar($score=Null) {
    // returns markup for a score bar for a score given to this anime.
    if ($score === Null || $score == 0) {
      return "<div class='progress progress-info'><div class='bar' style='width: 0%'></div>Unknown</div>";
    }
    if ($score >= 7.5) {
      $barClass = "danger";
    } elseif ($score >= 5.0) {
      $barClass = "warning";
    } elseif ($score >= 2.5) {
      $barClass = "success";
    } else {
      $barClass = "info";
    }
    return "<div class='progress progress-".$barClass."'><div class='bar' style='width: ".round($score*10.0)."%'>".round($score, 1)."/10</div></div>";
  }
  public function formatFeedEntry(BaseEntry $entry) {
    return $entry->user->animeList->formatFeedEntry($entry);
  }
  public function url($action="show", $format=Null, array $params=Null, $title=Null) {
    // returns the url that maps to this object and the given action.
    if ($title === Null) {
      $title = $this->title();
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output(self::modelUrl())."/".($action !== "index" ? rawurlencode(rawurlencode($title))."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
}
?>