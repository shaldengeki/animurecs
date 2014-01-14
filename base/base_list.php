<?php
abstract class BaseList extends BaseObject {
  // base list from which anime and manga lists inherit methods and properties.
  use Feedable;

  public static $URL = "base_lists";
  public static $TABLE = "";
  public static $PLURAL = "";
  public static $FIELDS = [
    'id' => [
      'type' => 'int',
      'db' => 'id'
    ],
    'startTime' => [
      'type' => 'date',
      'db' => 'time'
    ],
    'endTime' => [
      'type' => 'date',
      'db' => 'time'
    ]
  ];
  public static $JOINS = [
  ];

  public static $PART_NAME, $LIST_TYPE, $TYPE_VERB, $FEED_TYPE, $TYPE_ID = "";

  public $user_id;
  protected $user;

  protected $startTime, $endTime;
  protected $uniqueListAvg, $uniqueListStdDev, $entryAvg, $entryStdDev;
  public $statusStrings, $scoreStrings, $partStrings;

  protected $entries, $uniqueList;

  public function __construct(Application $app, $user_id=Null) {
    parent::__construct($app, $user_id);
    // strings with which to build feed messages.
    // the status messages we build will be different depending on 1) whether or not this is the first entry, and 2) what the status actually is.
    $this->statusStrings = [0 => [
                              0 => "did something mysterious with [TITLE]",
                              1 => "is now [TYPE_VERB] [TITLE]",
                              2 => "marked [TITLE] as completed",
                              3 => "marked [TITLE] as on-hold",
                              4 => "marked [TITLE] as dropped",
                              6 => "plans to watch [TITLE]"
                            ],
                            1 => [
                              0 => "removed [TITLE]",
                              1 => "started [TYPE_VERB] [TITLE]",
                              2 => "finished [TITLE]",
                              3 => "put [TITLE] on hold",
                              4 => "dropped [TITLE]",
                              6 => "now plans to watch [TITLE]"
                            ]
                          ];
    $this->scoreStrings = [0 => ["rated [TITLE] a [SCORE]/10", "and rated it a [SCORE]/10"],
                          1 => ["unrated [TITLE]", "and unrated it"]];
    $this->partStrings = ["just finished [PART_NAME] [PART]/[TOTAL_PARTS] of [TITLE]", "and finished [PART_NAME] [PART]/[TOTAL_PARTS]"];

    $this->uniqueListAvg = $this->uniqueListStdDev = $this->entryAvg = $this->entryStdDev = Null;
    $this->user = Null;
    if ($user_id === 0) {
      $this->user_id = 0;
      $this->username = $this->startTime = $this->endTime = "";
      $this->entries = $this->uniqueList = [];
    } else {
      $this->user_id = intval($user_id);
      $this->entries = $this->uniqueList = Null;
    }
  }
  public function delete($entries=Null) {
    /*
      Deletes list entries.
      Takes an array of entry ids as input, defaulting to all entries.
      Returns a boolean.
    */
    if ($entries === Null) {
      $entries = array_keys($this->entries()->entries());
    }
    if (!is_array($entries) && !is_numeric($entries)) {
      throw new InvalidParameterException($this->app, $entries, "array or numeric");
    }
    if (is_numeric($entries)) {
      $entries = [$entries];
    }
    $entryIDs = [];
    foreach ($entries as $entry) {
      if (is_numeric($entry)) {
        $entryIDs[] = intval($entry);
      }
    }
    if ($entryIDs) {
      $this->beforeDelete();
      $this->app->dbConn->table(static::$TABLE)->where(['user_id' => $this->user_id])->where(['id' => $entryIDs])->limit(count($entryIDs))->delete();
      $this->afterDelete();
    }
    foreach ($entryIDs as $entryID) {
      unset($this->entries[intval($entryID)]);
    }
    return True;
  }
  public function user() {
    if ($this->user === Null) {
      $this->user = new User($this->app, $this->user_id);
    }
    return $this->user;
  }
  public function load() {
    $userInfo = $this->app->dbConn->table(static::$TABLE)
                  ->fields('user_id', 'MIN(time) AS start_time', 'MAX(time) AS end_time')
                  ->where(['user_id' => $this->user_id])
                  ->firstRow();
    $this->startTime = intval($userInfo['start_time']);
    $this->endTime = intval($userInfo['end_time']);
  }
  public function getEntries() {
    // retrieves a list of arrays corresponding to anime list entries belonging to this user.
    $returnList = [];
    $entries = $this->app->dbConn->table(static::$TABLE)
                ->where(['user_id' => $this->user_id])
                ->order('time DESC')
                ->query();
    $entryCount = $this->entryAvg = $this->entryStdDev = $entrySum = 0;
    $entryType = static::$LIST_TYPE."Entry";
    while ($entry = $entries->fetch()) {
      $entry['list'] = $this;
      $currEntry = new $entryType($this->app, intval($entry['id']));
      $returnList[intval($entry['id'])] = $currEntry->set($entry);
      $entrySum += round(floatval($entry['score']), 2);
      $entryCount++;
    }
    $this->entryAvg = ($entryCount === 0) ? 0 : $entrySum / $entryCount;
    $entrySum = 0;
    if ($entryCount > 1) {
      foreach ($returnList as $entry) {
        $entrySum += pow(round(floatval($entry->score), 2) - $this->entryAvg, 2);
      }
      $this->entryStdDev = pow($entrySum / ($entryCount - 1), 0.5);
    }
    return $returnList;
  }
  public function getUniqueList() {
    // retrieves a list of static::$TYPE_ID, time, status, score, static::$PART_NAME arrays corresponding to the latest list entry for each thing the user has consumed.
    $listQuery = $this->app->dbConn->raw("SELECT ".static::$TABLE.".id, ".static::$TABLE.".".static::$TYPE_ID.", ".static::$TABLE.".time, ".static::$TABLE.".score, ".static::$TABLE.".status, ".static::$TABLE.".".static::$PART_NAME." FROM ".static::$TABLE."
      LEFT OUTER JOIN ".static::$TABLE." ".static::$TABLE."2 ON ".static::$TABLE.".user_id = ".static::$TABLE."2.user_id
        AND ".static::$TABLE.".".static::$TYPE_ID." = ".static::$TABLE."2.".static::$TYPE_ID."
        AND ".static::$TABLE.".time < ".static::$TABLE."2.time
      WHERE ".static::$TABLE.".user_id = ".intval($this->user_id)."
        AND ".static::$TABLE."2.time IS NULL
        AND ".static::$TABLE.".status != 0
      ORDER BY status ASC, score DESC");
    $this->uniqueListAvg = $this->uniqueListStdDev = $uniqueListSum = $uniqueListCount = $uniqueListStdDev = 0;
    $returnList = [];
    while ($row = $listQuery->fetch()) {
      if ($row['score'] != 0) {
        $uniqueListCount++;
        $uniqueListSum += round(floatval($row['score']), 2);
        $uniqueListStdDev += pow(round(floatval($row['score']), 2) - $this->uniqueListAvg, 2);
      }
      $returnList[$row[static::$TYPE_ID]] = [
        strtolower(static::$LIST_TYPE) => new static::$LIST_TYPE($this->app, intval($row[static::$TYPE_ID])),
        'id' => $row['id'],
        'time' => $row['time'],
        'score' => $row['score'],
        'status' => $row['status'],
        static::$PART_NAME => $row[static::$PART_NAME]
      ];
    }
    $this->uniqueListAvg = ($uniqueListCount === 0) ? 0 : $uniqueListSum / $uniqueListCount;
    $this->uniqueListStdDev = ($uniqueListCount <= 1) ? 0 : pow($uniqueListStdDev / ($uniqueListCount - 1), 0.5);
    return $returnList;
  }
  public function uniqueList() {
    if ($this->uniqueList === Null) {
      $this->uniqueList = $this->getUniqueList();
    }
    return $this->uniqueList;
  }
  public function uniqueListAvg() {
    if ($this->uniqueListAvg === Null) {
      $this->uniqueList();
    }
    return $this->uniqueListAvg;
  }
  public function uniqueListStdDev() {
    if ($this->uniqueListStdDev === Null) {
      $this->uniqueList();
    }
    return $this->uniqueListStdDev;
  }
  public function length() {
    return $this->entries()->length();
  }
  public function uniqueLength() {
    return count($this->uniqueList());
  }
  public function listSection($status=Null, $score=Null) {
    // returns a section of this user's unique list.
    return array_filter($this->uniqueList(), function($value) use ($status, $score) {
      return (($status !== Null && intval($value['status']) === $status) || ($score !== Null && round(floatval($value['score']), 2) === $score));
    });
  }
  public function prevEntry($id, DateTime $beforeTime) {
    // Returns the previous entry in this user's entry list for static::$TYPE_ID and before $beforeTime.
    $entryType = static::$LIST_TYPE."Entry";

    try {
      $prevEntry = $entryType::Get($this->app, [
        strtolower(static::$LIST_TYPE).'_id' => $id,
        'user_id' => $this->id,
        ['time < ?', $beforeTime->format("Y-m-d H:i:s")]
      ]);
      return $prevEntry;
    } catch (DbException $e) {
      return new $entryType($this->app, 0);
    }
    // $prevEntry = new $entryType($this->app, 0);
    // foreach ($this->entries()->entries() as $entry) {
    //   if ($entry->time >= $beforeTime) {
    //     continue;
    //   }
    //   if ($entry->{strtolower(static::$LIST_TYPE)}->id == $id) {
    //     return $entry;
    //   }
    // }
    // return $prevEntry;
  }
  public function url($action="show", $format=Null, array $params=Null, $id=Null) {
    // returns the url that maps to this object and the given action.
    if ($id === Null) {
      $id = $this->user_id;
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output(static::$TABLE)."/".($action !== "index" ? intval($id)."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
  public function link($action="show", $text=Null, $format=Null, $raw=False, array $params=Null, array $urlParams=Null, $id=Null) {
    // returns an HTML link to the current anime list, with action and text provided.
    $text = ($text === Null) ? "List" : $text;
    return parent::link($action, $text, $format, $raw, $params, $urlParams, $id);
  }
  public function similarity(BaseList $currentList, $minAnime=10) {
    // calculates pearson's r between this list and the current user's list.
    // returns False if there is no similarity computed.
    if ($this->uniqueListStdDev() == 0 || $currentList->uniqueListStdDev() == 0) {
      return False;
    }
    $thisUniques = $this->uniqueList();
    $currentUniques = $currentList->uniqueList();
    
    // filter keys not in common to both of these lists out.
    $commonIDs = array_intersect_key($currentUniques, $this->uniqueList());

    if (count($commonIDs) < $minAnime) {
      return False;
    }

    $thisScores = [];
    $currentScores = [];
    foreach (array_keys($commonIDs) as $animeID) {
      if ($this->uniqueList()[$animeID]['score'] != 0 && $currentUniques[$animeID]['score'] != 0) {
        $thisScores[$animeID] = $this->uniqueList()[$animeID]['score'];
        $currentScores[$animeID] = $currentUniques[$animeID]['score'];
      }
    }
    if (count($thisScores) < $minAnime) {
      return False;
    }
    return correlation($thisScores, $currentScores);
  }
  public function compatibility(BaseList $currentList) {
    $similarity = $this->similarity($currentList);
    return $similarity ? 100 * (1 + $this->similarity($currentList)) / 2.0 : False;
  }
}
?>