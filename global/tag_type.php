<?php
class TagType extends BaseObject {
  protected $name;
  protected $description;

  protected $tags;
  protected $createdUser;
  public function __construct($database, $id=Null) {
    parent::__construct($database, $id);
    $this->modelTable = "tag_types";
    $this->modelPlural = "tagTypes";
    if ($id === 0) {
      $this->name = $this->description = "";
      $this->tags = [];
      $this->createdUser = Null;
    } else {
      $fetchID = $this->dbConn->queryCount("SELECT COUNT(*) FROM `tag_types` WHERE `id` = ".intval($this->id));
      if ($fetchID < 1) {
        throw new Exception('ID Not Found');
      }
      $this->name = $this->description = $this->tags = $this->createdUser = Null;
    }
  }
  public function name() {
    return $this->returnInfo('name');
  }
  public function allow($authingUser, $action) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      // case 'approve':
      case 'new':
      case 'edit':
      case 'delete':
        if ($authingUser->isAdmin()) {
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
  public function create_or_update($tag_type, $currentUser) {
    // creates or updates a tag type based on the parameters passed in $tag_type and this object's attributes.
    // returns False if failure, or the ID of the tag type if success.
    // make sure tag type name adheres to standards.
    $tag_type['name'] = str_replace("_", " ", strtolower($tag_type['name']));
    $params = array();
    foreach ($tag_type as $parameter => $value) {
      if (!is_array($value)) {
        $params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
      }
    }
    //go ahead and create or update this tag type.
    if ($this->id != 0) {
      //update this tag_type.
      $updateTagType = $this->dbConn->stdQuery("UPDATE `tag_types` SET ".implode(", ", $params)." WHERE `id` = ".intval($this->id)." LIMIT 1");
      if (!$updateTagType) {
        return False;
      }
    } else {
      // add this tag type.
      $insertTagType = $this->dbConn->stdQuery("INSERT INTO `tag_types` SET ".implode(",", $params).", `created_user_id` = ".intval($currentUser->id));
      if (!$insertTagType) {
        return False;
      } else {
        $this->id = intval($this->dbConn->insert_id);
      }
    }
    return $this->id;
  }
  public function delete() {
    // delete this tag type from the database.
    // returns a boolean.
    $deleteType = $this->dbConn->stdQuery("DELETE FROM `tag_types` WHERE `id` = ".intval($this->id)." LIMIT 1");
    if (!$deleteType) {
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
    // retrieves a user object corresponding to the user who created this tag type.
    return new User($this->dbConn, intval($this->dbConn->queryFirstValue("SELECT `created_user_id` FROM `tag_types` WHERE `tag_types`.`id` = ".intval($this->id))));
  }
  public function createdUser() {
    if ($this->createdUser === Null) {
      $this->createdUser = $this->getCreatedUser();
    }
    return $this->createdUser;
  }
  public function getTags() {
    // retrieves a list of id arrays corresponding to tags belonging to this tag type
    $tags = [];
    $tagIDs = $this->dbConn->stdQuery("SELECT `id` FROM `tags` WHERE `tag_type_id` = ".intval($this->id)." ORDER BY `name` ASC");
    while ($tagID = $tagIDs->fetch_assoc()) {
      $tags[] = new Tag($this->dbConn, intval($tagID['id']));
    }
    return $tags;
  }
  public function tags() {
    if ($this->tags === Null) {
      $this->tags = $this->getTags();
    }
    return $this->tags;
  }
  public function link($action="show", $text=Null, $raw=False) {
    // returns an HTML link to the current tag's profile, with text provided.
    if ($text === Null) {
      $text = $this->title ? $this->title : "Info";
    }
    return "<a href='/tag_types/".intval($this->id)."/".urlencode($action)."/'>".($raw ? $text : escape_output($text))."</a>";
  }
  public function profile() {
    // displays a tag type's profile.
    return;
  }
  public function form($currentUser) {
    $output = "<form action='/tag_types/".(($this->id === 0) ? "0/new/" : intval($this->id)."/edit/")."' method='POST' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='tag_type[id]' value='".intval($this->id)."' />")."
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='tag_type[name]'>Name</label>
          <div class='controls'>
            <input name='tag_type[name]' type='text' class='input-xlarge' id='tag_type[name]'".(($this->id === 0) ? "" : " value='".escape_output($this->name)."'")." />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag_type[description]'>Description</label>
          <div class='controls'>
            <textarea class='field span4' name='tag_type[description]' rows='3' id='tag_type[description]'>".(($this->id === 0) ? "" : escape_output($this->description))."</textarea>
          </div>
        </div>\n";
        /*
        if ($currentUser->isModerator || $currentUser->isAdmin()) {
          $output .= "        <div class='control-group'>
          <label class='control-label' for='tag_type[approved]'>Approved</label>
          <div class='controls'>
            <input name='tag_type[approved]' type='checkbox' value=1 ".($this->isApproved() ? "checked=checked" : "")."/>
          </div>
        </div>\n";
        }
        */
        $output .= "    <div class='form-actions'>
          <button type='submit' class='btn btn-primary'>".(($this->id === 0) ? "Create Tag Type" : "Save changes")."</button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>".(($this->id === 0) ? "Go back" : "Discard changes")."</a>
        </div>
      </fieldset>\n</form>\n";
    return $output;
  }
}
?>