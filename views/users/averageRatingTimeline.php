<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "averageRatingChart_div";
  $params['intervals'] = (intval($params['intervals']) > 0) ? intval($params['intervals']) : 12;

  // first, get time range of this user's anime completions.
  $startAndEndTimes = $this->app->dbConn->table(AnimeList::$MODEL_TABLE)->fields("UNIX_TIMESTAMP(MIN(time)) AS startTime", "UNIX_TIMESTAMP(MAX(time)) AS endTime")->where(['user_id' => $this->id, 'score != 0'])->firstRow();
  if (!$startAndEndTimes) {
    exit;
  }
  $startTime = $startAndEndTimes['startTime'];
  $endTime = $startAndEndTimes['endTime'];

  echo $this->app->view('ratingTimeline', [
    'id' => $this->id,
    'idField' => 'user_id',
    'uniqueIDField' => 'anime_id',
    'chartDivID' => $params['chartDivID'],
    'intervals' => $params['intervals'],
    'start' => $startTime,
    'end' => $endTime
  ]);

?>