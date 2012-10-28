<?php

class Tag {
  public $dbConn;
  public $id;
  public $name;
  public $description;
  public $type;
  public $anime;
  public $manga;
  public $createdAt;
  public $updatedAt;
  public $createdUser;
  public function __construct($database, $id=Null) {
    $this->dbConn = $database;
    if ($id === 0) {
      $this->id = 0;
      $this->name = $this->description = $this->createdAt = $this->updatedAt = "";
      $this->episodeCount = $this->episodeLength = 0;
      $this->type = $this->anime = $this->manga = $this->createdUser = [];
    } else {
      $tagInfo = $this->dbConn->queryFirstRow("SELECT `id`, `name`, `description`, `created_at`, `updated_at` FROM `tags` WHERE `id` = ".intval($id)." LIMIT 1");
      $this->id = intval($tagInfo['id']);
      $this->name = $tagInfo['name'];
      $this->description = $tagInfo['description'];
      $this->createdAt = $tagInfo['created_at'];
      $this->updatedAt = $tagInfo['updated_at'];
      $this->type = $this->getType();
      $this->createdUser = $this->getCreatedUser();
      $this->anime = $this->getAnime();
      //$this->manga = $this->getManga();
    }
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
      case 'json_search':
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
  public function create_or_update_tagging($anime_id, $currentUser) {
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
    if ($animus === False) {
      $animus = array_keys($this->anime);
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
      foreach ($this->anime as $anime) {
        if (!in_array($anime['id'], $tag['anime_tags'])) {
          $animeToDrop[] = intval($anime['id']);
        }
      }
      $drop_anime = $this->drop_taggings($animeToDrop);
      foreach ($tag['anime_tags'] as $animeToAdd) {
        // find this animeID.
        $animeID = intval($this->dbConn->queryFirstValue("SELECT `id` FROM `anime` WHERE `id` = ".intval($animeToAdd)." LIMIT 1"));
        if ($animeID) {
          $create_tagging = $this->create_or_update_tagging($animeID, $currentUser);
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
    if ($this->approvedOn === '' or !$this->approvedOn) {
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
    // retrieves an id,name array corresponding to the user who created this tag.
    return $this->dbConn->queryFirstRow("SELECT `users`.`id`, `users`.`name` FROM `tags` LEFT OUTER JOIN `users` ON `users`.`id` = `tags`.`created_user_id` WHERE `tags`.`id` = ".intval($this->id));
  }
  public function getType() {
    // retrieves an id,name array corresponding to the tag type this tag belongs to.
    return $this->dbConn->queryFirstRow("SELECT `tag_types`.`id`, `tag_types`.`name` FROM `tags` LEFT OUTER JOIN `tag_types` ON `tag_types`.`id` = `tags`.`tag_type_id` WHERE `tags`.`id` = ".intval($this->id));
  }
  public function getAnime() {
    // retrieves a list of id,title arrays corresponding to anime tagged with this tag.
    $animus = [];
    $animeQuery = $this->dbConn->stdQuery("SELECT `id`, `title` FROM `anime_tags` LEFT OUTER JOIN `anime` ON `anime`.`id` = `anime_tags`.`anime_id` WHERE `tag_id` = ".intval($this->id)." ORDER BY `anime`.`title` ASC");
    while ($anime = $animeQuery->fetch_assoc()) {
      $animus[intval($anime['id'])] = $anime;
    }
    return $animus;
  }
  public function link($action="show", $text=Null, $raw=False) {
    // returns an HTML link to the current tag's profile, with text provided.
    if ($text === Null) {
      $text = $this->title ? $this->title : "Info";
    }
    return "<a href='/tag.php?action=".urlencode($action)."&id=".intval($this->id)."'>".($raw ? $text : escape_output($text))."</a>";
  }
  public function profile() {
    // displays a tag's profile.
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
    $output = "<form action='tag.php".(($this->id === 0) ? "" : "?id=".intval($this->id))."' method='POST' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='tag[id]' value='".intval($this->id)."' />")."
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='tag[name]'>Tag Name</label>
          <div class='controls'>
            <input name='tag[name]' type='text' class='input-xlarge' id='tag[name]'".(($this->id === 0) ? "" : " value='".escape_output($this->name)."'")." />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[description]'>Description</label>
          <div class='controls'>
            <textarea class='field span4' name='tag[description]' rows='3' id='tag[description]'>".(($this->id === 0) ? "" : escape_output($this->description))."</textarea>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[tag_type_id]'>Type</label>
          <div class='controls'>
            ".display_tag_type_dropdown($this->dbConn, "tag[tag_type_id]", ($this->id === 0 ? False : intval($this->type['id'])))."
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[anime_tags]'>Anime</label>
          <div class='controls'>
            <input name='tag[anime_tags]' type='text' class='token-input input-small' data-field='title' data-url='/anime.php?action=json_search' data-value='".($this->id === 0 ? "[]" : escape_output(json_encode(array_values($this->anime))))."' id='tag[anime_tags]' />
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