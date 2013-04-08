<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "animeCompletionChart_div";
  $params['intervals'] = (intval($params['intervals']) > 0) ? intval($params['intervals']) : 12;
  $params['title'] = isset($params['title']) ? $params['title'] : "Anime completion over time";

  // first, get time range of this user's anime completions.
  $times = $this->app->dbConn->queryFirstRow("SELECT UNIX_TIMESTAMP(MIN(`time`)) AS `min`, UNIX_TIMESTAMP(MAX(`time`)) AS `max` FROM `anime_lists` WHERE (`user_id` = ".intval($this->id)." && `status` = 2)");
  $groupBySeconds = ceil(($times['max'] - $times['min'])/$params['intervals']);
  if ($groupBySeconds < 86400) {
    $groupBySeconds = 86400;
  }
  if ($groupBySeconds > 2592000) {
    $dateFormatString = 'n/y';
  } else {
    $dateFormatString = 'n/j/y';
  }

  // now bin this user's completions into intervals and output markup for these counts.
  $userAnimeTimeline = $this->app->dbConn->queryAssoc("SELECT ROUND(UNIX_TIMESTAMP(`time`)/".$groupBySeconds.")*".$groupBySeconds." AS `groupedTime`, COUNT(*) AS `count` FROM `anime_lists` 
                                              WHERE (`user_id` = ".intval($this->id)." && `status` = 2) 
                                              GROUP BY `groupedTime` 
                                              ORDER BY `groupedTime` ASC");
  $lastTime = $userAnimeTimeline[0]['groupedTime'];
  $maxAnime = 0;
  $maxAnimeTime = 0;
  $finishedTimeline = [];
  foreach ($userAnimeTimeline as $timePoint) {
    if ($timePoint['count'] > $maxAnime) {
      $maxAnime = $timePoint['count'];
      $maxAnimeTime = date($dateFormatString, $timePoint['groupedTime']);
    }
    if ($timePoint['groupedTime'] - $lastTime > $groupBySeconds) {
      while ($lastTime < $timePoint['groupedTime'] - $groupBySeconds) {
        $lastTime += $groupBySeconds;
        $finishedTimeline[] = [date($dateFormatString, $lastTime), 0];
      }
    }
    $finishedTimeline[] = [date($dateFormatString, $timePoint['groupedTime']), intval($timePoint['count'])];
    $lastTime = $timePoint['groupedTime'];
  }
  $params['data'] = $finishedTimeline;
  echo $this->app->view('timeline', $params);
?>