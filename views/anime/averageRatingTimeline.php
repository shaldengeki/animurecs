<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "averageRatingChart_div";
  $params['intervals'] = (intval($params['intervals']) > 0) ? intval($params['intervals']) : 12;

  // first, get time range of this anime's ratings.
  $times = $this->app->dbConn->firstRow("SELECT UNIX_TIMESTAMP(MIN(`time`)) AS `start`, UNIX_TIMESTAMP(MAX(`time`)) AS `end` FROM `anime_lists` WHERE (`anime_id` = ".intval($this->id)." && `score` != 0 && `status` != 0)");
  if ($startTime === False) {
    exit;
  }
  $startTime = intval($times['start']);
  $endTime = intval($times['end']);

  echo $this->app->view('ratingTimeline', [
    'id' => $this->id,
    'idField' => 'anime_id',
    'uniqueIDField' => 'user_id',
    'chartDivID' => $params['chartDivID'],
    'intervals' => $params['intervals'],
    'start' => $startTime,
    'end' => $endTime
  ]);
?>