<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "animeStatusDist_div";
  $params['title'] = isset($params['title']) ? $params['title'] : "Status Distribution";
  $params['entries'] = isset($params['entries']) ? $params['entries'] : $this->latestEntries();
  $params['data'] = isset($params['data']) ? $params['data'] : [];

  if (!$params['data']) {
    $statuses = statusArray();
    $statusCounts = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
    foreach ($params['entries']->objects() as $entry) {
      $statusCounts[intval($entry->status)]++;
    }
    foreach ($statusCounts as $status=>$count) {
      if ($status != 0 && isset($statuses[$status])) {
        $params['data'][$statuses[$status]] = intval($count);
      }
    }
  }
  echo $this->app->view('histogram', $params);
?>