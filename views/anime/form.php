<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $animeTags = [];
  $firstTag = Tag::first($this->app);
  if ($this->tags()) {
    foreach ($this->tags()->load('info') as $tag) {
      $animeTags[] = ['id' => $tag->id, 'name' => $tag->name()];
    }
  }
  echo $this->app->form(['action' => ($this->id === 0) ? $this->url("new") : $this->url("edit"), 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal']);
?>
    <?php echo ($this->id === 0) ? "" : $this->input('id', ['type' => 'hidden']); ?>
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='anime[title]'>Series Title</label>
          <div class='controls'>
            <?php echo $this->input('title', ['type' => 'text', 'class' => 'input-xlarge']); ?>
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
            <?php echo $this->input('episode_count', ['type' => 'number', 'min' => 0, 'step' => 1, 'class' => 'input-small']); ?> episodes at 
            <?php echo $this->input('episode_length', ['name' => 'anime[episode_minutes]', 'id' => 'anime[episode_minutes]', 'value' => round($this->episodeLength()/60, 2), 'type' => 'number', 'min' => 0, 'step' => 1, 'class' => 'input-small']); ?> minutes per episode
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[anime_tags]'>Tags</label>
          <div class='controls'>
            <?php echo $this->input('anime_tags', ['type' => 'text', 'class' => 'token-input input-small', 'data-field' => 'name', 'data-url' => $firstTag->url("token_search"), 'data-value' => ($this->id === 0 ? "[]" : escape_output(json_encode(array_values($animeTags))))]); ?>
          </div>
        </div>
<?php
  if ($this->id != 0) {
?>        <div class='control-group'>
          <label class='control-label' for='anime_image'>Image</label>
          <div class='controls'>
            <?php echo $this->input('anime_image', ['type' => 'file', 'class' => 'input-file', 'onChange' => 'displayImagePreview(this.files);']); ?>
            <span class='help-inline'>Max size 300x300, JPEG/PNG/GIF.</span>
          </div>
        </div>
<?php
  }
  if ($this->allow($this->app->user, $this->id === 0 ? 'new' : 'edit')) {
?>        <div class='control-group'>
          <label class='control-label' for='anime[approved]'>Approved</label>
          <div class='controls'>
            <?php 
              $approvedParams = ['type' => 'checkbox', 'value' => 1];
              if ($this->isApproved()) {
                $approvedParams['checked'] = 'checked';
              }
              echo $this->input('approved', $approvedParams); ?>
          </div>
          <?php echo $this->input('approved_user_id', ['type' => 'hidden', 'value' => ($this->isApproved() ? intval($this->approvedUser()->id) : intval($this->app->user->id))]); ?>
        </div>
<?php
  }
?>    <div class='form-actions'>
          <button type='submit' class='btn btn-primary'><?php echo ($this->id === 0) ? "Add Anime" : "Save changes"; ?></button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'><?php echo ($this->id === 0) ? "Go back" : "Discard changes"; ?></a>
        </div>
      </fieldset>
    </form>