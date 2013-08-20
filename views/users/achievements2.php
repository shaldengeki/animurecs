<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<div class='achievements'>
<?php
  $noAchieves = True;
  foreach ($this->app->achievements as $id=>$achievement) {
    if ($achievement->alreadyAwarded($this)) {
      $noAchieves = False;
?>
  <div class='row'>
    <div class='col-md-8'>
      <h4><?php echo escape_output($achievement->name); ?><?php echo $achievement->children ? " (".intval($achievement->level).")" : "";?></h4>
      <p><?php echo $achievement->description(); ?></p>
<?php
      if ($achievement->children) {
        $child = $achievement->children[0];
        if (!$child->alreadyAwarded($this)) {
          $barClass = "bar";
          if ($child->progress($this) > 0.75) {
            $barClass .= " bar-danger";
          } elseif ($child->progress($this) > 0.5) {
            $barClass .= " bar-warning";
          }
?>
      <p><em>Progress to next achievement (<?php echo escape_output($child->name); ?>):</em></p>
      <div class="progress">
        <div class="<?php echo $barClass; ?>" style="width: <?php echo round($child->progress($this) * 100, 2); ?>%;"><?php echo $child->progressString($this); ?></div>
      </div>
<?php
        }
      }
?>
    </div>
    <div class='col-md-4'>
      <?php echo $achievement->imageTag(); ?>
    </div>
  </div>
<?php
    }
  }
  if ($noAchieves) {
?>
  <blockquote>No achievements yet! Try adding some anime to your list, or filling out your profile!</blockquote>
<?php
  }
?>
</ul>