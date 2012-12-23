<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $animeTags = [];
  $blankTag = new Tag($this->app, 0);
  foreach ($this->tags()->load('info') as $tag) {
    $animeTags[] = array('id' => $tag->id, 'name' => $tag->name());
  }
  echo $this->app->form(array('action' => ($this->id === 0) ? $this->url("new") : $this->url("edit"), 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal'));
?>
    <?php echo ($this->id === 0) ? "" : "<input type='hidden' name='anime[id]' value='".intval($this->id)."' />"; ?>
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='anime[title]'>Series Title</label>
          <div class='controls'>
            <input name='anime[title]' type='text' class='input-xlarge' id='anime[title]'<?php echo ($this->id === 0) ? "" : " value='".escape_output($this->title())."'"; ?> />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[description]'>Description</label>
          <div class='controls'>
            <textarea class='field span4' name='anime[description]' rows='3' id='anime[description]'><?php echo ($this->id === 0) ? "" : escape_output($this->description()); ?></textarea>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[episode_count]'>Episodes</label>
          <div class='controls'>
            <input name='anime[episode_count]' type='number' min=0 step=1 class='input-small' id='anime[episode_count]'<?php echo ($this->id === 0) ? "" : " value=".intval($this->episodeCount()); ?> /> episodes at 
            <input name='anime[episode_minutes]' type='number' min=0 step=1 class='input-small' id='anime[episode_minutes]'<?php echo ($this->id === 0) ? "" : " value=".intval($this->episodeLength()/60); ?> /> minutes per episode
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[anime_tags]'>Tags</label>
          <div class='controls'>
            <input name='anime[anime_tags]' type='text' class='token-input input-small' data-field='name' data-url='<?php echo $blankTag->url("token_search"); ?>' data-value='<?php echo $this->id === 0 ? "[]" : escape_output(json_encode(array_values($animeTags))); ?>' id='anime[anime_tags]' />
          </div>
        </div>
<?php
  if ($this->id != 0) {
?>        <div class='control-group'>
          <label class='control-label' for='anime_image'>Image</label>
          <div class='controls'>
            <input name='anime_image' class='input-file' type='file' onChange='displayImagePreview(this.files);' /><span class='help-inline'>Max size 300x300, JPEG/PNG/GIF.</span>
          </div>
        </div>
<?php
  }
  if ($this->allow($this->app->user, $this->id === 0 ? 'new' : 'edit')) {
?>        <div class='control-group'>
          <label class='control-label' for='anime[approved]'>Approved</label>
          <div class='controls'>
            <input name='anime[approved]' type='checkbox' value=1 <?php echo $this->isApproved() ? "checked=checked" : ""; ?>/>
          </div>
          <input name='anime[approved_user_id]' type='hidden' value='<?php echo $this->isApproved() ? intval($this->approvedUser()->id) : $this->app->user->id; ?>' />
        </div>
<?php
  }
?>    <div class='form-actions'>
          <button type='submit' class='btn btn-primary'><?php echo ($this->id === 0) ? "Add Anime" : "Save changes"; ?></button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'><?php echo ($this->id === 0) ? "Go back" : "Discard changes"; ?></a>
        </div>
      </fieldset>
    </form>