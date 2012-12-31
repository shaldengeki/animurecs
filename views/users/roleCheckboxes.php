<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  if (!isset($params['select_id'])) {
    $params['select_id'] = 'user[usermask][]';
  }
  if (!isset($params['selected'])) {
    if ($this->usermask()) {
      $params['selected'] = intval($this->usermask());
    } else {
      $params['selected'] = 0;
    }
  }
  for ($usermask = 0; $usermask <= 2; $usermask++) {
?>
<label class='checkbox'>
  <input type='checkbox' name='<?php echo escape_output($params['select_id']); ?>' value='<?php echo intval(pow(2, $usermask)); ?>'<?php echo (($params['selected'] & intval(pow(2, $usermask))) ? " checked='checked'" : ""); ?>/><?php echo escape_output(convert_usermask_to_text(pow(2, $usermask))); ?>
</label>
<?php
  }
?>