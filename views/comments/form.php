<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // Takes a parameter at params['currentObject'] specifying the object this comment is being posted to.
?>
    <?php echo $this->app->form(array('action' => ($this->id === 0) ? $this->url("new") : $this->url("edit"), 'class' => 'form-horizontal')); ?>
      <?php echo ($this->id === 0) ? "" : "<input type='hidden' name='comment[id]' value='".intval($this->id)."' />"; ?>
      <?php echo $this->input('user_id', ['type' => 'hidden', 'value' => $this->app->user->id]); ?>
      <?php echo $this->input('type', ['type' => 'hidden', 'value' => escape_output(($this->id === 0) ? get_class($params['currentObject']) : $this->type())]); ?>
      <?php echo $this->input('parent_id', ['type' => 'hidden', 'value' => ($this->id ? $params['currentObject']->id : $this->parent()->id)]); ?>
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