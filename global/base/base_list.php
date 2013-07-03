<?php
abstract class BaseList extends BaseObject {
  // base list from which anime and manga lists inherit methods and properties.
  use Feedable;

  public $user_id;
  protected $user;

  protected $startTime, $endTime;
  protected $uniqueListAvg, $uniqueListStdDev, $entryAvg, $entryStdDev;
  protected $statusStrings, $scoreStrings, $partStrings;

  protected $entries, $uniqueList;

  protected $partName, $listType, $listTypeLower, $typeVerb, $typeID, $feedType;

  public function __construct(Application $app, $user_id=Null) {
    parent::__construct($app, $user_id);
    $this->partName = "";
    $this->listType = "";
    $this->typeVerb = "";
    $this->listTypeLower = strtolower($this->listType);
    $this->feedType = "";
    $this->typeID = $this->listTypeLower.'_id';
    // strings with which to build feed messages.
    // the status messages we build will be different depending on 1) whether or not this is the first entry, and 2) what the status actually is.
    $this->statusStrings = [0 => [0 => "did something mysterious with [TITLE]",
                                      1 => "is now [TYPE_VERB] [TITLE]",
                                      2 => "marked [TITLE] as completed",
                                      3 => "marked [TITLE] as on-hold",
                                      4 => "marked [TITLE] as dropped",
                                      6 => "plans to watch [TITLE]"],
                                  1 => [0 => "removed [TITLE]",
                                            1 => "started [TYPE_VERB] [TITLE]",
                                            2 => "finished [TITLE]",
                                            3 => "put [TITLE] on hold",
                                            4 => "dropped [TITLE]",
                                            6 => "now plans to watch [TITLE]"]];
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
  public function create_or_update(array $entry, array $whereConditions=Null) {
    /*
      Creates or updates an existing list entry for the current user.
      Takes an array of entry parameters.
      Returns the resultant list entry ID.
    */
    // ensure that this user and list type exist.
    try {
      $user = new User($this->app, intval($entry['user_id']));
      $user->getInfo();
      $type = new $this->listType($this->app, intval($entry[$this->typeID]));
      $type->getInfo();
    } catch (Exception $e) {
      return False;
    }
    foreach ($entry as $parameter => $value) {
      if (!is_array($value)) {
        if (is_numeric($value)) {
          $entry[$parameter] = intval($value);
        } else {
          $entry[$parameter] = $this->dbConn->escape($value);
        }
      }
    }

    // check to see if this is an update.
    $entryGroup = $this->entries();
    if (!isset($entry['id'])) {
      // see if there are any entries matching these params for this user.
      try {
        $checkExists = $this->dbConn->table(static::$MODEL_TABLE)->fields('id')->where($entry)->limit(1)->firstValue();
        if ($checkExists) {
          // if entry exists, set its ID.
          $entry['id'] = intval($checkExists);
        }
      } catch (DbException $e) {
        // entry does not exist, no need to do anything.
      }
    }
    $this->dbConn->table(static::$MODEL_TABLE);
    if (isset($entryGroup->entries()[intval($entry['id'])])) {
      // this is an update.
      $this->beforeUpdate($entry);
      $updateDependency = $this->dbConn->set($entry)->where(['id' => $entry['id']])->limit(1)->update();
      if (!$updateDependency) {
        return False;
      }
      // update list locally.
      if ($this->uniqueList()[intval($entry[$this->typeID])]['score'] != intval($entry['score']) || $this->uniqueList()[intval($entry[$this->typeID])]['status'] != intval($entry['status']) || $this->uniqueList()[intval($entry[$this->typeID])][$this->partName] != intval($entry[$this->partName])) {
        if (intval($entry['status']) == 0) {
          unset($this->uniqueList[intval($entry[$this->typeID])]);
        } else {
          $this->uniqueList[intval($entry[$this->typeID])] = [$this->typeID => intval($entry[$this->typeID]), 'time' => $entry['time'], 'score' => intval($entry['score']), 'status' => intval($entry['status']), $this->partName => intval($entry[$this->partName])];
        }
      }
      $returnValue = intval($entry['id']);
      $this->afterUpdate($entry);
    } else {
      // this is a new entry.
      if (!isset($entry['time'])) {
        $dateTime = new DateTime('now', $this->app->serverTimeZone);
        $entry['time'] = $dateTime->format("Y-m-d H:i:s");
      }
      $this->beforeUpdate($entry);
      $insertEntry = $this->dbConn->set($entry)->insert();
      if (!$insertEntry) {
        return False;
      }
      $returnValue = intval($insertEntry);
      $entry['id'] = $returnValue;
      // insert list locally.
      $this->uniqueList();
      if (intval($entry['status']) == 0) {
        unset($this->uniqueList[intval($entry[$this->typeID])]);
      } else {
        $this->uniqueList[intval($entry[$this->typeID])] = [$this->typeID => intval($entry[$this->typeID]), 'time' => $entry['time'], 'score' => intval($entry['score']), 'status' => intval($entry['status']), $this->partName => intval($entry[$this->partName])];
      }
      $this->afterUpdate($entry);
    }
    //$this->entries[intval($returnValue)] = $entry;
    return $returnValue;
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
      return False;
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
      $drop_entries = $this->dbConn->table(static::$MODEL_TABLE)->where(['user_id' => $this->user_id])->where(['id' => $entryIDs])->limit(count($entryIDs))->delete();
      if (!$drop_entries) {
        return False;
      }
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
  public function getInfo() {
    $userInfo = $this->dbConn->table(static::$MODEL_TABLE)->fields('user_id', 'MIN(time) AS start_time', 'MAX(time) AS end_time')->where(['user_id' => $this->user_id])->firstRow();
    if (!$userInfo) {
      return False;
    }
    $this->startTime = intval($userInfo['start_time']);
    $this->endTime = intval($userInfo['end_time']);
  }
  public function startTime() {
    return $this->returnInfo("startTime");
  }
  public function endTime() {
    return $this->returnInfo("endTime");
  }
  public function getEntries() {
    // retrieves a list of arrays corresponding to anime list entries belonging to this user.
    $returnList = [];
    $entries = $this->dbConn->table(static::$MODEL_TABLE)->where(['user_id' => $this->user_id])->order('time DESC')->query();
    $entryCount = $this->entryAvg = $this->entryStdDev = $entrySum = 0;
    $entryType = $this->listType."Entry";
    while ($entry = $entries->fetch()) {
      $entry['list'] = $this;
      $returnList[intval($entry['id'])] = new $entryType($this->app, intval($entry['id']), $entry);
      $entrySum += intval($entry['score']);
      $entryCount++;
    }
    $this->entryAvg = ($entryCount === 0) ? 0 : $entrySum / $entryCount;
    $entrySum = 0;
    if ($entryCount > 1) {
      foreach ($returnList as $entry) {
        $entrySum += pow(intval($entry->score) - $this->entryAvg, 2);
      }
      $this->entryStdDev = pow($entrySum / ($entryCount - 1), 0.5);
    }
    return $returnList;
  }
  public function getUniqueList() {
    // retrieves a list of $this->typeID, time, status, score, $this->partName arrays corresponding to the latest list entry for each thing the user has consumed.
    $listQuery = $this->dbConn->raw("SELECT `".static::$MODEL_TABLE."`.`id`, `".$this->typeID."`, `time`, `score`, `status`, `".$this->partName."` FROM (
                                        SELECT MAX(`id`) AS `id` FROM `".static::$MODEL_TABLE."`
                                        WHERE `user_id` = ".intval($this->user_id)."
                                        GROUP BY `".$this->typeID."`
                                      ) `p` INNER JOIN `".static::$MODEL_TABLE."` ON `".static::$MODEL_TABLE."`.`id` = `p`.`id`
                                      WHERE `status` != 0
                                      ORDER BY `status` ASC, `score` DESC");
    $this->uniqueListAvg = $this->uniqueListStdDev = $uniqueListSum = $uniqueListCount = $uniqueListStdDev = 0;
    $returnList = [];
    while ($row = $listQuery->fetch()) {
      if ($row['score'] != 0) {
        $uniqueListCount++;
        $uniqueListSum += intval($row['score']);
        $uniqueListStdDev += pow(intval($row['score']) - $this->uniqueListAvg, 2);
      }
      $returnList[$row[$this->typeID]] = [
        $this->listTypeLower => new $this->listType($this->app, intval($row[$this->typeID])),
        'id' => $row['id'],
        'time' => $row['time'],
        'score' => $row['score'],
        'status' => $row['status'],
        $this->partName => $row[$this->partName]
      ];
    }
    $this->uniqueListAvg = ($uniqueListCount === 0) ? 0 : $uniqueListSum / $uniqueListCount;
    $this->uniqueListStdDev = ($uniqueListCount === 0) ? 0 : pow($uniqueListStdDev / ($uniqueListCount - 1), 0.5);
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
      return (($status !== Null && intval($value['status']) === $status) || ($score !== Null && intval($value['score']) === $score));
    });
  }
  public function prevEntry($id, DateTime $beforeTime) {
    // Returns the previous entry in this user's entry list for $this->typeID and before $beforeTime.
    $entryType = $this->listType."Entry";
    $prevEntry = new $entryType($this->app, 0);

    foreach ($this->entries()->entries() as $entry) {
      if ($entry->time >= $beforeTime) {
        continue;
      }
      if ($entry->{$this->listTypeLower}->id == $id) {
        return $entry;
      }
    }
    return $prevEntry;
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
    return "/".escape_output(static::$MODEL_TABLE)."/".($action !== "index" ? intval($id)."/".escape_output($action)."/" : "").($format !== Null ? ".".escape_output($format) : "").($params !== Null ? "?".$urlParams : "");
  }
  public function link($action="show", $text=Null, $format=Null, $raw=False, array $params=Null, array $urlParams=Null, $id=Null) {
    // returns an HTML link to the current anime list, with action and text provided.
    $text = ($text === Null) ? "List" : $text;
    return parent::link($action, $text, $format, $raw, $params, $urlParams, $id);
  }
  public function similarity(BaseList $currentList, $minAnime=10) {
    // calculates pearson's r between this list and the current user's list.
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
  public function compatibilityBar(BaseList $currentList) {
    // returns markup for a compatibility bar between this list and the current user's list.
    $compatibility = $this->compatibility($currentList);
    if ($compatibility === False) {
      return "<div class='progress progress-info'><div class='bar' style='width: 0%'></div>Unknown</div>";
    }
    if ($compatibility >= 75) {
      $barClass = "danger";
    } elseif ($compatibility >= 50) {
      $barClass = "warning";
    } elseif ($compatibility >= 25) {
      $barClass = "success";
    } else {
      $barClass = "info";
    }
    return "<div class='progress progress-".$barClass."'><div class='bar' style='width: ".round($compatibility)."%'>".round($compatibility)."%</div></div>";
  }
}
?>