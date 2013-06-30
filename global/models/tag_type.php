<?php
class TagType extends BaseObject {
  public static $modelTable = "tag_types";
  public static $modelPlural = "tagTypes";

  protected $name;
  protected $description;

  protected $tags;
  protected $createdUser;
  public function __construct(Application $app, $id=Null) {
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->name = $this->description = "";
      $this->tags = [];
      $this->createdUser = Null;
    } else {
      $this->name = $this->description = $this->tags = $this->createdUser = Null;
    }
  }
  public function name() {
    return $this->returnInfo('name');
  }
  public function description() {
    return $this->returnInfo('description');
  }
  public function allow(User $authingUser, $action, array $params=Null) {
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
      case 'index':
        return True;
        break;
      default:
        return False;
        break;
    }
  }
  public function validate(array $tag_type) {
    if (!parent::validate($tag_type)) {
      return False;
    }
    if (!isset($tag_type['name']) || strlen($tag_type['name']) < 1) {
      return False;
    }
    if (isset($tag_type['description']) && (strlen($tag_type['description']) < 1 || strlen($tag_type['description']) > 600)) {
      return False;
    }
    if (isset($tag_type['created_user_id'])) {
      if (!is_numeric($tag_type['created_user_id']) || intval($tag_type['created_user_id']) != $tag_type['created_user_id'] || intval($tag_type['created_user_id']) <= 0) {
        return False;
      } else {
        try {
          $approvedUser = new User($this->app, intval($tag_type['created_user_id']));
          $approvedUser->getInfo();
        } catch (Exception $e) {
          return False;
        }
      }
    }
    return True;
  }
  public function create_or_update(array $tag_type, array $whereConditions=Null) {
    // creates or updates a tag type based on the parameters passed in $tag_type and this object's attributes.
    // returns False if failure, or the ID of the tag type if success.
    // make sure tag type name adheres to standards.
    $tag_type['name'] = str_replace("_", " ", strtolower($tag_type['name']));
    return parent::create_or_update($tag_type);
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
    // return $this->dbConn->firstRow("SELECT `users`.`id`, `users`.`name` FROM `anime` LEFT OUTER JOIN `users` ON `users`.`id` = `anime`.`approved_user_id` WHERE `anime`.`id` = ".intval($this->id));
  }
  public function getCreatedUser() {
    // retrieves a user object corresponding to the user who created this tag type.
    return new User($this->app, intval($this->dbConn->firstValue("SELECT `created_user_id` FROM `tag_types` WHERE `tag_types`.`id` = ".intval($this->id))));
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
    $tagIDs = $this->dbConn->query("SELECT `id` FROM `tags` WHERE `tag_type_id` = ".intval($this->id)." ORDER BY `name` ASC");
    while ($tagID = $tagIDs->fetch_assoc()) {
      $tags[] = new Tag($this->app, intval($tagID['id']));
    }
    return $tags;
  }
  public function tags() {
    if ($this->tags === Null) {
      $this->tags = new TagGroup($this->app, $this->getTags());
    }
    return $this->tags;
  }

  public function render() {
    if (isset($_POST['tag_type']) && is_array($_POST['tag_type'])) {
      $updateTagType = $this->create_or_update($_POST['tag_type']);
      if ($updateTagType) {
        $this->app->delayedMessage("Successfully updated.", "success");
        $this->app->redirect($this->url("show"));
      } else {
         $this->app->delayedMessage("An error occurred while creating or updating this tag type.", "error");
        $this->app->redirect($this->id === 0 ? $this->url("new") : $this->url("edit"));
      }
    }
    switch($this->app->action) {
      case 'new':
        $title = "Create a Tag Type";
        $output = $this->view('new');
        break;
      case 'edit':
        if ($this->id == 0) {
          $this->app->display_error(404);
        }
        $title = "Editing ".escape_output($this->name());
        $output = $this->view('edit');
        break;
      case 'show':
        if ($this->id == 0) {
          $this->app->display_error(404);
        }
        $title = escape_output($this->name());
        $output = $this->view('show');
        break;
      case 'delete':
        if ($this->id == 0) {
          $this->app->display_error(404);
        }
        if (!$this->app->checkCSRF()) {
          $this->app->display(403);
        }
        $deleteTagType = $this->delete();
        if ($deleteTagType) {
          $this->app->delayedMessage('Successfully deleted '.$this->name().'.', "success");
          $this->app->redirect();
        } else {
          $this->app->delayedMessage('An error occurred while deleting '.$this->name().'.', 'error');
          $this->app->redirect();
        }
        break;
      default:
      case 'index':
        $title = "All Tag Types";
        $output = $this->view('index');
        break;
    }
    return $this->app->render($output, ['subtitle' => $title]);
  }
}
?>