<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "animeStatusDist_div";
  $params['title'] = isset($params['title']) ? $params['title'] : "Status Distribution";

  $statuses = statusArray();
  $statusCounts = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
  foreach ($this->animeList()->uniqueList() as $entry) {
    $statusCounts[intval($entry['status'])]++;
  }
  $finalHist = [];
  foreach ($statusCounts as $status=>$count) {
    if ($status != 0 && isset($statuses[$status])) {
      $finalHist[$statuses[$status]] = intval($count);
    }
  }
  $params['data'] = $finalHist;
  echo $this->app->view('histogram', $params);
?>