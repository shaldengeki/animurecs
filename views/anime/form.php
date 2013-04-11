<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $firstAnime = Anime::first($this->app);
  $anime = isset($params['anime']) ? $params['anime'] : $this;

  $animeTags = [];
  $firstTag = Tag::first($anime->app);
  if ($anime->tags()) {
    foreach ($anime->tags()->load('info') as $tag) {
      $animeTags[] = ['id' => $tag->id, 'name' => $tag->name()];
    }
  }
  echo $anime->app->form(['action' => ($anime->id === 0) ? $firstAnime->url("new") : $anime->url("edit"), 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal']);
?>
    <?php echo ($anime->id === 0) ? "" : $anime->input('id', ['type' => 'hidden']); ?>
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='anime[title]'>Series Title</label>
          <div class='controls'>
            <?php echo $anime->input('title', ['type' => 'text', 'class' => 'input-xlarge']); ?>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[description]'>Description</label>
          <div class='controls'>
            <textarea class='field span4' name='anime[description]' rows='3' id='anime[description]'><?php echo ($anime->id === 0) ? "" : escape_output($anime->description()); ?></textarea>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[episode_count]'>Episodes</label>
          <div class='controls'>
            <?php echo $anime->input('episode_count', ['type' => 'number', 'min' => 0, 'step' => 1, 'class' => 'input-small']); ?> episodes at 
            <?php echo $anime->input('episode_length', ['name' => 'anime[episode_minutes]', 'id' => 'anime[episode_minutes]', 'value' => round($anime->episodeLength()/60, 2), 'type' => 'number', 'min' => 0, 'step' => 1, 'class' => 'input-small']); ?> minutes per episode
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='anime[anime_tags]'>Tags</label>
          <div class='controls'>
            <?php echo $anime->input('anime_tags', ['type' => 'text', 'class' => 'token-input input-small', 'data-field' => 'name', 'data-url' => $firstTag->url("token_search"), 'data-value' => ($anime->id === 0 ? "[]" : escape_output(json_encode(array_values($animeTags))))]); ?>
          </div>
        </div>
<?php
  if ($anime->id != 0) {
?>        <div class='control-group'>
          <label class='control-label' for='anime_image'>Image</label>
          <div class='controls'>
            <?php echo $anime->input('anime_image', ['type' => 'file', 'class' => 'input-file', 'onChange' => 'displayImagePreview(this.files);']); ?>
            <span class='help-inline'>Max size 300x300, JPEG/PNG/GIF.</span>
          </div>
        </div>
<?php
  }
  if ($anime->allow($anime->app->user, $anime->id === 0 ? 'new' : 'edit')) {
?>        <div class='control-group'>
          <label class='control-label' for='anime[approved]'>Approved</label>
          <div class='controls'>
            <?php 
              $approvedParams = ['type' => 'checkbox', 'value' => 1];
              if ($anime->isApproved()) {
                $approvedParams['checked'] = 'checked';
              }
              echo $anime->input('approved', $approvedParams); ?>
          </div>
          <?php echo $anime->input('approved_user_id', ['type' => 'hidden', 'value' => ($anime->isApproved() ? intval($anime->approvedUser()->id) : intval($anime->app->user->id))]); ?>
        </div>
<?php
  }
?>    <div class='form-actions'>
          <button type='submit' class='btn btn-primary'><?php echo ($anime->id === 0) ? "Add Anime" : "Save changes"; ?></button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'><?php echo ($anime->id === 0) ? "Go back" : "Discard changes"; ?></a>
<?php
  if ($anime->id !== 0) {
?>
          <a class='btn btn-danger' href='<?php echo $anime->url('delete', Null, ['csrf_token' => $this->app->csrfToken]); ?>'>Delete</a>
<?php
  }
?>
        </div>
      </fieldset>
    </form>