<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }
  // displays the rating timeline for a user or anime over the history of that user or anime's ratings.
  // MUST provide object ID.
  if (!isset($params['id'])) {
    exit;
  }

  // table column for current object's ID.
  $params['idField'] = isset($params['idField']) ? $params['idField'] : "anime_id";

  // table column for each unique rating's ID (i.e. for an anime's avg score, would be user_id)
  $params['uniqueIDField'] = isset($params['uniqueIDField']) ? $params['uniqueIDField'] : "user_id";

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "averageRatingChart_div";
  $params['intervals'] = (isset($params['intervals']) && intval($params['intervals']) > 0) ? intval($params['intervals']) : 12;

  $params['start'] = (isset($params['start']) && intval($params['start']) > 0) ? intval($params['start']) : 0;
  $params['end'] = (isset($params['end']) && intval($params['end']) > 0) ? intval($params['end']) : microtime(true);

  // optional transform to calculate rating metric. default is arithmetic mean.
  $params['title'] = (isset($params['title'])) ? $params['title'] : "Average rating over time";
  $params['metric'] = (isset($params['metric'])) ? $params['metric'] : "array_mean";

  // calculate interval size and set output format based on the interval size.
  $groupBySeconds = ceil(($params['end'] - $params['start'] * 1.0)/$params['intervals']);
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
          <h3>".escape_output($params['title'])."</h3>
          </header>
          <ul>\n";

  $timeline = $this->dbConn->stdQuery("SELECT `".$params['uniqueIDField']."`, UNIX_TIMESTAMP(`time`) AS `time`, `score` FROM `anime_lists`
                      WHERE (`".$params['idField']."` = ".intval($params['id'])." && `score` != 0 && `status` != 0)
                      ORDER BY `time` ASC");
  $currTime = $params['start'];
  $ratings = array();
  $ratingSum = 0;
  $ratingNum = 0;
  $maxRating = 0;
  $maxRatingTime = 0;
  while ($timelinePoint = $timeline->fetch_assoc()) {
    if ($timelinePoint['time'] > $currTime + $groupBySeconds) {
      //done with this chunk. output its stats and continue.
      $currentAvg = round($params['metric']($ratings), 2);
      echo "            <li>".date($dateFormatString, $currTime).": ".$currentAvg."</li>\n";
      $currTime += $groupBySeconds;
      while ($timelinePoint['time'] > $currTime + $groupBySeconds) {
        echo "            <li>".date($dateFormatString, $currTime).": ".$currentAvg."</li>\n";
        $currTime += $groupBySeconds;
      }
    }
    if (isset($ratings[$timelinePoint[$params['uniqueIDField']]])) {
      $ratingSum += ($timelinePoint['score'] - $ratings[$timelinePoint[$params['uniqueIDField']]]);
    } else {
      $ratingSum += $timelinePoint['score'];
      $ratingNum++;
    }
    $currentAvg = round($params['metric']($ratings), 2);
    $ratings[$timelinePoint[$params['uniqueIDField']]] = $timelinePoint['score'];
    if ($ratingNum > 0 && $ratingSum/$ratingNum > $maxRating) {
      $maxRating = $ratingSum/$ratingNum;
      $maxRatingTime = date($dateFormatString, $timelinePoint['time']);
    }
  }
  if ($currTime > microtime(true)) {
    $currTime = microtime(true);
  }
  $currentAvg = round($params['metric']($ratings), 2);
  echo "            <li>".date($dateFormatString, $currTime).": ".$currentAvg."</li>
          </ul>
        </div>
      </div>\n";

?>