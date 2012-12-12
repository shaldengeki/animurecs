<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  check_partial_include(__FILE__);
  $tagAnime = [];
  foreach ($this->anime() as $anime) {
    $tagAnime[] = array('id' => $anime->id, 'title' => $anime->title());
  }
  $anime = new Anime($this->dbConn, 0);
?>
    <form action='<?php echo ($this->id === 0) ? $this->url("new") : $this->url("edit"); ?>' method='POST' class='form-horizontal'><?php echo ($this->id === 0) ? "" : "<input type='hidden' name='tag[id]' value='".intval($this->id)."' />"; ?>
      <input name='tag[created_user_id]' type='hidden' value=<?php echo $this->id === 0 ? intval($currentUser->id) : $this->createdUser()->id; ?> />
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='tag[name]'>Name</label>
          <div class='controls'>
            <input name='tag[name]' type='text' class='input-xlarge' id='tag[name]'<?php echo ($this->id === 0) ? "" : " value='".escape_output($this->name())."'"; ?> />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[description]'>Description</label>
          <div class='controls'>
            <textarea class='field span4' name='tag[description]' rows='3' id='tag[description]'><?php echo ($this->id === 0) ? "" : escape_output($this->description()); ?></textarea>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[tag_type_id]'>Tag Type</label>
          <div class='controls'>
            <?php echo display_tag_type_dropdown($this->dbConn, "tag[tag_type_id]", ($this->id === 0 ? False : intval($this->type()->id))); ?>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[anime_tags]'>Anime</label>
          <div class='controls'>
            <input name='tag[anime_tags]' type='text' class='token-input input-small' data-field='title' data-url='<?php echo $anime->url('token_search'); ?>' data-value='<?php echo $this->id === 0 ? "[]" : escape_output(json_encode($tagAnime)); ?>' id='tag[anime_tags]' />
          </div>
        </div>
        <div class='form-actions'>
          <button type='submit' class='btn btn-primary'><?php echo ($this->id === 0) ? "Create Tag" : "Save changes"; ?></button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'><?php echo ($this->id === 0) ? "Go back" : "Discard changes"; ?></a>
        </div>
      </fieldset>
    </form>