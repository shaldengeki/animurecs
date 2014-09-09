<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "wilsonScoreChart_div";
  $params['intervals'] = (intval($params['intervals']) > 0) ? intval($params['intervals']) : 12;
  $params['start'] = isset($params['start']) ? $params['start'] : 0;
  $params['end'] = isset($params['end']) ? $params['end'] : time();

  // displays a graph of an anime's wilson score over time.
  echo $this->app->view('ratingTimeline', [
    'id' => $this->id,
    'idField' => 'anime_id',
    'uniqueIDField' => 'user_id',
    'chartDivID' => $params['chartDivID'],
    'intervals' => $params['intervals'],
    'start' => $params['start'],
    'end' => $params['end'],
    'title' => 'Wilson score over time',
    'metric' => 'wilson_score'
  ]);

?>