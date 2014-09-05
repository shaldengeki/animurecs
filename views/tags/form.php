<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $firstTag = Tag::Get($this->app);
  $tag = isset($params['tag']) ? $params['tag'] : $this;

  $tagAnime = [];
  foreach ($this->anime as $anime) {
    $tagAnime[] = ['id' => $anime->id, 'title' => $anime->title];
  }
  $anime = Anime::Get($this->app);
?>
    <?php echo $tag->app->form(['action' => ($tag->id === 0) ? $tag->url("new") : $tag->url("edit"), 'class' => 'form-inline']); ?>
      <?php echo ($tag->id === 0) ? "" : $tag->input('id', ['type' => 'hidden']); ?>
      <?php echo $tag->input('created_user_id', ['type' => 'hidden', 'value' => ($tag->id ? $tag->createdUser->id : $tag->app->user->id)]); ?>
      <fieldset>
        <div class='form-group'>
          <label class='control-label col-sm-2' for='tags[name]'>Name</label>
          <div class='col-sm-10'>
            <?php echo $tag->input('name', ['type' => 'text', 'class' => 'input-xlarge']); ?>
          </div>
        </div>
        <div class='form-group'>
          <label class='control-label col-sm-2' for='tags[description]'>Description</label>
          <div class='col-sm-10'>
            <?php echo $tag->textArea('description', ['class' => 'field col-md-4', 'rows' => 3], ($tag->id === 0) ? "" : escape_output($tag->description)); ?>
          </div>
        </div>
        <div class='form-group'>
          <label class='control-label col-sm-2' for='tags[tag_type_id]'>Tag Type</label>
          <div class='col-sm-10'>
            <?php echo $tag->view('tagTypeDropdown'); ?>
          </div>
        </div>
        <div class='form-group'>
          <label class='control-label col-sm-2' for='tags[anime_tags]'>Anime</label>
          <div class='col-sm-10'>
            <?php echo $tag->input('anime_tags', ['type' => 'text', 'class' => 'token-input input-sm', 'data-field' => 'title', 'data-url' => $anime->url('token_search'), 'data-value' => ($tag->id ? escape_output(json_encode($tagAnime)) : "[]")]); ?>
          </div>
        </div>
        <div class='form-group'>
          <div class='col-sm-offset-2 col-sm-10'>
            <button type='submit' class='btn btn-primary'><?php echo ($tag->id === 0) ? "Create Tag" : "Save changes"; ?></button>
            <a href='#' onClick='window.location.replace(document.referrer);' class='btn btn-default'><?php echo ($tag->id === 0) ? "Go back" : "Discard changes"; ?></a>
<?php
  if ($tag->id !== 0) {
?>
            <a class='btn btn-danger' href='<?php echo $tag->url('delete', Null, ['csrf_token' => $this->app->csrfToken]); ?>'>Delete</a>
<?php
  }
?>
          </div>
        </div>
      </fieldset>
    </form>