<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "timelineChart_div";
  $params['intervals'] = (intval($params['intervals']) > 0) ? intval($params['intervals']) : 12;
  $params['title'] = isset($params['title']) ? $params['title'] : "Timeline";
  $params['data'] = isset($params['data']) && is_array($params['data']) ? $params['data'] : [];
?>
      <div class='fullwidth' id="<?php echo escape_output($params['chartDivID']); ?>">
        <div class='timeline'>
          <div class='page-header'>
            <h3><?php echo escape_output($params['title']); ?></h3>
          </div>
          <ul>
<?php
  foreach ($params['data'] as $timePoint) {
?>
            <li><?php echo implode(",", $timePoint); ?></li>
<?php
  }
?>
          </ul>
        </div>
      </div>