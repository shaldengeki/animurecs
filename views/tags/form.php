<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $firstTag = Tag::first($this->app);
  $tag = isset($params['tag']) ? $params['tag'] : $this;

  $tagAnime = [];
  foreach ($this->anime()->load('info') as $anime) {
    $tagAnime[] = ['id' => $anime->id, 'title' => $anime->title()];
  }
  $anime = Anime::first($this->app);
?>
    <?php echo $tag->app->form(['action' => ($tag->id === 0) ? $tag->url("new") : $tag->url("edit"), 'class' => 'form-inline']); ?>
      <?php echo ($tag->id === 0) ? "" : $tag->input('id', ['type' => 'hidden']); ?>
      <?php echo $tag->input('created_user_id', ['type' => 'hidden', 'value' => ($tag->id ? $tag->createdUser()->id : $tag->app->user->id)]); ?>
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='tag[name]'>Name</label>
          <div class='controls'>
            <?php echo $tag->input('name', ['type' => 'text', 'class' => 'input-xlarge']); ?>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[description]'>Description</label>
          <div class='controls'>
            <textarea class='field span4' name='tag[description]' rows='3' id='tag[description]'><?php echo ($tag->id === 0) ? "" : escape_output($tag->description()); ?></textarea>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[tag_type_id]'>Tag Type</label>
          <div class='controls'>
            <?php echo $tag->view('tagTypeDropdown'); ?>
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='tag[anime_tags]'>Anime</label>
          <div class='controls'>
            <?php echo $tag->input('anime_tags', ['type' => 'text', 'class' => 'token-input input-small', 'data-field' => 'title', 'data-url' => $anime->url('token_search'), 'data-value' => ($tag->id ? escape_output(json_encode($tagAnime)) : "[]")]); ?>
          </div>
        </div>
        <div class='form-actions'>
          <button type='submit' class='btn btn-primary'><?php echo ($tag->id === 0) ? "Create Tag" : "Save changes"; ?></button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'><?php echo ($tag->id === 0) ? "Go back" : "Discard changes"; ?></a>
<?php
  if ($tag->id !== 0) {
?>
          <a class='btn btn-danger' href='<?php echo $tag->url('delete', Null, ['csrf_token' => $this->app->csrfToken]); ?>'>Delete</a>
<?php
  }
?>
        </div>
      </fieldset>
    </form>