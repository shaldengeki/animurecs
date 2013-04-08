<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "animeRatingDist_div";
  $params['title'] = isset($params['title']) ? $params['title'] : "Rating Distribution";
  $params['barLabelWidth'] = 30;
  $params['maxBarWidth'] = 200;

  $ratingCounts = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0];
  foreach ($this->animeList()->uniqueList() as $entry) {
    $ratingCounts[intval($entry['score'])]++;
  }
  $finalDist = [];
  foreach ($ratingCounts as $rating=>$count) {
    if ($rating != 0) {
      $finalDist[$rating] = intval($count);
    }
  }
  $params['data'] = $finalDist;
  echo $this->app->view('histogram', $params);
?>