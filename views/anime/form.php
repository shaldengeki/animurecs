<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $firstAnime = Anime::Get($this->app);
  $anime = isset($params['anime']) ? $params['anime'] : $this;

  $animeTags = [];
  $firstTag = Tag::Get($anime->app);
  if ($anime->tags) {
    foreach ($anime->tags as $tag) {
      $animeTags[] = ['id' => $tag->id, 'name' => $tag->name];
    }
  }
  echo $anime->app->form(['action' => ($anime->id === 0) ? $firstAnime->url("new") : $anime->url("edit"), 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal']);
?>
    <?php echo ($anime->id === 0) ? "" : $anime->input('id', ['type' => 'hidden']); ?>
      <fieldset>
        <div class='form-group'>
          <label class='control-label col-sm-2' for='anime[title]'>Series Title</label>
          <div class='col-sm-10'>
            <?php echo $anime->input('title', ['type' => 'text', 'class' => 'input-xlarge']); ?>
          </div>
        </div>
        <div class='form-group'>
          <label class='control-label col-sm-2' for='anime[description]'>Description</label>
          <div class='col-sm-10'>
            <?php echo $anime->textArea('description', ['class' => 'field col-md-4', 'rows' => 3], ($anime->id === 0) ? "" : escape_output($anime->description)); ?>
          </div>
        </div>
        <div class='form-group'>
          <label class='control-label col-sm-2' for='anime[episode_count]'>Episodes</label>
          <div class='col-sm-10'>
            <?php echo $anime->input('episode_count', ['type' => 'number', 'min' => 0, 'step' => 1, 'class' => 'input-sm']); ?> episodes at 
            <?php echo $anime->input('episode_length', ['name' => 'anime[episode_minutes]', 'id' => 'anime[episode_minutes]', 'value' => round($anime->episodeLength/60, 2), 'type' => 'number', 'min' => 0, 'step' => 1, 'class' => 'input-sm']); ?> minutes per episode
          </div>
        </div>
        <div class='form-group'>
          <label class='control-label col-sm-2' for='anime[anime_tags]'>Tags</label>
          <div class='col-sm-10'>
            <?php echo $anime->input('anime_tags', ['type' => 'text', 'class' => 'token-input input-sm', 'data-field' => 'name', 'data-url' => $firstTag->url("token_search"), 'data-value' => ($anime->id === 0 ? "[]" : escape_output(json_encode(array_values($animeTags))))]); ?>
          </div>
        </div>
<?php
  if ($anime->id != 0) {
?>
        <div class='form-group'>
          <label class='control-label col-sm-2' for='anime_image'>Image</label>
          <div class='col-sm-10'>
            <?php echo $anime->input('anime_image', ['type' => 'file', 'class' => 'input-file', 'onChange' => 'displayImagePreview(this.files);']); ?>
            <span class='help-inline'>Max size 300x300, JPEG/PNG/GIF.</span>
          </div>
        </div>
<?php
  }
  if ($anime->allow($anime->app->user, $anime->id === 0 ? 'new' : 'edit')) {
?>
        <div class='form-group'>
          <div class='col-sm-offset-2 col-sm-10'>
            <label>
              <?php 
                $approvedParams = ['type' => 'checkbox', 'value' => 1];
                if ($anime->isApproved()) {
                  $approvedParams['checked'] = 'checked';
                }
                echo $anime->input('approved', $approvedParams); 
              ?>
              Approved
            </label>
          </div>
          <?php echo $anime->input('approved_user_id', ['type' => 'hidden', 'value' => ($anime->isApproved() ? intval($anime->approvedUser->id) : intval($anime->app->user->id))]); ?>
        </div>
<?php
  }
?>
        <div class='form-group'>
          <div class='col-sm-offset-2 col-sm-10'>
            <button type='submit' class='btn btn-primary'><?php echo ($anime->id === 0) ? "Add Anime" : "Save changes"; ?></button>
            <a href='#' onClick='window.location.replace(document.referrer);' class='btn btn-default'><?php echo ($anime->id === 0) ? "Go back" : "Discard changes"; ?></a>
<?php
  if ($anime->id !== 0) {
?>
            <a class='btn btn-danger' href='<?php echo $anime->url('delete', Null, ['csrf_token' => $this->app->csrfToken]); ?>'>Delete</a>
<?php
  }
?>
          </div>
        </div>
      </fieldset>
    </form>