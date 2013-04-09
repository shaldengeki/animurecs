<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "animeStatusDist_div";
  $params['title'] = "Status Distribution";

  $statuses = statusArray();
  $statusCounts = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
  foreach ($this->latestEntries()->objects() as $entry) {
    $statusCounts[intval($entry->status)]++;
  }
  $params['data'] = [];
  foreach ($statusCounts as $status=>$count) {
    if ($status != 0 && isset($statuses[$status])) {
      $params['data'][$statuses[$status]] = intval($count);
    }
  }
  echo $this->app->view('histogram', $params);
?>