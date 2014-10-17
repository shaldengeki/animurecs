<?php
class Comment extends Model {
  use Feedable;

  public static $TABLE = "comments";
  public static $PLURAL = "comments";
  public static $FIELDS = [
    'id' => [
      'type' => 'int',
      'db' => 'id'
    ],
    'userId' => [
      'type' => 'int',
      'db' => 'user_id'
    ],
    'type' => [
      'type' => 'str',
      'db' => 'type'
    ],
    'parentId' => [
      'type' => 'int',
      'db' => 'parent_id'
    ],
    'message' => [
      'type' => 'str',
      'db' => 'message'
    ],
    'createdAt' => [
      'type' => 'date',
      'db' => 'created_at'
    ],
    'updatedAt' => [
      'type' => 'date',
      'db' => 'updated_at'
    ]
  ];
  public static $JOINS = [
    'user' => [
      'obj' => 'User',
      'table' => 'users',
      'own_col' => 'user_id',
      'join_col' => 'id',
      'type' => 'one'
    ],
    'entries' => [
      'obj' => 'CommentEntry',
      'table' => 'comments',
      'own_col' => 'id',
      'join_col' => 'parent_id',
      'condition' => "comments.type = 'CommentEntry'",
      'type' => 'many'
    ]
  ];
  public $parent;
  public $ancestor;
  public $depth;

  public $entries;

  public function __construct(Application $app, $id=Null, User $user=Null, Model $parent=Null) {
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->message = "";
      $this->parent = $parent;
      $this->parentId = ($parent !== Null) ? $parent->id : Null;
      $this->type = ($parent !== Null) ? get_class($this->parent) : Null;
      $this->user = $user;
      $this->userId = ($user !== Null) ? $user->id : Null;
    }
  }
  public function depth() {
    if ($this->depth === Null) {
      $parentClass = get_class($this->parent());
      $this->depth = method_exists($this->parent(), 'depth') ? $this->parent()->depth() + 1 : ($parentClass::MODEL_NAME() == "User" ? 0 : 1);
    }
    return $this->depth;
  }
  public function ancestor() {
    if ($this->ancestor === Null) {
      $this->ancestor = method_exists($this->parent(), 'parent') && $this->parent()->type != "User" && $this->parent()->type != "Anime" ? $this->parent()->ancestor() : $this->parent();
    }
    return $this->ancestor;
  }
  public function parent() {
    if ($this->parent === Null) {
      $type = $this->type;
      $this->parent = new $type($this->app, $this->parentId);
    }
    return $this->parent;
  }
  public function getEntries() {
    // retrieves a list of id arrays corresponding to the comments belonging to this comment.
    $returnList = [];
    $commentEntries = $this->app->dbConn->table(static::$TABLE)
                        ->where(["type='Comment'", 'parent_id' => $this->id])
                        ->order('time ASC')
                        ->query();
    while ($entry = $commentEntries->fetch()) {
      $newEntry = new CommentEntry($this->app, intval($entry['id']), $entry);
      $returnList[intval($entry['id'])] = $newEntry;
    }
    return $returnList;
  }
  public function validate(array $comment) {
    $validationErrors = [];
    try {
      parent::validate($comment);
    } catch (ValidationException $e) {
      $validationErrors = array_merge($validationErrors, $e->messages);
    }
    if (!isset($comment['user_id']) || !is_integral($comment['user_id']) || intval($comment['user_id']) <= 0) {
      $validationErrors[] = "User ID must be valid";
    }else {
      try {
        $createdUser = new User($this->app, intval($comment['user_id']));
      } catch (DatabaseException $e) {
        $validationErrors[] = "User must exist";
      }
    }
    if (!isset($comment['parent_id']) || !is_integral($comment['parent_id']) || intval($comment['parent_id']) <= 0) {
      $validationErrors[] = "Parent ID must be valid";
    } else {
      try {
        $parent = new $comment['type']($this->app, intval($comment['parent_id']));
        $parent->load();
      } catch (DatabaseException $e) {
        $validationErrors[] = "Parent must be valid";
      }
    }
    if (!isset($comment['message']) || mb_strlen($comment['message']) < 1 || mb_strlen($comment['message']) > 300) {
      $validationErrors[] = "Message must be between 1 and 300 characters long";
    }
    if ($validationErrors) {
      throw new ValidationException($this->app, $comment, $validationErrors);
    } else {
      return True;
    }
  }
  public function url($action="show", $format=Null, array $params=Null, $id=Null) {
    // returns the url that maps to this comment and the given action.
    // if we're showing this comment, show its parent instead.
    if ($action == "show") {
      return $this->parent()->url($action, $format, $params);
    }
    if ($id === Null) {
      $id = intval($this->id);
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output(static::$TABLE)."/".($action !== "index" ? intval($id)."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
}
?>