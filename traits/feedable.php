<?php

trait Feedable {
  // allows an object to assemble and display a formatted feed of entries belonging to this object.

  // any feedable class must define a way to retrieve entries (from the database, presumably)
  abstract protected function getEntries();
  
  public function entries(DateTime $minTime=Null, DateTime $maxTime=Null, $limit=Null) {
    // returns a list of feed entries, up to $maxTime and with at most $limit entries.
    // feed entries contain at a minimum an object, time and user field.

    if ($this->entries === Null) {
      $this->entries = $this->getEntries();
    }
    if ($minTime !== Null || $maxTime !== Null || $limit !== Null) {
      // Returns a list of up to $limit entries up to $maxTime.
      $maxTime = $maxTime === Null ? new DateTime("now", $this->app->outputTimeZone) : $maxTime;
      $minTime = $minTime === Null ? new DateTime("@0", $this->app->outputTimeZone) : $minTime;

      // loop through all of this feedable's entries until we reach the end or our limit.
      $returnList = [];
      $entryCount = 0;
      foreach ($this->entries()->entries() as $entry) {
        if ($entry->time() >= $maxTime || $entry->time() <= $minTime) {
          continue;
        }
        $returnList[] = $entry;
        $entryCount++;
        if ($limit !== Null && $entryCount >= $limit) {
          return new EntryGroup($this->app, $returnList);
        }
      }
      return new EntryGroup($this->app, $returnList);
    } else {
      if ($this->entries instanceof EntryGroup) {
        return $this->entries;
      } else {
        return new EntryGroup($this->app, $this->entries);
      }
    }
  }

  public function feed(EntryGroup $entries, $numEntries=50, $emptyFeedText="") {
    // takes a list of entries (given by entries()) and returns markup for the resultant feed.

    // sort by key and grab only the latest numEntries.
    $entries = array_sort_by_method($entries->load('comments')->entries(), 'time', [], 'desc');
    $entries = array_slice($entries, 0, $numEntries);
    if (!$entries) {
      $output .= $emptyFeedText;
    } else {
      // now pull info en masse for these entries.
      $entryGroup = new EntryGroup($this->app, $entries);

      $output = "<ul class='media-list ajaxFeed' data-url='".$this->url("feed")."'>\n";
      $feedOutput = [];
      foreach ($entryGroup->load('info')->load('users')->load('anime')->load('comments')->entries() as $entry) {
        $feedOutput[] = $this->app->user->view('feedEntry', ['entry' => $entry]);
      }
      $output .= implode("\n", $feedOutput);
      $output .= "</ul>\n";
    }
    return $output;
  }
}

?>