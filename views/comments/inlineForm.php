<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  check_partial_include(__FILE__);
?>
    <form class='form-inline' action='<?php echo ($this->id === 0) ? $this->url("new") : $this->url("edit"); ?>' method='POST'>
      <?php echo ($this->id === 0) ? "" : "<input type='hidden' name='comment[id]' value='".intval($this->id)."' />"; ?>
      <input type='hidden' name='comment[user_id]' value='<?php echo intval($currentUser->id); ?>' />
      <input type='hidden' name='comment[type]' value='<?php echo escape_output(($this->id === 0) ? get_class($params['currentObject']) : $this->type()); ?>' />
      <input type='hidden' name='comment[parent_id]' value='<?php echo ($this->id === 0) ? intval($params['currentObject']->id) : $this->parent()->id; ?>' />
      <input type='text' name='comment[message]'<?php echo ($this->id === 0) ? "placeholder='Leave a comment!'" : "value='".escape_output($this->message())."'"; ?> />
      <button type='submit' class='btn btn-primary'><?php echo ($this->id === 0) ? "Send" : "Update"; ?></button>
    </form>