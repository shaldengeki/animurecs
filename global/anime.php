<?php

class Anime {
  public $dbConn;
  public $id;
  public $title;
  public $description;
  public $episodeCount;
  public $episodeLength;
  public $createdAt;
  public $updatedAt;
  public $approvedUser;
  public $approvedOn;
  public $imagePath;
  public $tags;
  public $comments;
  public $entries;
  public function __construct($database, $id=Null) {
    $this->dbConn = $database;
    if ($id === 0) {
      $this->id = 0;
      $this->title = $this->description = $this->createdAt = $this->updatedAt = $this->imagePath = $this->approvedOn = "";
      $this->episodeCount = $this->episodeLength = 0;
      $this->tags = $this->comments = $this->entries = $this->approvedUser = [];
    } else {
      $animeInfo = $this->dbConn->queryFirstRow("SELECT `id`, `title`, `description`, `episode_count`, `episode_length`, `created_at`, `updated_at`, `image_path`, `approved_on` FROM `anime` WHERE `id` = ".intval($id)." LIMIT 1");
      $this->id = intval($animeInfo['id']);
      $this->title = $animeInfo['title'];
      $this->description = $animeInfo['description'];
      $this->episodeCount = intval($animeInfo['episode_count']);
      $this->episodeLength = intval($animeInfo['episode_length']);
      $this->createdAt = $animeInfo['created_at'];
      $this->updatedAt = $animeInfo['updated_at'];
      $this->imagePath = $animeInfo['image_path'];
      $this->approvedOn = $animeInfo['approved_on'];
      $this->approvedUser = $this->getApprovedUser();
      $this->tags = $this->getTags();
      //$this->comments = $this->getComments();
      //$this->entries = $this->getEntries();
    }
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
      case 'json_search':
      case 'new':
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
  public function create_or_update_tagging($tag_id, $currentUser) {
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
    if ($tags === False) {
      $tags = array_keys($this->tags);
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
  public function create_or_update($anime, $currentUser) {
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
      // drop any unneeded access rules.
      $tagsToDrop = array();
      foreach ($this->tags as $tag) {
        if (!in_array($tag['id'], $anime['anime_tags'])) {
          $tagsToDrop[] = intval($tag['id']);
        }
      }
      $drop_tags = $this->drop_taggings($tagsToDrop);
      foreach ($anime['anime_tags'] as $tagToAdd) {
        // find this tagID.
        $tagID = intval($this->dbConn->queryFirstValue("SELECT `id` FROM `tags` WHERE `id` = ".intval($tagToAdd)." LIMIT 1"));
        if ($tagID) {
          $create_tagging = $this->create_or_update_tagging($tagID, $currentUser);
        }
      }
    }

    return $this->id;
  }
  public function delete() {
    // delete this anime from the database.
    // returns a boolean.
    $deleteAnime = $this->dbConn->stdQuery("DELETE FROM `anime` WHERE `id` = ".intval($this->id)." LIMIT 1");
    if (!$deleteAnime) {
      return False;
    }
    return True;
  }
  public function isApproved() {
    // Returns a bool reflecting whether or not the current anime is approved.
    if ($this->approvedOn === '' or !$this->approvedOn) {
      return False;
    }
    return True;
  }
  public function getApprovedUser() {
    // retrieves an id,name array corresponding to the user who approved this anime.
    return $this->dbConn->queryFirstRow("SELECT `users`.`id`, `users`.`name` FROM `anime` LEFT OUTER JOIN `users` ON `users`.`id` = `anime`.`approved_user_id` WHERE `anime`.`id` = ".intval($this->id));
  }
  public function getTags() {
    // retrieves a list of id,name entries corresponding to tags belonging to this anime.
    $tags = [];
    $tagQuery = $this->dbConn->stdQuery("SELECT `id`, `name` FROM `anime_tags` LEFT OUTER JOIN `tags` ON `tags`.`id` = `anime_tags`.`tag_id` WHERE `anime_id` = ".intval($this->id)." ORDER BY `tags`.`tag_type_id` ASC, `tags`.`name` ASC");
    while ($tag = $tagQuery->fetch_assoc()) {
      $tags[intval($tag['id'])] = $tag;
    }
    return $tags;
  }
  public function getComments() {
    // retrieves a list of id entries corresponding to comments belonging to this anime.
    $comments = [];
    $commentQuery = $this->dbConn->stdQuery("SELECT `id` FROM `comments` WHERE `type` = 'anime' && `type_id` = ".intval($this->id)." ORDER BY `comments`.`created_at` DESC");
    while ($comment = $commentQuery->fetch_assoc()) {
      $comments[intval($comment['id'])] = $comment;
    }
    return $comments;
  }
  public function getEntries() {
    // retrieves a list of id arrays corresponding to the list entries belonging to this anime.
    $entries = [];
    $entryQuery = $this->dbConn->stdQuery("SELECT `id` FROM `anime_lists` WHERE `anime_id` = ".intval($this->id)." ORDER BY `time` DESC");
    while ($entry = $entryQuery->fetch_assoc()) {
      $entries[intval($entry['id'])] = $entry;
    }
    return $entries;
  }
  public function link($action="show", $text=Null, $raw=False) {
    // returns an HTML link to the current anime's profile, with text provided.
    if ($text === Null) {
      $text = $this->title ? $this->title : "Info";
    }
    return "<a href='/anime.php?action=".urlencode($action)."&id=".intval($this->id)."'>".($raw ? $text : escape_output($text))."</a>";
  }
  public function profile() {
    // displays an anime's profile.
    return;
    $userObject = new User($database, $user_id);
    $facility = $database->queryFirstValue("SELECT `name` FROM `facilities` WHERE `id` = ".intval($userObject->facility_id)." LIMIT 1");
    $form_entries = $database->stdQuery("SELECT `form_entries`.*, `forms`.`name` AS `form_name`, `machines`.`name` AS `machine_name` FROM `form_entries` 
                                          LEFT OUTER JOIN `forms` ON `forms`.`id` = `form_entries`.`form_id`
                                          LEFT OUTER JOIN `machines` ON `machines`.`id` = `form_entries`.`machine_id`
                                          WHERE `user_id` = ".intval($user_id)." 
                                          ORDER BY `updated_at` DESC");
    echo "<dl class='dl-horizontal'>
      <dt>Email</dt>
      <dd>".escape_output($userObject->email)."</dd>
      <dt>Facility</dt>
      <dd><a href='facility.php?action=show&id=".intval($userObject->facility_id)."'>".escape_output($facility)."</a></dd>
      <dt>User Role</dt>
      <dd>".escape_output(convert_usermask_to_text($userObject->usermask))."</dd>
    </dl>\n";
    if (convert_usermask_to_text($userObject->usermask) == 'Physicist') {
      $form_approvals = $database->stdQuery("SELECT `form_entries`.`id`, `qa_month`, `qa_year`, `machine_id`, `machines`.`name` AS `machine_name`, `user_id`, `users`.`name` AS `user_name`, `approved_on` FROM `form_entries` LEFT OUTER JOIN `machines` ON `machines`.`id` = `form_entries`.`machine_id` LEFT OUTER JOIN `users` ON `users`.`id` = `form_entries`.`user_id` WHERE `approved_user_id` = ".intval($userObject->id)." ORDER BY `approved_on` DESC");
      echo "  <h3>Approvals</h3>
    <table class='table table-striped table-bordered dataTable'>
      <thead>
        <tr>
          <th>QA Date</th>
          <th>Machine</th>
          <th>Submitter</th>
          <th>Approval Date</th>
        </tr>
      </thead>
      <tbody>\n";
      while ($approval = mysqli_fetch_assoc($form_approvals)) {
        echo "      <tr>
          <td><a href='form_entry.php?action=edit&id=".intval($approval['id'])."'>".escape_output($approval['qa_year']."/".$approval['qa_month'])."</a></td>
          <td><a href='form.php?action=show&id=".intval($approval['machine_id'])."'>".escape_output($approval['machine_name'])."</a></td>
          <td><a href='user.php?action=show&id=".intval($approval['user_id'])."'>".escape_output($approval['user_name'])."</a></td>
          <td>".escape_output(format_mysql_timestamp($approval['approved_on']))."</td>
        </tr>\n";
      }
      echo "    </tbody>
    </table>\n";
    }
    echo "  <h3>Form Entries</h3>
    <table class='table table-striped table-bordered dataTable'>
      <thead>
        <tr>
          <th>Form</th>
          <th>Machine</th>
          <th>Comments</th>
          <th>QA Date</th>
          <th>Submitted on</th>
          <th></th>
        </tr>
      </thead>
      <tbody>\n";
    while ($form_entry = mysqli_fetch_assoc($form_entries)) {
      echo "    <tr>
        <td><a href='form.php?action=show&id=".intval($form_entry['form_id'])."'>".escape_output($form_entry['form_name'])."</a></td>
        <td><a href='form.php?action=show&id=".intval($form_entry['machine_id'])."'>".escape_output($form_entry['machine_name'])."</a></td>
        <td>".escape_output($form_entry['comments'])."</td>
        <td>".escape_output($form_entry['qa_year']."/".$form_entry['qa_month'])."</td>
        <td>".escape_output(format_mysql_timestamp($form_entry['created_at']))."</td>
        <td><a href='form_entry.php?action=edit&id=".intval($form_entry['id'])."'>View</a></td>
      </tr>\n";
    }
    echo "    </tbody>
    </table>\n";
  }
  public function form($currentUser) {
    $output = "<form action='anime.php".(($this->id === 0) ? "" : "?id=".intval($this->id))."' method='POST' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='anime[id]' value='".intval($this->id)."' />")."
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='anime[title]'>Series Title</label>
          <div class='controls'>
            <input name='anime[title]' type='text' class='input-xlarge' id='anime[title]'".(($this->id === 0) ? "" : " value='".escape_output($this->title)."'")." />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[description]'>Description</label>
          <div class='controls'>
            <textarea class='field span4' name='anime[description]' rows='3' id='anime[description]'>".(($this->id === 0) ? "" : escape_output($this->description))."</textarea>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[episode_count]'>Episodes</label>
          <div class='controls'>
            <input name='anime[episode_count]' type='number' min=0 step=1 class='input-small' id='anime[episode_count]'".(($this->id === 0) ? "" : " value=".intval($this->episodeCount))." /> episodes at 
            <input name='anime[episode_minutes]' type='number' min=0 step=1 class='input-small' id='anime[episode_minutes]'".(($this->id === 0) ? "" : " value=".intval($this->episodeLength/60))." /> minutes per episode
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[anime_tags]'>Tags</label>
          <div class='controls'>
            <input name='anime[anime_tags]' type='text' class='token-input input-small' data-field='name' data-url='/tag.php?action=json_search' ".($this->id === 0 ? "" : "data-value='".escape_output(json_encode(array_values($this->tags)))."'")."id='anime[anime_tags]' />
          </div>
        </div>\n";
        if ($currentUser->isModerator || $currentUser->isAdmin()) {
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