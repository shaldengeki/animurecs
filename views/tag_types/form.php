<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $firstTagType = TagType::first($this->app);
  $tagType = isset($params['tagType']) ? $params['tagType'] : $this;

?>
    <?php echo $tagType->app->form(['action' => ($tagType->id === 0) ? $tagType->url("new") : $tagType->url("edit"), 'class' => 'form-inline']); ?>
      <?php echo ($tagType->id === 0) ? "" : $tagType->input('id', ['type' => 'hidden']); ?>
      <?php echo $tagType->input('created_user_id', ['type' => 'hidden', 'value' => ($tagType->id ? $tagType->createdUser()->id : $tagType->app->user->id)]); ?>
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='tag_types[name]'>Name</label>
          <div class='controls'>
            <?php echo $tagType->input('name', ['type' => 'text', 'class' => 'input-xlarge']); ?>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag_types[description]'>Description</label>
          <div class='controls'>
            <?php echo $tagType->textArea('description', ['class' => 'field col-md-4', 'rows' => 3], ($tagType->id === 0) ? "" : escape_output($tagType->description)); ?>
          </div>
        </div>
        <div class='form-actions'>
          <button type='submit' class='btn btn-primary'><?php echo ($tagType->id === 0) ? "Create Tag Type" : "Save changes"; ?></button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn btn-default'><?php echo ($tagType->id === 0) ? "Go back" : "Discard changes"; ?></a>
<?php
  if ($tagType->id !== 0) {
?>
          <a class='btn btn-danger' href='<?php echo $tagType->url('delete', Null, ['csrf_token' => $this->app->csrfToken]); ?>'>Delete</a>
<?php
  }
?>
        </div>
      </fieldset>
    </form>