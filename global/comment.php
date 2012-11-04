<?php
class Comment extends BaseObject {
  protected $user;
  protected $type;
  protected $typeObject;
  protected $message;
  protected $createdAt;
  protected $updatedAt;

  public function __construct($database, $id=Null, $user=Null, $typeObject=Null) {
    parent::__construct($database, $id);
    $this->typeObject = $typeObject;
    $this->type = Null;
    $this->modelTable = "comments";
    $this->modelPlural = "comments";
    $this->user = $user;
    if ($id === 0) {
      $this->message = $this->createdAt = $this->updatedAt = "";
    } else {
      $this->message = $this->createdAt = $this->updatedAt = Null;
    }
  }
  public function type() {
    return $this->returnInfo('type');
  }
  public function typeObject() {
    if ($this->typeObject === Null) {
      $this->typeObject = new $this->type($this->dbConn, 0);
    }
    return $this->typeObject;
  }
  public function message() {
    return $this->returnInfo('message');
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
      case 'edit':
      case 'delete':
        // if this user is the owner, or if they're the owner of the thing that the comment is posted on, allow them to change this comment.
        if ($authingUser->id === $this->user()->id || $this->typeObject()->allow($authingUser, $action) || $authingUser->isModerator() || $authingUser->isAdmin()) {
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
      case 'show':
      default:
      case 'index':
        return True;
        break;
    }
  }
  public function create_or_update($comment, $currentUser=Null) {
    // creates or updates a comment based on the parameters passed in $comment and this comment's attributes.
    // assumes the existence of updated_at and created_at fields in the database.
    // returns False if failure, or the ID of the comment if success.

    // ensure that this user and comment type object exist.
    try {
      $user = new User($this->dbConn, intval($comment['user_id']));
      $type = new $comment['type']($this->dbConn, intval($comment['type_id']));
    } catch (Exception $e) {
      return False;
    }
    $params = array();
    foreach ($comment as $parameter => $value) {
      if (!is_array($value)) {
        $params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
      }
    }
    //go ahead and create or update this comment.
    if ($this->id != 0) {
      //update this comment.
      $updateComment = $this->dbConn->stdQuery("UPDATE `comments` SET ".implode(", ", $params).", `updated_at` = NOW() WHERE `id` = ".intval($this->id)." LIMIT 1");
      if (!$updateComment) {
        return False;
      }
    } else {
      // add this comment.
      $insertComment = $this->dbConn->stdQuery("INSERT INTO `comments` SET ".implode(",", $params).", `created_at` = NOW(), `updated_at` = NOW()");
      if (!$insertComment) {
        return False;
      } else {
        $this->id = intval($this->dbConn->insert_id);
      }
    }
    return $this->id;
  }
  public function link($action="show", $text=Null, $raw=False) {
    // returns an HTML link to the current comment's profile, with text provided.
    if ($text === Null) {
      $text = "View";
    }
    return "<a href='/comments/".intval($this->id)."/".urlencode($action)."/'>".($raw ? $text : escape_output($text))."</a>";
  }
  public function profile() {
    // displays an comment's profile.
    return;
  }
  public function form($currentUser, $currentObject) {
    $output = "<form action='/comments/".(($this->id === 0) ? "0/new/" : intval($this->id)."/edit/")."' method='POST' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='comment[id]' value='".intval($this->id)."' />")."
      <input type='hidden' name='comment[type]' value='".escape_output(($this->id === 0) ? get_class($currentObject) : $this->type)."' />
      <input type='hidden' name='comment[type_id]' value='".(($this->id === 0) ? intval($currentObject->id) : $this->typeObject->id)."' />
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='comment[message]'>Comment</label>
          <div class='controls'>
            <textarea class='field span4' name='comment[message]' rows='3' id='comment[message]'>".(($this->id === 0) ? "" : escape_output($this->message()))."</textarea>
          </div>
        </div>

        <div class='form-actions'>
          <button type='submit' class='btn btn-primary'>".(($this->id === 0) ? "Add Comment" : "Save changes")."</button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>".(($this->id === 0) ? "Go back" : "Discard changes")."</a>
        </div>
      </fieldset>\n</form>\n";
    return $output;
  }
}
?>