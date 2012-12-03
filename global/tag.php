<?php
class Tag extends BaseObject {
  protected $name;
  protected $description;
  protected $type;

  protected $createdUser;

  protected $numAnime;
  protected $anime;

  protected $numManga;
  protected $manga;

  public function __construct(DbConn $database, $id=Null) {
    parent::__construct($database, $id);
    $this->modelTable = "tags";
    $this->modelPlural = "tags";
    if ($id === 0) {
      $this->name = $this->description = $this->createdAt = $this->updatedAt = "";
      $this->type = $this->anime = $this->manga = $this->createdUser = [];
    } else {
      $this->name = $this->description = $this->createdAt = $this->updatedAt = $this->type = $this->anime = $this->manga = $this->createdUser = Null;
    }
  }
  public function name() {
    return $this->returnInfo('name');
  }
  public function description() {
    return $this->returnInfo('description');
  }
  public function createdAt() {
    return new DateTime($this->returnInfo('createdAt'), new DateTimeZone(SERVER_TIMEZONE));
  }
  public function updatedAt() {
    return new DateTime($this->returnInfo('updatedAt'), new DateTimeZone(SERVER_TIMEZONE));
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      // case 'approve':
      case 'new':
      case 'edit':
      case 'delete':
        if ($authingUser->isModerator() || $authingUser->isAdmin()) {
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
    $this->anime();
    if (isset($this->anime()[intval($anime_id)])) {
      return True;
    }
    try {
      $anime = new Anime($this->dbConn, intval($anime_id));
    } catch (Exception $e) {
      return False;
    }
    $insertTagging = $this->dbConn->stdQuery("INSERT INTO `anime_tags` (`anime_id`, `tag_id`, `created_user_id`, `created_at`) VALUES (".intval($anime->id).", ".intval($this->id).", ".intval($currentUser->id).", NOW())");
    if (!$insertTagging) {
      return False;
    }
    $this->anime[intval($anime->id)] = array('id' => intval($anime->id), 'title' => $anime->title);
    return True;
  }
  public function drop_taggings(array $animus=Null) {
    /*
      Deletes tagging relations.
      Takes an array of anime ids as input, defaulting to all anime.
      Returns a boolean.
    */
    $this->anime();
    if ($animus === Null) {
      $animus = array_keys($this->anime());
    }
    $animeIDs = [];
    foreach ($animus as $anime) {
      if (is_numeric($anime)) {
        $animeIDs[] = intval($anime);
      }
    }
    if ($animeIDs) {
      $drop_taggings = $this->dbConn->stdQuery("DELETE FROM `anime_tags` WHERE `tag_id` = ".intval($this->id)." AND `anime_id` IN (".implode(",", $animeIDs).") LIMIT ".count($animeIDs));
      if (!$drop_taggings) {
        return False;
      }
    }
    foreach ($animeIDs as $animeID) {
      unset($this->anime[intval($animeID)]);
    }
    return True;
  }
  public function validate(array $tag) {
    if (!parent::validate($tag)) {
      return False;
    }
    if (!isset($tag['name']) || strlen($tag['name']) < 1 || strlen($tag['name']) > 50) {
      return False;
    }
    if (isset($tag['description']) && (strlen($tag['description']) < 1 || strlen($tag['description']) > 600)) {
      return False;
    }
    if (!isset($tag['created_user_id'])) {
      return False;
    }
    if (!is_numeric($tag['created_user_id']) || intval($tag['created_user_id']) != $tag['created_user_id'] || intval($tag['created_user_id']) <= 0) {
      return False;
    } else {
      try {
        $createdUser = new User($this->dbConn, intval($tag['created_user_id']));
      } catch (Exception $e) {
        return False;
      }
    }
    if (!isset($tag['tag_type_id'])) {
      return False;
    }
    if (!is_numeric($tag['tag_type_id']) || intval($tag['tag_type_id']) != $tag['tag_type_id'] || intval($tag['tag_type_id']) <= 0) {
      return False;
    } else {
      try {
        $parent = new TagType($this->dbConn, intval($tag['tag_type_id']));
      } catch (Exception $e) {
        return False;
      }
    }
    return True;
  }
  public function create_or_update(array $tag, array $whereConditions=Null) {
    // creates or updates a tag based on the parameters passed in $tag and this object's attributes.
    // returns False if failure, or the ID of the tag if success.
    // make sure this tag name adheres to standards.
    $tag['name'] = str_replace("_", " ", strtolower($tag['name']));

    // filter some parameters out first and replace them with their corresponding db fields.
    if (isset($tag['anime_tags']) && !is_array($tag['anime_tags'])) {
      $tag['anime_tags'] = explode(",", $tag['anime_tags']);
    }

    //go ahead and create or update this tag.
    if (!parent::create_or_update($tag)) {
      return False;
    }

    // now process any taggings.
    if (isset($tag['anime_tags'])) {
      // drop any unneeded access rules.
      $animeToDrop = array();
      foreach ($this->anime() as $anime) {
        if (!in_array($anime->id, $tag['anime_tags'])) {
          $animeToDrop[] = intval($anime->id);
        }
      }
      $drop_anime = $this->drop_taggings($animeToDrop);
      foreach ($tag['anime_tags'] as $animeToAdd) {
        if (!array_filter_by_property($this->anime(), 'id', $animeToAdd)) {
          // find this tagID.
          $animeID = intval($this->dbConn->queryFirstValue("SELECT `id` FROM `anime` WHERE `id` = ".intval($animeToAdd)." LIMIT 1"));
          if ($animeID) {
            $create_tagging = $this->create_or_update_tagging($animeID, $currentUser);
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
    $dropTaggings = $this->drop_taggings();
    if (!$dropTaggings) {
      return False;
    }
    return parent::delete();
  }
  public function isApproved() {
    // Returns a bool reflecting whether or not the current anime is approved.
    // doesn't do anything for now. maybe use later.
    /* 
    if ($this->approvedOn() === '' or !$this->approvedOn()) {
      return False;
    }
    return True;
    */
  }
  public function getApprovedUser() {
    // retrieves an id,name array corresponding to the user who approved this anime.
    // return $this->dbConn->queryFirstRow("SELECT `users`.`id`, `users`.`name` FROM `anime` LEFT OUTER JOIN `users` ON `users`.`id` = `anime`.`approved_user_id` WHERE `anime`.`id` = ".intval($this->id));
  }
  public function getCreatedUser() {
    // retrieves a user object corresponding to the user who created this tag.
    return new User($this->dbConn, intval($this->dbConn->queryFirstValue("SELECT `created_user_id` FROM `tags` WHERE `id` = ".intval($this->id))));
  }
  public function createdUser() {
    if ($this->createdUser === Null) {
      $this->createdUser = $this->getCreatedUser();
    }
    return $this->createdUser;
  }
  public function getType() {
    // retrieves the tag type that this tag belongs to.
    return new TagType($this->dbConn, intval($this->dbConn->queryFirstValue("SELECT `tag_type_id` FROM `tags` WHERE `id` = ".intval($this->id))));
  }
  public function type() {
    if ($this->type === Null) {
      $this->type = $this->getType();
    }
    return $this->type;
  }
  public function getAnime() {
    // retrieves a list of anime objects corresponding to anime tagged with this tag.
    $animes = [];
    $animeIDs = $this->dbConn->stdQuery("SELECT `anime_id` FROM `anime_tags` WHERE `tag_id` = ".intval($this->id));
    while ($animeID = $animeIDs->fetch_assoc()) {
      $animes[intval($animeID['anime_id'])] = new Anime($this->dbConn, intval($animeID['anime_id']));
    }
    return $animes;
  }
  public function anime() {
    if ($this->anime === Null) {
      $this->anime = $this->getAnime();
    }
    return $this->anime;
  }
  public function getNumAnime() {
    // retrieves the number of anime tagged with this tag.
    return $this->dbConn->queryCount("SELECT COUNT(*) FROM `anime_tags` WHERE `tag_id` = ".intval($this->id));
  }
  public function numAnime() {
    if ($this->numAnime === Null) {
      $this->numAnime = $this->getNumAnime();
    }
    return $this->numAnime;
  }
  public function profile(RecsEngine $recsEngine, User $currentUser) {
    // displays a tag's profile.
    $output = "<h1>".escape_output($this->name()).($this->allow($currentUser, "edit") ? " <small>(".$this->link("edit", "edit").")</small>" : "")."</h1>\n<ul class='recommendations'>\n";
    $predictedRatings = $recsEngine->predict($currentUser, $this->anime());
    if (is_array($predictedRatings)) {
      arsort($predictedRatings);
      foreach ($predictedRatings as $animeID=>$prediction) {
        $output .= "<li>".$this->anime()[$animeID]->link("show", "<h4>".escape_output($this->anime()[$animeID]->title)."</h4><img src='".joinPaths(ROOT_URL, escape_output($this->anime()[$animeID]->imagePath))."' />", True, array('title' => $this->anime()[$animeID]->description(True)))."<p><em>Predicted score: ".round($prediction, 1)."</em></p></li>\n";
      }
    } else {
      $i = 0;
      foreach ($this->anime() as $anime) {
        $output .= "<li>".$anime->link("show", "<h4>".escape_output($anime->title)."</h4><img src='".joinPaths(ROOT_URL, escape_output($anime->imagePath))."' />", True, array('title' => $anime->description(True)))."</li>\n";
        $i++;
        if ($i >= 20) {
          break;
        }
      }
    }
    $output .= "</ul>";
    $output .= tag_list($this->anime());
    return $output;
  }
  public function form(User $currentUser) {
    $tagAnime = [];
    foreach ($this->anime() as $anime) {
      $tagAnime[] = array('id' => $anime->id, 'title' => $anime->title);
    }
    $anime = new Anime($this->dbConn, 0);
    $output = "<form action='".(($this->id === 0) ? $this->url("new") : $this->url("edit"))."' method='POST' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='tag[id]' value='".intval($this->id)."' />")."
      <input name='tag[created_user_id]' type='hidden' value=".($this->id === 0 ? intval($currentUser->id) : $this->createdUser()->id)." />
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='tag[name]'>Name</label>
          <div class='controls'>
            <input name='tag[name]' type='text' class='input-xlarge' id='tag[name]'".(($this->id === 0) ? "" : " value='".escape_output($this->name())."'")." />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[description]'>Description</label>
          <div class='controls'>
            <textarea class='field span4' name='tag[description]' rows='3' id='tag[description]'>".(($this->id === 0) ? "" : escape_output($this->description()))."</textarea>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[tag_type_id]'>Tag Type</label>
          <div class='controls'>
            ".display_tag_type_dropdown($this->dbConn, "tag[tag_type_id]", ($this->id === 0 ? False : intval($this->type()->id)))."
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[anime_tags]'>Anime</label>
          <div class='controls'>
            <input name='tag[anime_tags]' type='text' class='token-input input-small' data-field='title' data-url='".$anime->url('token_search')."' data-value='".($this->id === 0 ? "[]" : escape_output(json_encode($tagAnime)))."' id='tag[anime_tags]' />
          </div>
        </div>\n";
        /*
        if ($currentUser->isModerator || $currentUser->isAdmin()) {
          $output .= "        <div class='control-group'>
          <label class='control-label' for='tag[approved]'>Approved</label>
          <div class='controls'>
            <input name='tag[approved]' type='checkbox' value=1 ".($this->isApproved() ? "checked=checked" : "")."/>
          </div>
        </div>\n";
        }
        */
        $output .= "    <div class='form-actions'>
          <button type='submit' class='btn btn-primary'>".(($this->id === 0) ? "Create Tag" : "Save changes")."</button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>".(($this->id === 0) ? "Go back" : "Discard changes")."</a>
        </div>
      </fieldset>\n</form>\n";
    return $output;
  }
  public function link($action="show", $text="Show", $raw=False, array $params=Null, array $urlParams=Null, $id=Null) {
    // returns an HTML link to the current object's profile, with text provided.
    $linkParams = [];
    if (is_array($params)) {
      foreach ($params as $key => $value) {
        $linkParams[] = escape_output($key)."='".escape_output($value)."'";
      }
    }
    $linkClass = $this->id != 0 ? " class='tag-".escape_output($this->type()->name)."'" : "";
    $linkTitle = $this->id != 0 ? " title='".escape_output($this->name())."'" : "";
    }
    return "<a".$linkClass.$linkTitle." href='".$this->url($action, $urlParams, $id)."' ".implode(" ", $linkParams).">".($raw ? $text : escape_output($text))."</a>";
  }
}
?>