<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }

  $params['chartDivID'] = isset($params['chartDivID']) ? $params['chartDivID'] : "histogram_div";
  $params['title'] = isset($params['title']) ? $params['title'] : "Distribution";
  $params['data'] = isset($params['data']) && is_array($params['data']) ? $params['data'] : [];

  // graph display parameters.
  // space reserved for value labels (right)
  $chart['data-valueLabelWidth'] = isset($params['valueLabelWidth']) && is_numeric($params['valueLabelWidth']) ? $params['valueLabelWidth'] : 40; 

  // height of one bar
  $chart['data-barHeight'] = isset($params['barHeight']) && is_numeric($params['barHeight']) ? $params['barHeight'] : 20; 

  // space reserved for bar labels
  $chart['data-barLabelWidth'] = isset($params['barLabelWidth']) && is_numeric($params['barLabelWidth']) ? $params['barLabelWidth'] : 130; 

  // padding between bar and bar labels (left)
  $chart['data-barLabelPadding'] = isset($params['barLabelPadding']) && is_numeric($params['barLabelPadding']) ? $params['barLabelPadding'] : 5; 

  // space reserved for gridline labels
  $chart['data-gridLabelHeight'] = isset($params['gridLabelHeight']) && is_numeric($params['gridLabelHeight']) ? $params['gridLabelHeight'] : 18; 

  // space between start of grid and first bar
  $chart['data-gridChartOffset'] = isset($params['gridChartOffset']) && is_numeric($params['gridChartOffset']) ? $params['gridChartOffset'] : 3; 

  // width of the bar with the max value
  $chart['data-maxBarWidth'] = isset($params['maxBarWidth']) && is_numeric($params['maxBarWidth']) ? $params['maxBarWidth'] : 275;

  // number of y-axis gridlines.
  $chart['data-ticks'] = isset($params['ticks']) && is_numeric($params['ticks']) ? $params['ticks'] : 5;


  $chartProperties = [];
  foreach ($chart as $attr=>$value) {
    $chartProperties[] = escape_output($attr)."='".escape_output($value)."'";
  }
?>
<div class='page-header'>
  <h3><?php echo escape_output($params['title']); ?></h3>
</div>
<div class='fullwidth' id="<?php echo escape_output($params['chartDivID']); ?>" <?php echo implode(" ", $chartProperties); ?>></div>
<script id="<?php echo escape_output($params['chartDivID']); ?>-csv" type="text/csv">Category,Value
<?php
foreach ($params['data'] as $category=>$value) {
  echo escape_output($category).",".escape_output($value)."\n";
}
?>
</script>
<script>renderHistogram('#<?php echo escape_output($params['chartDivID']); ?>');</script>