<?php
require_once("../core/model.php");

abstract class Entry extends Model {
  // feed entry class.
  use Commentable;

  public static $TABLE = "";
  public static $PLURAL = "";
  public static $FIELDS = [
    'id' => [
      'type' => 'int',
      'db' => 'id'
    ],
    'userId' => [
      'type' => 'int',
      'db' => 'user_id'
    ],
    'time' => [
      'type' => 'date',
      'db' => 'time'
    ],
    'status' => [
      'type' => 'int',
      'db' => 'status'
    ],
    'score' => [
      'type' => 'float',
      'db' => 'score'
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
    'comments' => [
      'obj' => 'CommentEntry',
      'table' => 'comments',
      'own_col' => 'id',
      'join_col' => 'parent_id',
      'type' => 'many'
    ]
  ];

  public static $ENTRY_TYPE, $TYPE_ID, $PART_NAME = "";

  // this is the parent object to which the entry belongs.
  // e.g. for an animelist entry on a user's page, it'd be the animelist
  // and for a comment entry on the user's page, it'd be the comment
  public $object;

  public function __construct(Application $app, $id=Null, $params=Null) {
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->time = new DateTime("now", $this->app->serverTimeZone);
      $this->status = $this->score = 0;
      $this->comments = [];
    }
    if (is_array($params)) {
      foreach ($params as $key=>$value) {
        $this->{$this->humanizeParameter($key)} = $value;
      }
    }
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      /* Require the authing user to be logged in. */
      case 'new':
      case 'comment':
        if ($authingUser->loggedIn()) {
          return True;
        }
        return False;
        break;

      /* Require the current user to be the requested user, or be staff. */
      case 'edit':
      case 'delete':
        if ($authingUser->id == $this->user->id || $authingUser->isStaff()) {
          return True;
        }
        return False;
        break;

      /* Require the authing user to be staff. */
      case 'index':
        if ($authingUser->isAdmin()) {
          return True;
        }
        return False;
        break;

      /* Public views. */
      case 'show':
        return True;
        break;
      default:
        return False;
        break;
    }
  }
  public function validate(array $entry) {
    // validates a pending base_entry creation or update.
    $validationErrors = [];

    try {
      parent::validate($entry);
    } catch (ValidationException $e) {
      $validationErrors = array_merge($validationErrors, $e->messages);
    }

    if (!isset($entry['user_id']) || !is_integral($entry['user_id']) || intval($entry['user_id']) < 1) {
      $validationErrors[] = "User ID must be valid";
    }
    try {
      $user = new User($this->app, intval($entry['user_id']));
      $user->load();
    } catch (DatabaseException $e) {
      $validationErrors[] = "User ID must exist";
    }

    if (!isset($entry[static::$TYPE_ID]) || !is_integral($entry[static::$TYPE_ID]) || intval($entry[static::$TYPE_ID]) < 1) {
      $validationErrors[] = static::$ENTRY_TYPE." ID must be valid";
    }
    try {
      $parentMedia = new static::$ENTRY_TYPE($this->app, intval($entry[static::$TYPE_ID]));
      $parentMedia->load();
    } catch (DatabaseException $e) {
      $validationErrors[] = static::$ENTRY_TYPE." ID must exist";
    }

    if (isset($entry['time']) && $entry['time'] && !strtotime($entry['time'])) {
      $validationErrors[] = "Entry time must be a parseable date-time stamp";
    }

    if (!isset($entry['status']) || !is_integral($entry['status']) || !isset(statusArray()[intval($entry['status'])])) {
      $validationErrors[] = "Entry status must be one of 0,1,2,3,4,6";
    }

    if (isset($entry['score']) && (!is_numeric($entry['score']) || round(floatval($entry['score']), 2) < 0 || round(floatval($entry['score']), 2) > 10)) {
      $validationErrors[] = "Entry score must be numeric and between 0 and 10";
    }

    if (isset($entry[static::$PART_NAME]) && (!is_integral($entry[static::$PART_NAME]) || intval($entry[static::$PART_NAME]) < 0 || ($parentMedia->{static::$PART_NAME."Count"} > 0 && intval($entry[static::$PART_NAME]) > $parentMedia->{static::$PART_NAME."Count"}))) {
      $validationErrors[] = static::$PART_NAME." number must be integral and at least 0, less than the ".static::$PART_NAME." count of its parent.";
    }

    if ($validationErrors) {
      throw new ValidationException($this->app, $entry, $validationErrors);
    }
    return True;
  }
  public function delete($entries=Null) {
    // delete comments on this thing before deleting this thing.
    foreach ($this->comments as $comment) {
      $comment->delete();
    }
    parent::delete();
  }
  public function create_or_update(array $entry, array $whereConditions=Null) {
    /*
      Creates or updates an existing list entry for the current user.
      Takes an array of entry parameters.
      Returns the resultant list entry ID.
    */
    // validate this entry prior to doing anything else.
    $this->validate($entry);

    foreach ($entry as $parameter => $value) {
      if (!is_array($value)) {
        if (is_integral($value)) {
          $entry[$parameter] = intval($value);
        } elseif (is_numeric($value)) {
          $entry[$parameter] = round(floatval($value), 2);
        }
      }
    }

    // check to see if this is an update.
    $this->app->dbConn->table(static::$TABLE)->set($entry);
    if ($this->id != 0) {
      $this->beforeUpdate($entry);
      $this->app->dbConn->where(['id' => $entry['id']])->limit(1)->update();
      $returnValue = intval($entry['id']);
      $this->afterUpdate($entry);
    } else {
      $this->beforeCreate($entry);
      if (!isset($entry['time'])) {
        $this->app->dbConn->set(['time=NOW()']);
      }
      $this->app->dbConn->insert();
      $returnValue = intval($this->app->dbConn->lastInsertId);
      $entry['id'] = $returnValue;
      $this->afterCreate($entry);
    }
    return $returnValue;
  }

  public function statusSince() {
    /*
      Returns the time of the earliest entry that set the status to the status of the current entry, for the current entry's user and anime.
      Only scans the subset of entries surrounding this one that have this status set.
      e.g. if the statuses are:
      <--- earlier                                    recent ----->
      1111111122222222222222222244444444444444222222222222222222222
      and you call statusSince on -------------------^ this one
      you'll get the time of -----------------^ this one.
              ^----------------^
      returned time          statusSince() called
    */

    // get the latest entry that a) had a different status and b) happened before this one
    try {
      $minTime = $this->app->dbConn->table(static::$TABLE)
        ->fields('MAX('.static::$FIELDS['time']['db'].') AS '.static::$FIELDS['time']['db'])
        ->where([
                [static::$FIELDS['time']['db'].' <=  ?', $this->time->format('Y-m-d H:i:s')],
                [static::$FIELDS['status']['db'].' != ?', intval($this->status)]
                ])
        ->firstValue();
      $minTime = new \DateTime($minTime, $this->app->serverTimeZone);
    } catch (NoDatabaseRowsRetrievedException $e) {
      // there are no other statuses prior to this.
      $minTime = Null;
    }

    // get the earliest entry that a) has the current status and b) happened after minTime
    $startTime = $this->app->dbConn->table(static::$TABLE)
      ->fields('MIN('.static::$FIELDS['time']['db'].') AS '.static::$FIELDS['time']['db'])
      ->where([
              [static::$FIELDS['time']['db'].' >=  ?', $minTime->format('Y-m-d H:i:s')],
              [static::$FIELDS['status']['db'].' = ?', intval($this->status)]
              ])
      ->firstValue();
    $startTime = new \DateTime($startTime, $this->app->serverTimeZone);

    /*
    $sinceQuery = $this->app->dbConn->query("SELECT list_table.".static::$TYPE_ID.", list_table.time FROM ".static::$TABLE." list_table
      LEFT OUTER JOIN ".static::$TABLE." list_table2 ON list_table.".static::$FIELDS['userId']['db']." = list_table2.".static::$FIELDS['userId']['db']."
        AND list_table.".static::$TYPE_ID." = list_table2.".static::$TYPE_ID."
        AND list_table.".static::$FIELDS['time']['db']." < list_table2.".static::$FIELDS['time']['db']."
        AND list_table2.".static::$FIELDS['status']['db']." != :status
      WHERE list_table.".static::$FIELDS['userId']['db']." = ".intval($this->id)."
      AND list_table.".static::$TYPE_ID." = :anime_id
      AND list_table.".static::$FIELDS['status']['db']." = :status
      AND list_table2.".static::$FIELDS['time']['db']." IS NULL
      ORDER BY ".static::$TYPE_ID." ASC;",
      [':anime_id' => $this->]);
    $startedAnime = [];
    while ($row = $sinceQuery->fetch()) {
      $startedAnime[intval($row['anime_id'])] = new \DateTime($row['time'], $this->app->serverTimeZone);
    }
    */
    return $startTime;
  }
  
  // all feed entry classes must implement a way to format said feed entries into markup.
  abstract public function formatFeedEntry();

  // also a method to determine the time that this entry was posted.
  abstract public function time();
}

?>