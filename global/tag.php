<?php
class Tag extends BaseObject {
  protected $name;
  protected $description;
  protected $type;
  protected $createdAt;
  protected $updatedAt;

  protected $createdUser;
  protected $anime;
  protected $manga;

  public function __construct($database, $id=Null) {
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
    return $this->returnInfo('createdAt');
  }
  public function updatedAt() {
    return $this->returnInfo('updatedAt');
  }
  public function allow($authingUser, $action) {
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
      default:
      case 'index':
        return True;
        break;
    }
  }
  public function create_or_update_tagging($anime_id, $currentUser) {
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
  public function drop_taggings($animus=False) {
    /*
      Deletes tagging relations.
      Takes an array of anime ids as input, defaulting to all anime.
      Returns a boolean.
    */
    $this->anime();
    if ($animus === False) {
      $animus = array_keys($this->anime());
    }
    $animeIDs = array();
    foreach ($animus as $anime) {
      if (is_numeric($anime)) {
        $animeIDs[] = intval($anime);
      }
    }
    if (count($animeIDs) > 0) {
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
  public function create_or_update($tag, $currentUser) {
    // creates or updates a tag based on the parameters passed in $tag and this object's attributes.
    // returns False if failure, or the ID of the tag if success.
    // make sure this tag name adheres to standards.
    $tag['name'] = str_replace("_", " ", strtolower($tag['name']));

    // filter some parameters out first and replace them with their corresponding db fields.
    if (isset($tag['anime_tags']) && !is_array($tag['anime_tags'])) {
      $tag['anime_tags'] = explode(",", $tag['anime_tags']);
    }

    $params = array();
    foreach ($tag as $parameter => $value) {
      if (!is_array($value)) {
        $params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
      }
    }
    //go ahead and create or update this tag.
    if ($this->id != 0) {
      //update this tag.
      $updateTag = $this->dbConn->stdQuery("UPDATE `tags` SET ".implode(", ", $params).", `updated_at` = NOW() WHERE `id` = ".intval($this->id)." LIMIT 1");
      if (!$updateTag) {
        return False;
      }
    } else {
      // add this tag.
      $insertTag = $this->dbConn->stdQuery("INSERT INTO `tags` SET ".implode(",", $params).", `created_at` = NOW(), `updated_at` = NOW(), `created_user_id` = ".intval($currentUser->id));
      if (!$insertTag) {
        return False;
      } else {
        $this->id = intval($this->dbConn->insert_id);
      }
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
  public function delete() {
    // delete this tag from the database.
    // returns a boolean.
    $deleteTag = $this->dbConn->stdQuery("DELETE FROM `tags` WHERE `id` = ".intval($this->id)." LIMIT 1");
    if (!$deleteTag) {
      return False;
    }
    return True;
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
  public function link($action="show", $text=Null, $raw=False) {
    // returns an HTML link to the current tag's profile, with text provided.
    if ($text === Null) {
      $text = $this->title() ? $this->title() : "Info";
    }
    return "<a href='/tags/".intval($this->id)."/".urlencode($action)."/'>".($raw ? $text : escape_output($text))."</a>";
  }
  public function profile() {
    // displays a tag's profile.
    return;
  }
  public function form($currentUser) {
    $tagAnime = [];
    foreach ($this->anime() as $anime) {
      $tagAnime[] = array('id' => $anime->id, 'title' => $anime->title);
    }
    $output = "<form action='/tags/".(($this->id === 0) ? "0/new/" : intval($this->id)."/edit/")."' method='POST' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='tag[id]' value='".intval($this->id)."' />")."
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='tag[name]'>Tag Name</label>
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
          <label class='control-label' for='tag[tag_type_id]'>Type</label>
          <div class='controls'>
            ".display_tag_type_dropdown($this->dbConn, "tag[tag_type_id]", ($this->id === 0 ? False : intval($this->type()->id)))."
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[anime_tags]'>Anime</label>
          <div class='controls'>
            <input name='tag[anime_tags]' type='text' class='token-input input-small' data-field='title' data-url='/anime/0/token_search/' data-value='".($this->id === 0 ? "[]" : escape_output(json_encode($tagAnime)))."' id='tag[anime_tags]' />
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
}
?>