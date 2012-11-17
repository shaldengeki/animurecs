<?php
class Anime extends BaseObject {
  protected $title;
  protected $description;
  protected $episodeCount;
  protected $episodeLength;
  protected $createdAt;
  protected $updatedAt;
  protected $approvedUser;
  protected $approvedOn;
  protected $imagePath;

  protected $entries;
  protected $ratings;

  protected $ratingAvg;
  protected $regularizedAvg;

  protected $tags;
  protected $comments;
  public function __construct($database, $id=Null) {
    parent::__construct($database, $id);
    $this->modelTable = "anime";
    $this->modelPlural = "anime";
    if ($id === 0) {
      $this->title = $this->description = $this->createdAt = $this->updatedAt = $this->imagePath = $this->approvedOn = "";
      $this->episodeCount = $this->episodeLength = $this->ratingAvg = $this->regularizedAvg = 0;
      $this->tags = $this->comments = $this->entries = $this->approvedUser = $this->entries = $this->ratings = [];
    } else {
      $this->title = $this->description = $this->createdAt = $this->updatedAt = $this->imagePath = $this->approvedOn = $this->episodeCount = $this->episodeLength = $this->tags = $this->comments = $this->entries = $this->approvedUser = $this->comments = $this->entries = $this->ratings = $this->ratingAvg = $this->regularizedAvg = Null;
    }
  }
  public function title() {
    return $this->returnInfo('title');
  }
  public function description() {
    return $this->returnInfo('description');
  }
  public function episodeCount() {
    return $this->returnInfo('episodeCount');
  }
  public function episodeLength() {
    return $this->returnInfo('episodeLength');
  }
  public function createdAt() {
    return $this->returnInfo('createdAt');
  }
  public function updatedAt() {
    return $this->returnInfo('updatedAt');
  }
  public function imagePath() {
    return $this->returnInfo('imagePath');
  }
  public function approvedOn() {
    return $this->returnInfo('approvedOn');
  }
  public function allow($authingUser, $action) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'approve':
      case 'edit':
      case 'delete':
        if ($authingUser->isModerator() || $authingUser->isAdmin()) {
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
      case 'show':
      default:
      case 'index':
        return True;
        break;
    }
  }
  public function create_or_update_tagging($tag_id, $currentUser) {
    /*
      Creates or updates an existing tagging for the current anime.
      Takes a tag ID.
      Returns a boolean.
    */
    // check to see if this is an update.
    if (isset($this->tags()[intval($tag_id)])) {
      return True;
    }
    try {
      $tag = new Tag($this->dbConn, intval($tag_id));
    } catch (Exception $e) {
      return False;
    }
    $insertDependency = $this->dbConn->stdQuery("INSERT INTO `anime_tags` (`tag_id`, `anime_id`, `created_user_id`, `created_at`) VALUES (".intval($tag->id).", ".intval($this->id).", ".intval($currentUser->id).", NOW())");
    if (!$insertDependency) {
      return False;
    }
    $this->tags[intval($tag->id)] = array('id' => intval($tag->id), 'name' => $tag->name);
    return True;
  }
  public function drop_taggings($tags=False) {
    /*
      Deletes tagging relations.
      Takes an array of tag ids as input, defaulting to all tags.
      Returns a boolean.
    */
    $this->tags();
    if ($tags === False) {
      $tags = array_keys($this->tags());
    }
    $tagIDs = array();
    foreach ($tags as $tag) {
      if (is_numeric($tag)) {
        $tagIDs[] = intval($tag);
      }
    }
    if (count($tagIDs) > 0) {
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
  public function create_or_update($anime, $currentUser=Null) {
    // creates or updates a anime based on the parameters passed in $anime and this object's attributes.
    // returns False if failure, or the ID of the anime if success.

    // filter some parameters out first and replace them with their corresponding db fields.
    if (isset($anime['anime_tags']) && !is_array($anime['anime_tags'])) {
      $anime['anime_tags'] = explode(",", $anime['anime_tags']);
    }
    if ((isset($anime['approved']) && intval($anime['approved']) == 1 && !$this->isApproved())) {
      $anime['approved_on'] = unixToMySQLDateTime();
      $anime['approved_user_id'] = $currentUser->id;
    } elseif ((!isset($anime['approved']) || intval($anime['approved']) == 0) && $this->isApproved()) {
      $anime['approved_on'] = Null;
      $anime['approved_user_id'] = 0;
    }
    unset($anime['approved']);

    if (isset($anime['episode_minutes'])) {
      $anime['episode_length'] = intval($anime['episode_minutes']) * 60;
    }
    unset($anime['episode_minutes']);

    $params = array();
    foreach ($anime as $parameter => $value) {
      if (!is_array($value)) {
        $params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
      }
    }
    //go ahead and create or update this anime.
    if ($this->id != 0) {
      //update this anime.
      $updateUser = $this->dbConn->stdQuery("UPDATE `anime` SET ".implode(", ", $params).", `updated_at` = NOW() WHERE `id` = ".intval($this->id)." LIMIT 1");
      if (!$updateUser) {
        return False;
      }
    } else {
      // add this anime.
      $insertUser = $this->dbConn->stdQuery("INSERT INTO `anime` SET ".implode(",", $params).", `created_at` = NOW(), `updated_at` = NOW()");
      if (!$insertUser) {
        return False;
      } else {
        $this->id = intval($this->dbConn->insert_id);
      }
    }

    // now process any taggings.
    if (isset($anime['anime_tags'])) {
      // drop any unneeded  tags.
      $tagsToDrop = array();
      foreach ($this->tags() as $tag) {
        if (!in_array($tag->id, $anime['anime_tags'])) {
          $tagsToDrop[] = intval($tag->id);
        }
      }
      $drop_tags = $this->drop_taggings($tagsToDrop);
      foreach ($anime['anime_tags'] as $tagToAdd) {
        // add any needed tags.
        if (!array_filter_by_property($this->tags(), 'id', $tagToAdd)) {
          // find this tagID.
          $tagID = intval($this->dbConn->queryFirstValue("SELECT `id` FROM `tags` WHERE `id` = ".intval($tagToAdd)." LIMIT 1"));
          if ($tagID) {
            $create_tagging = $this->create_or_update_tagging($tagID, $currentUser);
          }
        }
      }
    }

    return $this->id;
  }
  public function getApprovedUser() {
    // retrieves an id,name array corresponding to the user who approved this anime.
    return $this->dbConn->queryFirstRow("SELECT `users`.`id`, `users`.`name` FROM `anime` LEFT OUTER JOIN `users` ON `users`.`id` = `anime`.`approved_user_id` WHERE `anime`.`id` = ".intval($this->id));
  }
  public function approvedUser() {
    if ($this->approvedUser === Null) {
      $this->approvedUser = $this->getApprovedUser();
    }
    return $this->approvedUser;
  }
  public function isApproved() {
    // Returns a bool reflecting whether or not the current anime is approved.
    if ($this->approvedOn === '' or !$this->approvedOn) {
      return False;
    }
    return True;
  }
  public function getTags() {
    // retrieves a list of tag objects corresponding to tags belonging to this anime.
    $tags = [];
    $tagIDs = $this->dbConn->stdQuery("SELECT `tag_id` FROM `anime_tags` WHERE `anime_id` = ".intval($this->id));
    while ($tagID = $tagIDs->fetch_assoc()) {
      $tags[] = new Tag($this->dbConn, intval($tagID['tag_id']));
    }
    return $tags;
  }
  public function tags() {
    if ($this->tags === Null) {
      $this->tags = $this->getTags();
    }
    return $this->tags;
  }
  public function getComments() {
    // retrieves a list of id entries corresponding to comments belonging to this anime.
    return $this->dbConn->queryAssoc("SELECT `id` FROM `comments` WHERE `type` = 'anime' && `type_id` = ".intval($this->id)." ORDER BY `comments`.`created_at` DESC", "id");
  }
  public function getEntries() {
    // retrieves a list of id arrays corresponding to the list entries belonging to this anime.
    return $this->dbConn->queryAssoc("SELECT `id`, `user_id`, `time`, `status`, `score`, `episode` FROM `anime_lists` WHERE `anime_id` = ".intval($this->id)." ORDER BY `time` DESC", "id");
  }
  public function entries() {
    if ($this->entries === Null) {
      $this->entries = $this->getEntries();
    }
    return $this->entries;    
  }
  public function getRatings() {
    $ratings = array_filter($this->entries(), function ($value) {
      if (intval($value['score']) != 0) {
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
  public function ratingAvg() {
    if ($this->ratingAvg === Null) {
      $ratingSum = $ratingCount = 0;
      foreach ($this->ratings() as $rating) {
        $ratingSum += $rating['score'];
        $ratingCount++;
      }
      if ($ratingCount != 0) {
        $this->ratingAvg = $ratingSum / $ratingCount;
      } else {
        $this->ratingAvg = 0;
      }
    }
    return $this->ratingAvg; 
  }
  public function profile() {
    // displays an anime's profile.
    return;
  }
  public function form($currentUser) {
    $animeTags = [];
    foreach ($this->tags() as $tag) {
      $animeTags[] = array('id' => $tag->id, 'name' => $tag->name);
    }
    $output = "<form action='".(($this->id === 0) ? $this->url("new") : $this->url("edit"))."' method='POST' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='anime[id]' value='".intval($this->id)."' />")."
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='anime[title]'>Series Title</label>
          <div class='controls'>
            <input name='anime[title]' type='text' class='input-xlarge' id='anime[title]'".(($this->id === 0) ? "" : " value='".escape_output($this->title())."'")." />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[description]'>Description</label>
          <div class='controls'>
            <textarea class='field span4' name='anime[description]' rows='3' id='anime[description]'>".(($this->id === 0) ? "" : escape_output($this->description()))."</textarea>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[episode_count]'>Episodes</label>
          <div class='controls'>
            <input name='anime[episode_count]' type='number' min=0 step=1 class='input-small' id='anime[episode_count]'".(($this->id === 0) ? "" : " value=".intval($this->episodeCount()))." /> episodes at 
            <input name='anime[episode_minutes]' type='number' min=0 step=1 class='input-small' id='anime[episode_minutes]'".(($this->id === 0) ? "" : " value=".intval($this->episodeLength()/60))." /> minutes per episode
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[anime_tags]'>Tags</label>
          <div class='controls'>
            <input name='anime[anime_tags]' type='text' class='token-input input-small' data-field='name' data-url='/tags/0/token_search/' data-value='".($this->id === 0 ? "[]" : escape_output(json_encode(array_values($animeTags))))."' id='anime[anime_tags]' />
          </div>
        </div>\n";
        if ($currentUser->isModerator() || $currentUser->isAdmin()) {
          $output .= "        <div class='control-group'>
          <label class='control-label' for='anime[approved]'>Approved</label>
          <div class='controls'>
            <input name='anime[approved]' type='checkbox' value=1 ".($this->isApproved() ? "checked=checked" : "")."/>
          </div>
        </div>\n";
        }
        $output .= "    <div class='form-actions'>
          <button type='submit' class='btn btn-primary'>".(($this->id === 0) ? "Add Anime" : "Save changes")."</button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>".(($this->id === 0) ? "Go back" : "Discard changes")."</a>
        </div>
      </fieldset>\n</form>\n";
    return $output;
  }
}
?>