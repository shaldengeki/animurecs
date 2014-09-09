<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "averageRatingChart_div";
  $params['intervals'] = (intval($params['intervals']) > 0) ? intval($params['intervals']) : 12;
  $params['start'] = isset($params['start']) ? $params['start'] : 0;
  $params['end'] = isset($params['end']) ? $params['end'] : time();
  $params['title'] = isset($params['title']) ? $params['title'] : "Average rating over time";

  echo $this->app->view('ratingTimeline', [
    'id' => $this->id,
    'idField' => 'user_id',
    'uniqueIDField' => 'anime_id',
    'chartDivID' => $params['chartDivID'],
    'intervals' => $params['intervals'],
    'start' => $params['start'],
    'end' => $params['end'],
    'title' => $params['title']
  ]);

?>