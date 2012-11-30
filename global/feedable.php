<?php

trait Feedable {
  // allows an object to assemble and display a formatted feed of entries belonging to this object.

  // any feedable class must define a way to retrieve entries (from the database, presumably)
  abstract protected function getEntries();
  public function entries(DateTime $maxTime=Null, $limit=Null) {
    // returns a list of feed entries, up to $maxTime and with at most $limit entries.
    // feed entries contain at a minimum an object, time and user field.

    if ($this->entries === Null) {
      $this->entries = $this->getEntries();
    }
    if ($maxTime !== Null || $limit !== Null) {
      // Returns a list of up to $limit entries up to $maxTime.
      $serverTimezone = new DateTimeZone(SERVER_TIMEZONE);
      $outputTimezone = new DateTimeZone(OUTPUT_TIMEZONE);
      if ($maxTime === Null) {
        $nowTime = new DateTime();
        $nowTime->setTimezone($outputTimezone);
        $maxTime = $nowTime;
      }
      $returnList = [];
      $entryCount = 0;
      foreach ($this->entries() as $entry) {
        if ($entry->time() >= $maxTime) {
          continue;
        }
        $returnList[] = $entry;
        $entryCount++;
        if ($limit !== Null && $entryCount >= $limit) {
          return $returnList;
        }
      }
      return $returnList;
    } else {
      return $this->entries;
    }
  }

  public function feedEntry(BaseEntry $entry, User $currentUser) {
    // takes a feed entry from the current object and outputs feed markup for this feed entry.
    $outputTimezone = new DateTimeZone(OUTPUT_TIMEZONE);
    $serverTimezone = new DateTimeZone(SERVER_TIMEZONE);
    $nowTime = new DateTime("now", $outputTimezone);
    $diffInterval = $nowTime->diff($entry->time);

    $feedMessage = $entry->formatFeedEntry($currentUser);

    $output = "  <li class='feedEntry row-fluid'>
        <div class='feedDate' data-time='".$entry->time->format('U')."'>".ago($diffInterval)."</div>
        <div class='feedAvatar'>".$entry->user->link("show", "<img class='feedAvatarImg' src='".joinPaths(ROOT_URL, escape_output($entry->user->avatarPath))."' />", True)."</div>
        <div class='feedText'>
          <div class='feedUser'>".$feedMessage['title']."</div>
          ".$feedMessage['text']."\n";
    if ($entry->allow($currentUser, 'delete')) {
      $output .= "            <ul class='feedEntryMenu hidden'><li>".$entry->link("delete", "<i class='icon-trash'></i> Delete", True)."</li></ul>";
    }
    if ($entry->comments) {
      $output .= "<ul class='userFeed'>";
      foreach ($entry->comments as $comment) {
        $commentEntry = new CommentEntry($this->dbConn, intval($comment->id));
        $output .= $this->feedEntry($commentEntry, $currentUser);
      }
      $output .= "</ul>";
    }
    $output .= "          </div>
      </li>\n";
    return $output;
  }

  public function feed(array $entries, User $currentUser, $numEntries=50, $emptyFeedText="") {
    // takes a list of entries (given by entries()) and returns markup for the resultant feed.

    // sort by key and grab only the latest numEntries.
    $entries = array_sort_by_property($entries, 'time', 'desc');
    $entries = array_slice($entries, 0, $numEntries);
    if (!$entries) {
      $output .= $emptyFeedText;
    } else {
      $output = "<ul class='userFeed ajaxFeed' data-url='".$this->url("feed")."'>\n";
      $feedOutput = [];
      foreach ($entries as $entry) {
        $feedOutput[] = $this->feedEntry($entry, $currentUser);
      }
      $output .= implode("\n", $feedOutput);
      $output .= "</ul>\n";
    }
    return $output;
  }
}

?>