<?php
class BaseList extends BaseObject {
  // base list from which anime and manga lists inherit methods and properties.
  use Feedable;

  public $user_id;
  protected $user;

  protected $startTime, $endTime;
  protected $uniqueListAvg, $uniqueListStdDev, $entryAvg, $entryStdDev;
  protected $statusStrings, $scoreStrings, $partStrings;

  protected $entries, $uniqueList;

  protected $partName, $modelTable, $modelPlural, $listType, $listTypeLower, $typeVerb, $typeID, $feedType;

  public function __construct(DbConn $database, $user_id=Null) {
    parent::__construct($database, $user_id);
    $this->modelTable = "";
    $this->modelPlural = "";
    $this->partName = "";
    $this->listType = "";
    $this->typeVerb = "";
    $this->listTypeLower = strtolower($this->listType);
    $this->feedType = "";
    $this->typeID = $this->listTypeLower.'_id';
    // strings with which to build feed messages.
    // the status messages we build will be different depending on 1) whether or not this is the first entry, and 2) what the status actually is.
    $this->statusStrings = array(0 => array(0 => "did something mysterious with [TITLE]",
                                      1 => "is now [TYPE_VERB] [TITLE]",
                                      2 => "marked [TITLE] as completed",
                                      3 => "marked [TITLE] as on-hold",
                                      4 => "marked [TITLE] as dropped",
                                      6 => "plans to watch [TITLE]"),
                                  1 => array(0 => "removed [TITLE]",
                                            1 => "started [TYPE_VERB] [TITLE]",
                                            2 => "finished [TITLE]",
                                            3 => "put [TITLE] on hold",
                                            4 => "dropped [TITLE]",
                                            6 => "now plans to watch [TITLE]"));
    $this->scoreStrings = array(0 => array("rated [TITLE] a [SCORE]/10", "and rated it a [SCORE]/10"),
                          1 => array("unrated [TITLE]", "and unrated it"));
    $this->partStrings = array("just finished [PART_NAME] [PART]/[TOTAL_PARTS] of [TITLE]", "and finished [PART_NAME] [PART]/[TOTAL_PARTS]");
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
  public function create_or_update(array $entry, User $currentUser=Null) {
    /*
      Creates or updates an existing list entry for the current user.
      Takes an array of entry parameters.
      Returns the resultant list entry ID.
    */
    // ensure that this user and list type exist.
    try {
      $user = new User($this->dbConn, intval($entry['user_id']));
      $type = new $this->listType($this->dbConn, intval($entry[$this->typeID]));
    } catch (Exception $e) {
      return False;
    }
    $params = [];
    foreach ($entry as $parameter => $value) {
      if (!is_array($value)) {
        if (is_numeric($value)) {
            $params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".intval($value);
        } else {
          $params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
        }
      }
    }

    // check to see if this is an update.
    if (isset($this->entries()[intval($entry['id'])])) {
      $updateDependency = $this->dbConn->stdQuery("UPDATE `".$this->modelTable."` SET ".implode(", ", $params)." WHERE `id` = ".intval($entry['id'])." LIMIT 1");
      if (!$updateDependency) {
        return False;
      }
      // update list locally.
      if ($this->uniqueList()[intval($entry[$this->typeID])]['score'] != intval($entry['score']) || $this->uniqueList()[intval($entry[$this->typeID])]['status'] != intval($entry['status']) || $this->uniqueList()[intval($entry[$this->typeID])][$this->partName] != intval($entry[$this->partName])) {
        if (intval($entry['status']) == 0) {
          unset($this->uniqueList[intval($entry[$this->typeID])]);
        } else {
          $this->uniqueList[intval($entry[$this->typeID])] = array($this->typeID => intval($entry[$this->typeID]), 'time' => $entry['time'], 'score' => intval($entry['score']), 'status' => intval($entry['status']), $this->partName => intval($entry[$this->partName]));
        }
      }
      $returnValue = intval($entry['id']);
    } else {
      $timeString = (isset($entry['time']) ? "" : ", `time` = NOW()");
      $insertDependency = $this->dbConn->stdQuery("INSERT INTO `".$this->modelTable."` SET ".implode(",", $params).$timeString);
      if (!$insertDependency) {
        return False;
      }
      $returnValue = intval($this->dbConn->insert_id);
      // insert list locally.
      $this->uniqueList();
      if (intval($entry['status']) == 0) {
        unset($this->uniqueList[intval($entry[$this->typeID])]);
      } else {
        $this->uniqueList[intval($entry[$this->typeID])] = array($this->typeID => intval($entry[$this->typeID]), 'time' => $entry['time'], 'score' => intval($entry['score']), 'status' => intval($entry['status']), $this->partName => intval($entry[$this->partName]));
      }
    }
    $this->entries[intval($returnValue)] = $entry;
    return $returnValue;
  }
  public function delete(array $entries=Null) {
    /*
      Deletes list entries.
      Takes an array of entry ids as input, defaulting to all entries.
      Returns a boolean.
    */
    if ($entries === Null) {
      $entries = array_keys($this->entries());
    }
    if (is_numeric($entries)) {
      $entries = [intval($entries)];
    }
    $entryIDs = [];
    foreach ($entries as $entry) {
      if (is_numeric($entry)) {
        $entryIDs[] = intval($entry);
      }
    }
    if ($entryIDs) {
      $drop_entries = $this->dbConn->stdQuery("DELETE FROM `".$this->modelTable."` WHERE `user_id` = ".intval($this->user_id)." AND `id` IN (".implode(",", $entryIDs).") LIMIT ".count($entryIDs));
      if (!$drop_entries) {
        return False;
      }
    }
    foreach ($entryIDs as $entryID) {
      unset($this->entries[intval($entryID)]);
    }
    return True;
  }
  public function user() {
    if ($this->user === Null) {
      $this->user = new User($this->dbConn, $this->user_id);
    }
    return $this->user;
  }
  public function getInfo() {
    $userInfo = $this->dbConn->queryFirstRow("SELECT `user_id`, MIN(`time`) AS `start_time`, MAX(`time`) AS `end_time` FROM `".$this->modelTable."` WHERE `user_id` = ".intval($this->user_id));
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
    $serverTimezone = new DateTimeZone(SERVER_TIMEZONE);
    $returnList = [];
    $entries = $this->dbConn->stdQuery("SELECT * FROM `".$this->modelTable."` WHERE `user_id` = ".intval($this->user_id)." ORDER BY `time` DESC");
    $entryCount = $this->entryAvg = $this->entryStdDev = $entrySum = 0;
    $entryType = $this->listType."Entry";
    while ($entry = $entries->fetch_assoc()) {
      $returnList[intval($entry['id'])] = new $entryType($this->dbConn, intval($entry['id']), $entry);
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
    $returnList = $this->dbConn->queryAssoc("SELECT `".$this->modelTable."`.`id`, `".$this->typeID."`, `time`, `score`, `status`, `".$this->partName."` FROM (
                                              SELECT MAX(`id`) AS `id` FROM `".$this->modelTable."`
                                              WHERE `user_id` = ".intval($this->user_id)."
                                              GROUP BY `".$this->typeID."`
                                            ) `p` INNER JOIN `".$this->modelTable."` ON `".$this->modelTable."`.`id` = `p`.`id`
                                            WHERE `status` != 0
                                            ORDER BY `status` ASC, `score` DESC", $this->typeID);

    $this->uniqueListAvg = $this->uniqueListStdDev = $uniqueListSum = $uniqueListCount = 0;
    foreach ($returnList as $key=>$entry) {
      $returnList[$key][$this->listTypeLower] = new $this->listType($this->dbConn, intval($entry[$this->typeID]));
      unset($returnList[$key][$this->typeID]);
      if ($entry['score'] != 0) {
        $uniqueListCount++;
        $uniqueListSum += intval($entry['score']);
      }
    }
    $this->uniqueListAvg = ($uniqueListCount === 0) ? 0 : $uniqueListSum / $uniqueListCount;
    $uniqueListSum = 0;
    if ($uniqueListCount > 1) {
      foreach ($returnList as $entry) {
        if ($entry['score'] != 0) {
          $uniqueListSum += pow(intval($entry['score']) - $this->uniqueListAvg, 2);
        }
      }
      $this->uniqueListStdDev = pow($uniqueListSum / ($uniqueListCount - 1), 0.5);
    }
    return $returnList;
  }
  public function uniqueList() {
    if ($this->uniqueList === Null) {
      $this->uniqueList = $this->getUniqueList();
    }
    return $this->uniqueList;
  }
  public function uniqueListStdDev() {
    if ($this->uniqueListStdDev === Null) {
      $this->uniqueList();
    }
    return $this->uniqueListStdDev;
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
    $prevEntry = new $entryType($this->dbConn, 0);

    foreach ($this->entries() as $entry) {
      if ($entry->time >= $beforeTime) {
        continue;
      }
      if ($entry->{$this->listTypeLower}->id == $id) {
        return $entry;
      }
    }
    return $prevEntry;
  }
  public function formatFeedEntry(BaseEntry $entry, User $currentUser) {
    // fetch the previous feed entry and compare values against current entry.

    $outputTimezone = new DateTimeZone(OUTPUT_TIMEZONE);
    $serverTimezone = new DateTimeZone(SERVER_TIMEZONE);
    $nowTime = new DateTime("now", $outputTimezone);

    $diffInterval = $nowTime->diff($entry->time);
    $prevEntry = $this->prevEntry($entry->{$this->listTypeLower}->id, $entry->time);

    $statusChanged = (bool) ($entry->status != $prevEntry->status);
    $scoreChanged = (bool) ($entry->score != $prevEntry->score);
    $partChanged = (bool) ($entry->{$this->partName} != $prevEntry->{$this->partName});
    
    // concatenate appropriate parts of this status text.
    $statusTexts = [];
    if ($statusChanged) {
      $statusTexts[] = $this->statusStrings[intval((bool)$prevEntry)][intval($entry->status)];
    }
    if ($scoreChanged) {
      $statusTexts[] = $this->scoreStrings[intval($entry->score == 0)][intval($statusChanged)];
    }
    if ($partChanged && ($entry->{$this->partName} != $entry->{$this->listTypeLower}->{$this->partName."Count"} || $entry->status != 2)) {
      $statusTexts[] = $this->partStrings[intval($statusChanged || $scoreChanged)];
    }
    $statusText = implode(" ", $statusTexts);

    // replace placeholders.
    $statusText = str_replace("[TYPE_VERB]", $this->typeVerb, $statusText);
    $statusText = str_replace("[PART_NAME]", $this->partName, $statusText);
    $statusText = str_replace("[TITLE]", $entry->{$this->listTypeLower}->link("show", $entry->{$this->listTypeLower}->title), $statusText);
    $statusText = str_replace("[SCORE]", $entry->score, $statusText);
    $statusText = str_replace("[PART]", $entry->{$this->partName}, $statusText);
    $statusText = str_replace("[TOTAL_PARTS]", $entry->{$this->listTypeLower}->{$this->partName."Count"}, $statusText);
    $statusText = ucfirst($statusText).".";

    return array('title' => $entry->user->link("show", $entry->user->username), 'text' => $statusText);
  }
  public function link($action="show", $text=Null, $raw=False, array $params=Null, array $urlParams=Null, $id=Null) {
    // returns an HTML link to the current anime list, with action and text provided.
    $text = ($text === Null) ? "List" : $text;
    return parent::link($action, $text, $raw, $params, $urlParams, $id);
  }
  public function similarity(BaseList $currentList) {
    // calculates pearson's r between this list and the current user's list.
    if ($this->uniqueListStdDev() == 0 || $currentList->uniqueListStdDev() == 0) {
      return False;
    }
    $similaritySum = $similarityCount = 0;
    foreach($this->uniqueList() as $entryID=>$entry) {
      if (intval($entry['score']) != 0 && isset($currentList->uniqueList()[$entryID]) && intval($currentList->uniqueList()[$entryID]['score']) != 0) {
        $similaritySum += (intval($entry['score']) - $this->uniqueListAvg) * (intval($currentList->uniqueList()[$entryID]['score']) - $currentList->uniqueListAvg);
        $similarityCount++;
      }
    }
    if ($similarityCount < 10) {
      return False;
    }
    return $similaritySum / ($this->uniqueListStdDev() * $currentList->uniqueListStdDev() * ($similarityCount - 1));
  }
  public function compatibilityBar(BaseList $currentList) {
    // returns markup for a compatibility bar between this list and the current user's list.
    $compatibility = $this->similarity($currentList);
    if ($compatibility === False) {
      return "<div class='progress progress-info'><div class='bar' style='width: 0%'></div>Unknown</div>";
    }
    $compatibility = 100 * (1 + $compatibility) / 2.0;
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