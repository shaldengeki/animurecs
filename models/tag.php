<?php
class Tag extends BaseObject {
  public static $TABLE = "tags";
  public static $PLURAL = "tags";
  public static $FIELDS = [
    'id' => [
      'type' => 'int',
      'db' => 'id'
    ],
    'name' => [
      'type' => 'str',
      'db' => 'name'
    ],
    'description' => [
      'type' => 'str',
      'db' => 'description'
    ],
    'tagTypeId' => [
      'type' => 'int',
      'db' => 'tag_type_id'
    ],
    'createdAt' => [
      'type' => 'date',
      'db' => 'created_at'
    ],
    'updatedAt' => [
      'type' => 'date',
      'db' => 'updated_at'
    ],
    'createdUserId' => [
      'type' => 'int',
      'db' => 'created_user_id'
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
    'type' => [
      'obj' => 'TagType',
      'table'  => 'tag_types',
      'own_col'  => 'tag_type_id',
      'join_col' => 'id',
      'type' => 'one'
    ],
    'createdUser' => [
      'obj' => 'User',
      'table' => 'users',
      'own_col' => 'created_user_id',
      'join_col' => 'id',
      'type' => 'one'
    ],
    'approvedUser' => [
      'obj' => 'User',
      'table' => 'users',
      'own_col' => 'approved_user_id',
      'join_col' => 'id',
      'type' => 'one'
    ],
    'anime' => [
      'obj' => 'Anime',
      'table' => 'anime',
      'own_col' => 'id',
      'join_col' => 'id',
      'type' => 'habtm',
      'join_table' => 'anime_tags',
      'join_table_own_col' => 'tag_id',
      'join_table_join_col' => 'anime_id'      
    ],/*
    'manga' => [
      'obj' => 'Manga',
      'table' => Manga::$TABLE,
      'own_col' => 'id',
      'join_col' => Manga::DB_FIELD('id'),
      'type' => 'habtm',
      'join_table' => 'manga_tags',
      'join_table_own_col' => 'tag_id',
      'join_table_join_col' => 'manga_id'      
    ]*/
  ];
  public $numAnime;

  // public $numManga;
  // public $manga;

  public function __construct(Application $app, $id=Null) {
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->name = "New Tag";
      $this->description = "";
      $this->type = $this->anime = $this->manga = $this->createdUser = [];
    }
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      // case 'approve':
      case 'new':
      case 'edit':
      case 'delete':
        if ($authingUser->isStaff()) {
          return True;
        }
        return False;
        break;
      case 'token_search':
        if ($authingUser->loggedIn()) {
          return True;
        }
        return False;
        break;
      case 'show':
      case 'index':
        return True;
        break;
      default:
        return False;
        break;
    }
  }
  public function create_or_update_tagging($anime_id, User $currentUser) {
    /*
      Creates or updates an existing tagging for the current anime.
      Takes a tag ID.
      Returns a boolean.
    */
    // check to see if this is an update.
    if (isset($this->anime[intval($anime_id)])) {
      return True;
    }
    try {
      $anime = new Anime($this->app, intval($anime_id));
    } catch (Exception $e) {
      return False;
    }
    $dateTime = new DateTime('now', $this->app->serverTimeZone);
    $this->app->dbConn->table('anime_tags')->fields('anime_id', 'tag_id', 'created_user_id', 'created_at')
      ->values([$anime->id, $this->id, $currentUser->id, $dateTime->format("Y-m-d H:i:s")])
      ->insert();
    $this->fire('tag');
    $anime->fire('tag');
    $this->anime[intval($anime->id)] = ['id' => intval($anime->id), 'title' => $anime->title];
    return True;
  }
  public function drop_taggings(array $animus=Null) {
    /*
      Deletes tagging relations.
      Takes an array of anime ids as input, defaulting to all anime.
      Returns a boolean.
    */
    if ($animus === Null) {
      $animus = array_keys($this->anime);
    }
    $animeIDs = [];
    foreach ($animus as $anime) {
      if (is_numeric($anime)) {
        $animeIDs[] = intval($anime);
      }
    }
    if ($animeIDs) {
      $animeObjects = [];
      foreach($animeIDs as $animeID) {
        $animeObjects[$animeID] = new Anime($this->app, $animeID);
        $animeObjects[$animeID]->beforeUpdate([]);
      }
      $this->beforeUpdate([]);
      $this->app->dbConn->table('anime_tags')
        ->where(['tag_id' => $this->id, 'anime_id' => $animeIDs])
        ->limit(count($animeIDs))
        ->delete();
      $this->afterUpdate([]);
      foreach ($animeObjects as $anime) {
        $anime->afterUpdate([]);
      }
    }
    foreach ($animeIDs as $animeID) {
      $this->anime[intval($animeID)]->fire('untag');
      unset($this->anime[intval($animeID)]);
    }
    $this->fire('untag');
    return True;
  }
  public function validate(array $tag) {
    $validationErrors = [];
    try {
      parent::validate($tag);
    } catch (ValidationException $e) {
      $validationErrors = array_merge($validationErrors, $e->messages);
    }
    if (!isset($tag['name']) || mb_strlen($tag['name']) < 1 || mb_strlen($tag['name']) > 50) {
      $validationErrors[] = "Tag must have name between 1 and 50 characters";
    }
    if (isset($tag['description']) && (mb_strlen($tag['description']) < 0 || mb_strlen($tag['description']) > 600)) {
      $validationErrors[] = "Tag must have description between 1 and 600 characters";
    }
    if (!isset($tag['created_user_id']) || !is_integral($tag['created_user_id']) || intval($tag['created_user_id']) <= 0) {
      $validationErrors[] = "Created user ID must be valid";
    } else {
      try {
        $createdUser = new User($this->app, intval($tag['created_user_id']));
        $createdUser->load();
      } catch (DbException $e) {
        $validationErrors[] = "Created user must exist";
      }
    }
    if (!isset($tag['tag_type_id']) || !is_integral($tag['tag_type_id']) || intval($tag['tag_type_id']) <= 0) {
      $validationErrors[] = "Tag type ID must be valid";
    } else {
      try {
        $parent = new TagType($this->app, intval($tag['tag_type_id']));
        $parent->load();
      } catch (DbException $e) {
        $validationErrors[] = "Tag type must exist";
      }
    }
    if ($validationErrors) {
      throw new ValidationException($this->app, $tag, $validationErrors);
    } else {
      return True;
    }
  }
  public function create_or_update(array $tag, array $whereConditions=Null) {
    // creates or updates a tag based on the parameters passed in $tag and this object's attributes.
    // returns False if failure, or the ID of the tag if success.
    // make sure this tag name adheres to standards.
    $tag['name'] = str_replace("_", " ", strtolower($tag['name']));

    // filter some parameters out first and replace them with their corresponding db fields.
    $tagAnime = [];
    if (isset($tag['anime_tags']) && !is_array($tag['anime_tags'])) {
      $tagAnime = explode(",", $tag['anime_tags']);
      unset($tag['anime_tags']);
    }

    //go ahead and create or update this tag.
    parent::create_or_update($tag);

    // now process any taggings.
    if (isset($tagAnime)) {
      // drop any unneeded access rules.
      $animeToDrop = [];
      foreach ($this->anime as $anime) {
        if (!in_array($anime->id, $tagAnime)) {
          $animeToDrop[] = intval($anime->id);
        }
      }
      $drop_anime = $this->drop_taggings($animeToDrop);
      foreach ($tagAnime as $animeToAdd) {
        if (!array_filter_by_property($this->anime, 'id', $animeToAdd)) {
          // find this animeID.
          try {
            $thisAnime = Anime::findById($this->app, $animeToAdd);
            $this->create_or_update_tagging($thisAnime->id, $currentUser);
          } catch (DbException $e) {
            // don't add a tagging for a non-existent anime ID.
          }
        }
      }
    }
    return $this->id;
  }
  public function delete($entries=Null) {
    // delete this tag from the database.
    // returns a boolean.

    // drop all taggings for this tag first.
    $this->drop_taggings();
    return parent::delete();
  }
  public function isApproved() {
    // Returns a bool reflecting whether or not the current anime is approved.
    // doesn't do anything for now. maybe use later.
    /* 
    if ($this->approvedOn === '' or !$this->approvedOn) {
      return False;
    }
    return True;
    */
  }
  public function getThreads() {
    // retrieves a list of thread objects corresponding to threads tagged with this tag.
    $threads = [];
    $threadIDs = $this->app->dbConn->table('thread_tags')->fields('thread_id')->where(['tag_id' => $this->id])->query();
    while ($threadID = $threadIDs->fetch()) {
      $threads[intval($threadID['thread_id'])] = new Thread($this->app, intval($threadID['thread_id']));
    }
    return new ThreadGroup($this->app, $threads);
  }
  public function threads() {
    if ($this->threads === Null) {
      $this->threads = $this->getThreads();
    }
    return $this->threads;
  }
  public function getNumAnime() {
    // retrieves the number of anime tagged with this tag.
    if (isset($this->anime)) {
      return count($this->anime);
    }
    return $this->app->dbConn->table(static::$JOINS['anime']['join_table'])
      ->fields('COUNT(*)')
      ->where([
        static::$JOINS['anime']['join_table_own_col'] => $this->id
      ])
      ->count();
  }
  public function numAnime() {
    if ($this->numAnime === Null) {
      $this->numAnime = $this->getNumAnime();
    }
    return $this->numAnime;
  }
  public function render() {
    if ($this->app->action === 'new' || $this->app->action === 'edit') {
      if (isset($_POST['tag']) && is_array($_POST['tag'])) {
        $updateTag = $this->create_or_update($_POST['tag']);
        if ($updateTag) {
          $this->app->delayedMessage("Successfully updated.", "success");
          $this->app->redirect($this->url("show"));
        } else {
          $this->app->delayedMessage("An error occurred while creating or updating this tag.", "error");
          $this->app->redirect($this->id === 0 ? $this->url("new") : $this->url("edit"));
        }
      }
    }
      switch($this->app->action) {
      case 'token_search':
        $tags = [];
        if (isset($_REQUEST['term'])) {
          $tags = $this->app->dbConn->table(static::$TABLE)->fields('id', 'name')->match('name', $_REQUEST['term'])->order('name ASC')->assoc();
        }
        echo json_encode($tags);
        exit;
        break;
      case 'new':
        $title = "Create a Tag";
        $output = $this->view('new');
        break;
      case 'edit':
        if ($this->id == 0) {
          $this->app->display_error(404);
        }
        $title = "Editing ".escape_output($this->name);
        $output = $this->view('edit');
        break;
      case 'show':
        if ($this->id == 0) {
          $this->app->display_error(404);
        }
        $title = "Tag: ".escape_output($this->name);
        $output = $this->view('show', ['recsEngine' => $this->app->recsEngine]);
        break;
      case 'delete':
        if ($this->id == 0) {
          $this->app->display_error(404);
        }
        if (!$this->app->checkCSRF()) {
          $this->app->display(403);
        }
        $tagName = $this->name;
        $deleteTag = $this->delete();
        if ($deleteTag) {
          $this->app->delayedMessage('Successfully deleted '.$tagName.'.', "success");
          $this->app->redirect();
        } else {
          $this->app->delayedMessage('An error occurred while deleting '.$tagName.'.', "error");
          $this->app->redirect();
        }
        break;
      default:
      case 'index':
        $title = "All Tags";
        $output = $this->view('index');
        break;
    }
    return $this->app->render($output, ['subtitle' => $title]);
  }
  public function url($action="show", $format=Null, array $params=Null, $name=Null) {
    // returns the url that maps to this object and the given action.
    if ($name === Null) {
      $name = $this->name;
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output(self::MODEL_URL())."/".($action !== "index" ? rawurlencode(rawurlencode($name))."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
  public function link($action="show", $text="Show", $format=Null, $raw=False, array $params=Null, array $urlParams=Null, $id=Null) {
    // returns an HTML link to the current object's profile, with text provided.
    $linkParams = [];
    if (!is_array($params)) {
      $params = [];
    }
    if (!isset($params['title'])) {
      $params['title'] = $this->name;
    }
    if (!isset($params['class'])) {
      $params['class'] = 'tag-'.$this->type->name;
    } else {
      $params['class'] .= ' tag-'.$this->type->name;
    }
    foreach ($params as $key => $value) {
      $linkParams[] = escape_output($key)."='".escape_output($value)."'";
    }
    return "<a href='".$this->url($action, $format, $urlParams, $id)."' ".implode(" ", $linkParams).">".($raw ? $text : escape_output($text))."</a>";
  }
}
?>