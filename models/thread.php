<?php
class Thread extends Model {
  use Feedable, Commentable;

  public static $TABLE = "threads";
  public static $PLURAL = "threads";
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
    'createdAt' => [
      'type' => 'date',
      'db' => 'created_at'
    ],
    'updatedAt' => [
      'type' => 'date',
      'db' => 'updated_at'
    ],
    'userId' => [
      'type' => 'int',
      'db' => 'user_id'
    ]
  ];
  public static $JOINS = [
    'tags' => [
      'obj' => 'Tag',
      'table' => 'tags',
      'own_col'  => 'id',
      'join_col' => 'id',
      'type' => 'habtm',
      'join_table' => 'tag_threads',
      'join_table_own_col' => 'thread_id',
      'join_table_join_col' => 'tag_id'
    ],
    'user' => [
      'obj' => 'User',
      'table' => 'users',
      'own_col'  => 'user_id',
      'join_col' => 'id',
      'type' => 'one'
    ],
    // 'entries' => [
    //   'obj' => 'CommentEntry',
    //   'table' => 'comments',
    //   'own_col'  => 'id',
    //   'join_col' => 'comments.parent_id',
    //   'condition' => Comment::FULL_DB_FIELD_NAME('type')."=".Thread::MODEL_NAME(),
    //   'type' => 'many'
    // ],
  ];
  protected $entries;
  protected $latestEntries;

  public function __construct(Application $app, $id=Null) {
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->title = "New Thread";
      $this->description = "Your description here.";
      $this->tags = $this->comments = $this->entries = $this->entries = [];
    }
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'remove_tag':
      case 'edit':
      case 'delete':
        if ($this->user->id == $authingUser->id || $authingUser->isStaff()) {
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
    if (isset($this->tags[intval($tag_id)])) {
      return True;
    }
    try {
      $tag = new Tag($this->app, intval($tag_id));
    } catch (DatabaseException $e) {
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
      foreach ($this->tags as $tag) {
        if (!in_array($tag->id, $thread['thread_tags'])) {
          $tagsToDrop[] = intval($tag->id);
        }
      }
      $drop_tags = $this->drop_taggings($tagsToDrop);
      foreach ($thread['thread_tags'] as $tagToAdd) {
        // add any needed tags.
        if (!$this->tags || !array_filter_by_property($this->tags, 'id', $tagToAdd)) {
          // find this tagID.
          $tagID = intval($this->app->dbConn->table(Tag::$TABLE)->fields('id')->where(['id' => $tagToAdd])->limit(1)->firstValue());
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
  public function getEntries() {
    // the entries in a thread are the comments on it.
    return $this->getComments();
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
    return new AnimeGroup($this->app, array_map(function($a) {
      return $a['id'];
    }, $result));
  }
  public function render() {
    if ($this->app->action === 'new' || $this->app->action === 'edit') {
      if (isset($_POST['threads']) && is_array($_POST['threads'])) {
        $verbProgressive = $this->id === 0 ? "creating" : "updating";
        $verbPast = $this->id === 0 ? "created" : "updated";
        $updateThread = $this->create_or_update($_POST['thread']);
        if ($updateThread) {
          $this->app->display_success(200, "Successfully ".$verbPast." ".$this->title.".", "success");
        } else {
          $this->app->display_error(500, "An error occurred while ".$verbProgressive." ".$this->title.".");
        }
      }
      $this->app->display_error(400, "You must provide thread info to create or update.");
    }
    switch($this->app->action) {
      case 'feed':
        $maxTime = isset($_REQUEST['maxTime']) ? new DateTime('@'.intval($_REQUEST['maxTime'])) : Null;
        $minTime = isset($_REQUEST['minTime']) ? new DateTime('@'.intval($_REQUEST['minTime'])) : Null;
        $entries = [];
        foreach (array_sort_by_method($this->entries($minTime, $maxTime, 50)->load('comments')->entries(), 'time', [], 'desc') as $entry) {
          $entries[] = $entry->serialize();
        }
        $this->app->display_response(200, $entries);
        break;
      case 'show':
        if ($this->id == 0) {
          $this->app->display_error(404, "This thread could not be found.");
        }
        $this->app->display_response(200, $this->serialize());
        break;
      case 'delete':
        if ($this->id == 0) {
          $this->app->display_error(404, "This thread could not be found.");
        }
        if (!$this->app->checkCSRF()) {
          $this->app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
        }
        $threadTitle = $this->title;
        $deleteThread = $this->delete();
        if ($deleteThread) {
          $this->app->display_success(200, 'Successfully deleted '.$threadTitle.'.');
        } else {
          $this->app->display_error(500, 'An error occurred while deleting '.$threadTitle.'.');
        }
        break;
      default:
      case 'index':
        $perPage = 25;
        $pages = ceil(Thread::Count($this->app)/$perPage);
        $threadsQuery = $this->app->dbConn->table(Thread::$TABLE)->order('updated_at DESC')->offset((intval($this->app->page)-1)*$perPage)->limit($perPage)->query();
        $threads = [];
        while ($thread = $threadsQuery->fetch()) {
          $threadObj = new Thread($this->app, intval($thread['id']));
          $threadObj->set($thread);
          $threads[] = $tagTypeObj->serialize();
        }
        $this->app->display_response(200, [
          'page' => $this->app->page,
          'pages' => $pages,
          'threads' => $threads
        ]);
        break;
    }
    return;
  }
  public function formatFeedEntry(BaseEntry $entry) {
    return $entry->user->animeList->formatFeedEntry($entry);
  }
  // public function tagList(User $currentUser) {
  //   $output = "<ul class='tagList'>\n";
  //   $tagCounts = $this->tags->load('info')->tagCounts();
  //   foreach ($tagCounts as $tagID => $count) {
  //     $output .= "<li>".$this->tags[$tagID]->link("show", $this->tags[$tagID]->name)." ".intval($count)."</li>\n";
  //   }
  //   $output .= "</ul>\n";
  //   return $output;
  // }
  public function url($action="show", $format=Null, array $params=Null, $title=Null) {
    // returns the url that maps to this object and the given action.
    if ($title === Null) {
      $title = $this->id."-".$this->title;
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output(self::MODEL_URL())."/".($action !== "index" ? rawurlencode(rawurlencode($title))."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
}
?>