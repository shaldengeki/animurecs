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
    return new DateTime($this->returnInfo('createdAt'), new DateTimeZone(SERVER_TIMEZONE));
  }
  public function updatedAt() {
    return new DateTime($this->returnInfo('updatedAt'), new DateTimeZone(SERVER_TIMEZONE));
  }
  public function imagePath() {
    return $this->returnInfo('imagePath');
  }
  public function approvedOn() {
    return $this->returnInfo('approvedOn');
  }
  public function approvedUser() {
    if ($this->approvedUser === Null) {
      $this->approvedUser = new User($this->dbConn, intval($this->returnInfo('approvedUserId')));
    }
    return $this->approvedUser;
  }
  public function isApproved() {
    // Returns a bool reflecting whether or not the current anime is approved.
    if ($this->approvedOn() === '' or !$this->approvedOn()) {
      return False;
    }
    return True;
  }
  public function allow($authingUser, $action, $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'remove_tag':
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
        if ($this->isApproved() || $authingUser->isModerator() || $authingUser->isAdmin()) {
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

    // process uploaded image.
    $file_array = $_FILES['anime_image'];
    $imagePath = "";
    if (!empty($file_array['tmp_name']) && is_uploaded_file($file_array['tmp_name'])) {
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
      if (!is_dir(joinPaths(APP_ROOT, "img", "anime", intval($this->id)))) {
        mkdir(joinPaths(APP_ROOT, "img", "anime", intval($this->id)));
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

    //go ahead and create or update this anime.
    if ($this->id != 0) {
      //update this anime.
      $updateUser = $this->dbConn->stdQuery("UPDATE `anime` SET ".implode(", ", $params).", `image_path` = ".$this->dbConn->quoteSmart($imagePath).", `updated_at` = NOW() WHERE `id` = ".intval($this->id)." LIMIT 1");
      if (!$updateUser) {
        return False;
      }
    } else {
      // add this anime.
      $insertUser = $this->dbConn->stdQuery("INSERT INTO `anime` SET ".implode(",", $params).", `image_path` = ".$this->dbConn->quoteSmart($imagePath).", `created_at` = NOW(), `updated_at` = NOW()");
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
  public function getTags() {
    // retrieves a list of tag objects corresponding to tags belonging to this anime.
    $tags = [];
    $tagIDs = $this->dbConn->stdQuery("SELECT `tag_id` FROM `anime_tags` INNER JOIN `tags` ON `tags`.`id` = `tag_id` WHERE `anime_id` = ".intval($this->id)." ORDER BY `tags`.`tag_type_id` ASC, `tags`.`name` ASC");
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
  public function entries($maxTime=Null, $limit=Null) {
    if ($this->entries === Null) {
      $this->entries = $this->getEntries();
    }
    if ($maxTime !== Null || $limit !== Null) {
      // Returns a list of up to $limit entries up to $maxTime.
      $serverTimezone = new DateTimeZone(SERVER_TIMEZONE);
      $outputTimezone = new DateTimeZone(OUTPUT_TIMEZONE);
      if ($maxTime === Null) {
        $nowTime = new DateTime();
        $nowTime->setTimezone($outputTimezone);
        $maxTime = $nowTime;
      }
      $returnList = [];
      $entryCount = 0;
      foreach ($this->entries() as $entry) {
        $entryDate = new DateTime($value['time'], $serverTimezone);
        if ($entryDate > $maxTime) {
          continue;
        }
        $entry['anime_id'] = intval($this->id);
        $entry['anime'] = new Anime($this->dbConn, intval($this->id));
        $entry['user'] = new User($this->dbConn, intval($entry['user_id']));
        $returnList[] = $entry;
        $entryCount++;
        if ($limit !== Null && $entryCount >= $limit) {
          return $returnList;
        }
      }
      return $returnList;
    } else {
      return $this->entries;
    }
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
  public function scoreBar($score=False) {
    // returns markup for a score bar for a score given to this anime.
    if ($score === False || $score == 0) {
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
  public function feed($entries, $currentUser) {
    // returns an array of feed entries, keyed by the time of the entry.
    $output = [];
    foreach ($entries as $entry) {
      $output[$entry['time']] = $entry['user']->animeList()->feedEntry($entry, $entry['user'], $currentUser);
    }
    return $output;
  }
  public function animeFeed($currentUser, $maxTime=Null,$numEntries=50) {
    // returns markup for this user's anime feed.
    $feedEntries = $this->entries($maxTime, $numEntries);
    $output = "<ul class='userFeed'>\n";
    if (count($feedEntries) == 0) {
      $output .= "<blockquote><p>No entries yet - ".$currentUser->link("show", "be the first!")."</p></blockquote>\n";
    }
    $output .= implode("\n", $this->feed($feedEntries, $currentUser));
    $output .= "</ul>\n";
    return $output;
  }
  public function tagCloud($currentUser) {
    $output = "<ul class='tagCloud'>";
    foreach ($this->tags() as $tag) {
      $output .= "<li class='".escape_output($tag->type->name)."'><p>".$tag->link("show", $tag->name)."</p>".($tag->allow($currentUser, "edit") ? "<span>".$this->link("remove_tag", "×", False, Null, array('tag_id' => $tag->id))."</span>" : "")."</li>";
    }
    $output .= "</ul>";
    return $output;
  }
  public function profile($currentUser, $recsEngine=Null) {
    // displays an anime's profile.
    // info header.
    $output = "     <div class='row-fluid'>
        <div class='span3 userProfileColumn leftColumn'>
          <ul class='thumbnails avatarContainer'>
            <li class='span12'>
              <div class='thumbnail profileAvatar'>\n";
    if ($this->imagePath() != '') {
      $output .= "                <img src='".joinPaths(array(ROOT_URL,escape_output($this->imagePath())))."' class='img-rounded' alt=''>\n";
    } else {
      $output .= "                <img src='/img/anime/blank.png' class='img-rounded' alt=''>\n";
    }
    $output .= "          </div>
            </li>
          </ul>
        </div>
        <div class='span9 userProfileColumn rightColumn'>
          <div class='profileUserInfo'>
            <h1>
              ".escape_output($this->title())." 
              ".($this->allow($currentUser, "edit") ? "<small>(".$this->link("edit", "edit").")</small>" : "")."</h1>
            <p>
              ".escape_output($this->description())."
            </p>\n";
    if ($currentUser->loggedIn()) {
      $output .= "            <ul class='thumbnails'>
              <li class='span4'>\n";
      if (!isset($currentUser->animeList->uniqueList[$this->id]) || $currentUser->animeList->uniqueList[$this->id]['score'] == 0) {
        $output .= "                <p class='lead'>Predicted score:</p>
                ".$this->scoreBar($recsEngine->predict($currentUser, $this))."\n";
      } else {
        $output .= "                <p>You rated this:</p>
                ".$this->scoreBar($currentUser->animeList->uniqueList[$this->id]['score'])."\n";
      }
    } else {
      $output .= "            <ul class='thumbnails'>
              <li class='span4'>
                <p class='lead'>Predicted score:</p>
                <p>Sign in to view your predicted score!</p>\n";
    }
    $output .= "              </li>
              <li class='span8'>
                <p class='lead'>Tags:</p>
                ".$this->tagCloud($currentUser)."
              </li>
            </ul>\n";
    $output .= "          </div>
          <div id='userFeed'>\n";
    if ($currentUser->loggedIn()) {
      $animeList = new AnimeList($this->dbConn, 0);
      $anime = new Anime($this->dbConn, 0);
      if (isset($currentUser->animeList->uniqueList[$this->id])) {
        $thisEntry = $currentUser->animeList->uniqueList[$this->id];
        $addText = "Update this anime in your list: ";
      } else {
        $thisEntry = [];
        $addText = "Add this anime to your list: ";
      }
      $output .= "            <div class='addListEntryForm'>
            <form class='form-inline' action='".$animeList->url("new", array('user_id' => intval($currentUser->id)))."' method='POST'>
              <input name='anime_list[user_id]' id='anime_list_user_id' type='hidden' value='".intval($currentUser->id)."' />
              ".$addText."
              <input name='anime_list[anime_id]' id='anime_list_anime_id' type='hidden' value='".intval($this->id)."' />
              ".display_status_dropdown("anime_list[status]", "span3", $thisEntry['status'])."
              <div class='input-append'>
                <input class='input-mini' name='anime_list[score]' id='anime_list_score' type='number' min='0' max='10' step='1' value='".intval($thisEntry['score'])."' />
                <span class='add-on'>/10</span>
              </div>
              <div class='input-prepend'>
                <span class='add-on'>Ep</span>
                <input class='input-mini' name='anime_list[episode]' id='anime_list_episode' type='number' min='0' step='1' value='".intval($thisEntry['episode'])."' />
              </div>
              <input type='submit' class='btn btn-primary updateEntryButton' value='Update' />
            </form>
          </div>\n";
    }
    $output .= "                ".$this->animeFeed($currentUser)."
          </div>
        </div>
      </div>\n";
    return $output;
  }
  public function form($currentUser) {
    $animeTags = [];
    $blankTag = new Tag($this->dbConn, 0);
    foreach ($this->tags() as $tag) {
      $animeTags[] = array('id' => $tag->id, 'name' => $tag->name);
    }
    $output = "<form action='".(($this->id === 0) ? $this->url("new") : $this->url("edit"))."' enctype='multipart/form-data' method='POST' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='anime[id]' value='".intval($this->id)."' />")."
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
            <input name='anime[anime_tags]' type='text' class='token-input input-small' data-field='name' data-url='".$blankTag->url("token_search")."' data-value='".($this->id === 0 ? "[]" : escape_output(json_encode(array_values($animeTags))))."' id='anime[anime_tags]' />
          </div>
        </div>\n";
        if ($this->id != 0) {
          $output .= "        <div class='control-group'>
          <label class='control-label' for='anime_image'>Image</label>
          <div class='controls'>
            <input name='anime_image' class='input-file' type='file' onChange='displayImagePreview(this.files);' /><span class='help-inline'>Max size 300x300, JPEG/PNG/GIF.</span>
          </div>
        </div>\n";
        }
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