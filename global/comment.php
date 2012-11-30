<?php
class Comment extends BaseObject {
  protected $userId;
  protected $user;
  protected $type;
  protected $parentId;
  protected $parent;
  protected $message;
  protected $createdAt;
  protected $updatedAt;

  public function __construct(DbConn $database, $id=Null, User $user=Null, BaseObject $parent=Null) {
    parent::__construct($database, $id);
    $this->modelTable = "comments";
    $this->modelPlural = "comments";
    if ($id === 0) {
      $this->message = $this->createdAt = $this->updatedAt = "";
      $this->parent = $parent;
      $this->parentId = $parent->id;
      $this->type = get_class($this->parent);
      $this->user = $user;
      $this->userId = $user->id;
    } else {
      $this->message = $this->createdAt = $this->updatedAt = $this->userId = $this->parent = $this->parentId = $this->type = Null;
    }
  }
  public function userId() {
    return $this->returnInfo('userId');
  }
  public function user() {
    if ($this->user === Null) {
      $this->user = new User($this->dbConn, $this->userId());
    }
    return $this->user;
  }
  public function type() {
    return $this->returnInfo('type');
  }
  public function parentId() {
    return $this->returnInfo('parentId');
  }
  public function parent() {
    if ($this->parent === Null) {
      $type = $this->type();
      $this->parent = new $type($this->dbConn, $this->parentId());
    }
    return $this->parent;
  }
  public function message() {
    return $this->returnInfo('message');
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
      case 'edit':
        // if this user is the owner of the comment, allow them to edit this comment.
        if ($authingUser->id === $this->user()->id || $authingUser->isModerator() || $authingUser->isAdmin()) {
          return True;
        }
        return False;
        break;
      case 'delete':
        // if this user is the owner, or if they're the owner of the thing that the comment is posted on, allow them to delete this comment.
        if ($authingUser->id === $this->user()->id || $this->parent()->allow($authingUser, $action) || $authingUser->isModerator() || $authingUser->isAdmin()) {
          return True;
        }
        return False;
        break;
      case 'new':
        // if user is logged in and this user has perms to comment on the parent, allow them to post a new comment.
        if ($authingUser->loggedIn() && $this->parent()->allow($authingUser, 'comment')) {
          return True;
        }
        return False;
        break;
      case 'show':
      case 'index':
        // if this user can view the parent object, allow them to view comments belonging to this object.
        if ($this->parent()->allow($authingUser, 'show')) {
          return True;
        }
        return False;
        break;
      default:
        return False;
        break;
    }
  }
  public function profile() {
    // displays an comment's profile.
    $output = $this->message();
    return $output;
  }
  public function form(User $currentUser, BaseObject $currentObject) {
    $output = "    <form action='".(($this->id === 0) ? $this->url("new") : $this->url("edit"))."' method='POST' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='comment[id]' value='".intval($this->id)."' />")."
      <input type='hidden' name='comment[user_id]' value='".intval($currentUser->id)."' />
      <input type='hidden' name='comment[type]' value='".escape_output(($this->id === 0) ? get_class($currentObject) : $this->type())."' />
      <input type='hidden' name='comment[parent_id]' value='".(($this->id === 0) ? intval($currentObject->id) : $this->parent()->id)."' />
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
  public function inlineForm(User $currentUser, BaseObject $currentObject) {
    $output = "    <form class='form-inline' action='".(($this->id === 0) ? $this->url("new") : $this->url("edit"))."' method='POST'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='comment[id]' value='".intval($this->id)."' />")."
      <input type='hidden' name='comment[user_id]' value='".intval($currentUser->id)."' />
      <input type='hidden' name='comment[type]' value='".escape_output(($this->id === 0) ? get_class($currentObject) : $this->type())."' />
      <input type='hidden' name='comment[parent_id]' value='".(($this->id === 0) ? intval($currentObject->id) : $this->parent()->id)."' />
      <input type='text' name='comment[message]'".(($this->id === 0) ? "placeholder='Leave a comment!'" : "value='".escape_output($this->message())."'")." />
      <button type='submit' class='btn btn-primary'>".(($this->id === 0) ? "Send" : "Update")."</button>
    </form>\n";
    return $output;
  }
}
?>