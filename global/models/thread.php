<?php
class Thread extends BaseObject {
  use Feedable, Commentable;

  public static $MODEL_TABLE = "threads";
  public static $MODEL_PLURAL = "threads";

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
      // $id = intval($app->dbConn->firstValue("SELECT `id` FROM `".static::$MODEL_TABLE."` WHERE `title` = ".$app->dbConn->escape(str_replace("_", " ", $title))." LIMIT 1"));
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
    } catch (DbException $e) {
      return False;
    }
    $dateTime = new DateTime('now', $this->app->serverTimeZone);
    $this->app->dbConn->table('thread_tags')->fields('tag_id', 'thread_id', 'created_user_id', 'created_at')
      ->values([$tag->id, $this->id, $currentUser->id, $dateTime->format("Y-m-d H:i:s")])
      ->insert();
    $this->tags[intval($tag->id)] = ['id' => intval($tag->id), 'name' => $tag->name];
    $this->fire('tag');
    $tag->fire('tag');
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
      if (!$this->app->dbConn->table('thread_tags')->where(['thread_id' => $this->id, 'tag_id' => $tagIDs])->limit(count($tagIDs))->delete()) {
        return False;
      }
    }
    foreach ($tagIDs as $tagID) {
      $this->tags[intval($tagID)]->fire('untag');
      unset($this->tags[intval($tagID)]);
    }
    $this->fire('untag');
    return True;
  }
  public function validate(array $thread) {
    $validationErrors = [];
    try {
      parent::validate($thread);
    } catch (ValidationException $e) {
      $validationErrors = array_merge($validationErrors, $e->messages);
    }
    if (!isset($thread['title']) || mb_strlen(trim($thread['title'])) < 1 || mb_strlen($thread['title']) > 100) {
      $validationErrors[] = "Thread must have a title between 1 and 100 characters";
    }
    if (!isset($thread['description']) || mb_strlen(trim($thread['description'])) < 1 || mb_strlen($thread['description']) > 600) {
      $validationErrors[] = "Thread must have a description between 1 and 600 characters";
    }
    if ($validationErrors) {
      throw new ValidationException($this->app, $thread, $validationErrors);
    } else {
      return True;
    }
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
          $tagID = intval($this->app->dbConn->table(Tag::$MODEL_TABLE)->fields('id')->where(['id' => $tagToAdd])->limit(1)->firstValue());
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
    $tagIDs = $this->app->dbConn->table('thread_tags')->fields('tag_id')->join('tags ON tags.id=tag_id')->where(['thread_id' => $this->id])->order('tags.tag_type_id ASC', 'tags.name ASC')->query();
    while ($tagID = $tagIDs->fetch()) {
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
    $userID = intval($this->app->dbConn->table($this->modelTable)->fields('user_id')->where(['id' => $this->id])->limit(1)->firstValue());
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
            $this->app->delayedMessage("Successfully created or updated ".$newAnime->title().".", "success");
            $this->app->redirect($newAnime->url("show"));
          } else {
            $this->app->delayedMessage("An error occurred while creating or updating ".$this->title().".", "error");
            $this->app->redirect($this->id === 0 ? $this->url("new") : $this->url("edit"));
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
          $this->app->delayedMessage('Successfully deleted '.$animeTitle.'.', "success");
          $this->app->redirect();
        } else {
          $this->app->delayedMessage('An error occurred while deleting '.$animeTitle.'.', "error");
          $this->app->redirect();
        }
        break;
      default:
      case 'index':
        $title = "Browse Anime";
        $resultsPerPage = 25;
        if (!isset($_REQUEST['search'])) {
          if ($this->app->user->isAdmin()) {
            $numPages = ceil($this->app->dbConn->table(Anime::$MODEL_TABLE)->fields('COUNT(*)')->count()/$resultsPerPage);
            $animeIDs = $this->app->dbConn->table(Anime::$MODEL_TABLE)->fields('anime.id')->order('anime.title ASC')->offset((intval($this->app->page)-1)*$resultsPerPage)->limit($resultsPerPage)->query();
          } else {
            $numPages = ceil($this->app->dbConn->table(Anime::$MODEL_TABLE)->fields('COUNT(*)')->where(['approved_on != ""'])->count()/$resultsPerPage);
            $animeIDs = $this->app->dbConn->table(Anime::$MODEL_TABLE)->fields('anime.id')->where(['approved_on != ""'])->order('anime.title ASC')->offset((intval($this->app->page)-1)*$resultsPerPage)->limit($resultsPerPage)->query();
          }
          $anime = [];
          while ($animeID = $animeIDs->fetch()) {
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
    return "/".escape_output(self::MODEL_URL())."/".($action !== "index" ? rawurlencode(rawurlencode($title))."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
}
?>