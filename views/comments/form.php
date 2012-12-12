<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $app->check_partial_include(__FILE__);
?>
    <form action='<?php echo ($this->id === 0) ? $this->url("new") : $this->url("edit"); ?>' method='POST' class='form-horizontal'><?php echo ($this->id === 0) ? "" : "<input type='hidden' name='comment[id]' value='".intval($this->id)."' />"; ?>
      <input type='hidden' name='comment[user_id]' value='<?php echo intval($app->user->id); ?>' />
      <input type='hidden' name='comment[type]' value='<?php echo escape_output(($this->id === 0) ? get_class($params['currentObject']) : $this->type()); ?>' />
      <input type='hidden' name='comment[parent_id]' value='<?php echo ($this->id === 0) ? intval($params['currentObject']->id) : $this->parent()->id; ?>' />
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='comment[message]'>Comment</label>
          <div class='controls'>
            <textarea class='field span4' name='comment[message]' rows='3' id='comment[message]'><?php echo ($this->id === 0) ? "" : escape_output($this->message()); ?></textarea>
          </div>
        </div>

        <div class='form-actions'>
          <button type='submit' class='btn btn-primary'><?php echo ($this->id === 0) ? "Add Comment" : "Save changes"; ?></button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'><?php echo ($this->id === 0) ? "Go back" : "Discard changes"; ?></a>
        </div>
      </fieldset>
    </form>