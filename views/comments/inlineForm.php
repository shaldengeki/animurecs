<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
    <?php echo $this->app->form(['action' => ($this->id === 0) ? $this->url("new") : $this->url("edit"), 'class' => 'form form-inline']); ?>
      <?php echo ($this->id === 0) ? "" : $this->input('id', ['type' => 'hidden']); ?>
      <?php echo $this->input('user_id', ['type' => 'hidden', 'value' => $this->app->user->id]); ?>
      <?php echo $this->input('type', ['type' => 'hidden', 'value' => escape_output(($this->id === 0) ? get_class($params['currentObject']) : $this->type())]); ?>
      <?php echo $this->input('parent_id', ['type' => 'hidden', 'value' => ($this->id ? $params['currentObject']->id : $this->parent()->id)]); ?>
      <div class='form-group'>
        <label class="sr-only" for="message">Message</label>
        <?php echo $this->input('message', ['class' => 'input-sm form-control', 'type' => 'text', 'placeholder' => 'Leave a comment!']); ?>
      </div>
      <button type='submit' class='btn btn-sm btn-primary'><?php echo ($this->id === 0) ? "Post" : "Update"; ?></button>
    </form>