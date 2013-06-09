<?php
class Thread extends BaseObject {
  use Feedable, Commentable;

  public static $modelTable = "threads";
  public static $modelPlural = "threads";

  protected $title;
  protected $description;

  protected $entries;
  protected $latestEntries;

  protected $user;
  protected $tags;

  public function __construct(Application $app, $id=Null, $title=Null) {
    if ($title !== Null) {
      // split the ID off of the title.
      $splitTitle = explode("-", $title);
      $id = intval($splitTitle[0]);
      // $id = intval($app->dbConn->queryFirstValue("SELECT `id` FROM `".$modelTable."` WHERE `title` = ".$app->dbConn->quoteSmart(str_replace("_", " ", $title))." LIMIT 1"));
    }
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->title = "New Thread";
      $this->description = "Your description here.";
      $this->tags = $this->comments = $this->entries = $this->entries = [];
    } else {
      $this->title = $this->description = $this->tags = $this->comments = $this->entries = $this->comments = $this->entries = Null;
    }
  }
  public function title() {
    return $this->returnInfo('title');
  }
  public function description() {
    return $this->returnInfo('description');
  }
  public function createdAt() {
    return new DateTime($this->returnInfo('createdAt'), $this->app->serverTimeZone);
  }
  public function updatedAt() {
    return new DateTime($this->returnInfo('updatedAt'), $this->app->serverTimeZone);
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'remove_tag':
      case 'edit':
      case 'delete':
        if ($this->user()->id == $authingUser->id || $authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'new':
        if ($authingUser->loggedIn()) {
          return True;
        }
        return False;
        break;
      case 'token_search':
      case 'feed':
      case 'show':
      case 'related':
        return True;
        break;
      case 'index':
        if ($authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      default:
        return False;
        break;
    }
  }
  public function create_or_update_tagging($tag_id, User $currentUser) {
    /*
      Creates or updates an existing tagging for the current thread.
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
    $insertDependency = $this->dbConn->stdQuery("INSERT INTO `thread_tags` (`tag_id`, `thread_id`, `created_user_id`, `created_at`) VALUES (".intval($tag->id).", ".intval($this->id).", ".intval($currentUser->id).", NOW())");
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
      $drop_taggings = $this->dbConn->stdQuery("DELETE FROM `thread_tags` WHERE `thread_id` = ".intval($this->id)." AND `tag_id` IN (".implode(",", $tagIDs).") LIMIT ".count($tagIDs));
      if (!$drop_taggings) {
        return False;
      }
    }
    foreach ($tagIDs as $tagID) {
      unset($this->tags[intval($tagID)]);
    }
    return True;
  }
  public function validate(array $thread) {
    if (!parent::validate($thread)) {
      return False;
    }
    if (!isset($thread['title']) || strlen(trim($thread['title'])) < 1) {
      return False;
    }
    if (!isset($thread['description']) || strlen(trim($thread['description'])) < 1) {
      return False;
    }
    return True;
  }
  public function create_or_update(array $thread, array $whereConditions=Null) {
    // creates or updates a thread based on the parameters passed in $thread and this object's attributes.
    // returns False if failure, or the ID of the thread if success.
    
    // filter some parameters out first and replace them with their corresponding db fields.
    if (isset($thread['thread_tags']) && !is_array($thread['thread_tags'])) {
      $thread['thread_tags'] = explode(",", $thread['thread_tags']);
    }

    $result = parent::create_or_update($thread, $whereConditions);
    if (!$result) {
      return False;
    }

    // now process any taggings.
    if (isset($thread['thread_tags'])) {
      // drop any unneeded tags.
      $tagsToDrop = [];
      foreach ($this->tags() as $tag) {
        if (!in_array($tag->id, $thread['thread_tags'])) {
          $tagsToDrop[] = intval($tag->id);
        }
      }
      $drop_tags = $this->drop_taggings($tagsToDrop);
      foreach ($thread['thread_tags'] as $tagToAdd) {
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
    $tagIDs = $this->dbConn->stdQuery("SELECT `tag_id` FROM `thread_tags` INNER JOIN `tags` ON `tags`.`id` = `tag_id` WHERE `thread_id` = ".intval($this->id)." ORDER BY `tags`.`tag_type_id` ASC, `tags`.`name` ASC");
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
  public function getUser() {
    // retrieves the user object that this thread belongs to.
    $userID = intval($this->dbConn->queryFirstValue("SELECT `user_id` FROM `".$this->modelTable."` WHERE `id` = ".intval($this->id)." LIMIT 1"));
    return new User($this->app, $userID);
  }
  public function user() {
    if ($this->user === Null) {
      $this->user = $this->getUser();
    }
    return $this->user;
  }
  public function getEntries() {
    // the entries in a thread are the comments on it.
    return $this->getComments();
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
    if (isset($_POST['anime']) && is_array($_POST['anime'])) {
      $updateAnime = $this->create_or_update($_POST['anime']);
      if ($updateAnime) {
        // fetch the new ID.
        $newAnime = new Anime($this->app, $updateAnime);
        $this->app->redirect($newAnime->url("show"), ['status' => "Successfully created or updated ".$newAnime->title().".", 'class' => 'success']);
      } else {
        $this->app->redirect(($this->id === 0 ? $this->url("new") : $this->url("edit")), ['status' => "An error occurred while creating or updating ".$this->title().".", 'class' => 'error']);
      }
    }
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
          $this->app->redirect("/anime/", ['status' => 'Successfully deleted '.$animeTitle.'.', 'class' => 'success']);
        } else {
          $this->app->redirect($this->url("show"), ['status' => 'An error occurred while deleting '.$animeTitle.'.', 'class' => 'error']);
        }
        break;
      default:
      case 'index':
        $title = "Browse Anime";
        $resultsPerPage = 25;
        if (!isset($_REQUEST['search'])) {
          if ($this->app->user->isAdmin()) {
            $animeIDs = $this->dbConn->stdQuery("SELECT `anime`.`id` FROM `anime` ORDER BY `anime`.`title` ASC LIMIT ".((intval($this->app->page)-1)*$resultsPerPage).",".intval($resultsPerPage));
            $numPages = ceil($this->dbConn->queryCount("SELECT COUNT(*) FROM `anime`")/$resultsPerPage);
          } else {
            $animeIDs = $this->dbConn->stdQuery("SELECT `anime`.`id` FROM `anime` WHERE `approved_on` != '' ORDER BY `anime`.`title` ASC LIMIT ".((intval($this->app->page)-1)*$resultsPerPage).",".intval($resultsPerPage));
            $numPages = ceil($this->dbConn->queryCount("SELECT COUNT(*) FROM `anime` WHERE `approved_on` != ''")/$resultsPerPage);
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
  public function formatFeedEntry(BaseEntry $entry) {
    return $entry->user->animeList->formatFeedEntry($entry);
  }
  // public function tagList(User $currentUser) {
  //   $output = "<ul class='tagList'>\n";
  //   $tagCounts = $this->tags()->load('info')->tagCounts();
  //   foreach ($tagCounts as $tagID => $count) {
  //     $output .= "<li>".$this->tags()[$tagID]->link("show", $this->tags()[$tagID]->name)." ".intval($count)."</li>\n";
  //   }
  //   $output .= "</ul>\n";
  //   return $output;
  // }
  public function url($action="show", $format=Null, array $params=Null, $title=Null) {
    // returns the url that maps to this object and the given action.
    if ($title === Null) {
      $title = $this->id."-".$this->title();
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output(self::modelUrl())."/".($action !== "index" ? rawurlencode(rawurlencode($title))."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
}
?>