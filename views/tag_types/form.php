<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
    <?php echo $this->app->form(array('action' => ($this->id === 0) ? $this->url("new") : $this->url("edit"), 'class' => 'form-inline')); ?>
      <?php echo ($this->id === 0) ? "" : "<input type='hidden' name='tag_type[id]' value='".intval($this->id)."' />"; ?>
      <?php echo $this->input('created_user_id', ['type' => 'hidden', 'value' => ($this->id ? $this->createdUser()->id : $this->app->user->id)]); ?>
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='tag_type[name]'>Name</label>
          <div class='controls'>
            <?php echo $this->input('name', ['type' => 'text', 'class' => 'input-xlarge']); ?>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag_type[description]'>Description</label>
          <div class='controls'>
            <textarea class='field span4' name='tag_type[description]' rows='3' id='tag_type[description]'><?php echo ($this->id === 0) ? "" : escape_output($this->description); ?></textarea>
          </div>
        </div>
        <div class='form-actions'>
          <button type='submit' class='btn btn-primary'><?php echo ($this->id === 0) ? "Create Tag Type" : "Save changes"; ?></button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'><?php echo ($this->id === 0) ? "Go back" : "Discard changes"; ?></a>
        </div>
      </fieldset>
    </form>