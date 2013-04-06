<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "averageRatingChart_div";
  $params['intervals'] = (intval($params['intervals']) > 0) ? intval($params['intervals']) : 12;

  // first, get time range of this user's anime completions.
  $startAndEndTimes = $this->app->dbConn->queryFirstRow("SELECT UNIX_TIMESTAMP(MIN(`time`)) AS `startTime`, UNIX_TIMESTAMP(MAX(`time`)) AS `endTime` FROM `anime_lists` WHERE (`user_id` = ".intval($this->id)." && `score` != 0)");
  if (!$startAndEndTimes) {
    return;
  }
  $startTime = $startAndEndTimes['startTime'];
  $endTime = $startAndEndTimes['endTime'];
  $groupBySeconds = ceil(($endTime - $startTime)/$params['intervals']);
  if ($groupBySeconds > 2592000) {
    $dateFormatString = 'n/y';
  } else {
    $dateFormatString = 'n/j/y';
  }
  
  if ($groupBySeconds < 86400) {
    $groupBySeconds = 86400;
  }
  echo "      <div class='fullwidth' id=".escape_output($params['chartDivID']).">
        <div class='timeline'>
          <header>
          <h3>Average rating over time</h3>
          </header>
          <ul>\n";

  $userAnimeTimeline = $this->app->dbConn->stdQuery("SELECT `anime_id`, UNIX_TIMESTAMP(`time`) AS `time`, `score` FROM `anime_lists`
                      WHERE (`user_id` = ".intval($this->id)." && `score` != 0)
                      ORDER BY `time` ASC");
  $currTime = $startTime;
  $animeRatings = array();
  $ratingSum = 0;
  $ratingNum = 0;
  $maxRating = 0;
  $maxRatingTime = 0;
  while ($timelinePoint = $userAnimeTimeline->fetch_assoc()) {
    if ($timelinePoint['time'] > $currTime + $groupBySeconds) {
      //done with this chunk. output its stats and continue.
      echo "            <li>".date($dateFormatString, $currTime).": ".round($ratingSum/$ratingNum, 2)."</li>\n";
      $currTime += $groupBySeconds;
      while ($timelinePoint['time'] > $currTime + $groupBySeconds) {
        echo "            <li>".date($dateFormatString, $currTime).": ".round($ratingSum/$ratingNum, 2)."</li>\n";
        $currTime += $groupBySeconds;
      }
    }
    if (isset($animeRatings[$timelinePoint['anime_id']])) {
      $ratingSum += ($timelinePoint['score'] - $animeRatings[$timelinePoint['anime_id']]);
    } else {
      $ratingSum += $timelinePoint['score'];
      $ratingNum++;
    }
    $animeRatings[$timelinePoint['anime_id']] = $timelinePoint['score'];
    
    if ($ratingSum/$ratingNum > $maxRating) {
      $maxRating = $ratingSum/$ratingNum;
      $maxRatingTime = date($dateFormatString, $timelinePoint['time']);
    }
  }
  if ($currTime > microtime(true)) {
    $currTime = microtime(true);
  }
  echo "            <li>".date($dateFormatString, $currTime).": ".round($ratingSum/$ratingNum, 2)."</li>
          </ul>
        </div>
      </div>\n";

?>